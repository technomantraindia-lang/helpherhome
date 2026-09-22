<?php
namespace Tests\Feature;

use App\Enums\AssignmentStatus;use App\Enums\AvailabilityStatus;use App\Enums\ReplacementStatus;use App\Models\Assignment;use App\Models\Customer;use App\Models\CustomerRequirement;use App\Models\DutyType;use App\Models\ReplacementRequest;use App\Models\Role;use App\Models\Service;use App\Models\User;use App\Models\Worker;use App\Models\WorkerShortlist;use App\Services\AssignmentService;use App\Services\CustomerManager;use App\Services\ReplacementService;use App\Services\WorkerMatchingService;use Database\Seeders\DatabaseSeeder;use Illuminate\Foundation\Testing\RefreshDatabase;use Illuminate\Validation\ValidationException;use Tests\TestCase;

class StageFourTest extends TestCase
{
    use RefreshDatabase;
    private int $workerSequence=0;
    protected function setUp():void{parent::setUp();$this->seed(DatabaseSeeder::class);}

    public function test_matching_returns_service_workers_and_excludes_inactive_and_unavailable():void
    {
        $r=$this->requirement();$match=$this->service();$other=Service::whereKeyNot($match->id)->firstOrFail();
        $good=$this->worker($match);$this->worker($other);$this->worker($match,['worker_status'=>'inactive']);$this->worker($match,['availability_status'=>'unavailable']);
        $ids=app(WorkerMatchingService::class)->candidates($r)->pluck('id');
        $this->assertEquals([$good->id],$ids->all());
    }

    public function test_match_score_has_transparent_reason_breakdown_and_filters_work():void
    {
        $r=$this->requirement();$w=$this->worker($this->service(),['gender'=>'female','city'=>'Mumbai','years_of_experience'=>5]);$w->verification()->create(['overall_verification_status'=>'verified']);
        $candidate=app(WorkerMatchingService::class)->candidates($r,['gender'=>'female','verified_only'=>true])->firstOrFail();
        $this->assertIsInt($candidate->match_score);$this->assertArrayHasKey('Service Match',$candidate->match_reasons);$this->assertArrayHasKey('Verification',$candidate->match_reasons);
    }

    public function test_worker_can_be_shortlisted_only_once_for_requirement():void
    {
        $r=$this->requirement();$w=$this->worker($this->service());$admin=$this->admin();
        $url=route('admin.customer-requirements.shortlist',[$r,$w]);$this->actingAs($admin)->post($url)->assertRedirect();$this->actingAs($admin)->post($url)->assertRedirect();
        $this->assertDatabaseCount('worker_shortlists',1);$this->assertSame('shortlisted',WorkerShortlist::first()->status->value);
    }

    public function test_assignment_creation_generates_code_and_updates_worker_requirement_and_histories():void
    {
        [$a,$r,$w]=$this->assigned();
        $this->assertMatchesRegularExpression('/^HH-ASG-\d{6}$/',$a->assignment_code);
        $this->assertSame(AssignmentStatus::Active,$a->status);$this->assertSame(AvailabilityStatus::Working,$w->fresh()->availability_status);$this->assertSame('assigned',$r->fresh()->requirement_status->value);
        $this->assertDatabaseHas('assignment_status_history',['assignment_id'=>$a->id,'new_status'=>'active']);$this->assertDatabaseHas('customer_requirement_status_history',['customer_requirement_id'=>$r->id,'new_status'=>'assigned']);
    }

    public function test_future_assignment_is_confirmed_then_start_makes_worker_working():void
    {
        $r=$this->requirement();$w=$this->worker($this->service());$service=app(AssignmentService::class);$a=$service->create($r,$w,$this->assignmentData(['assignment_start_date'=>today()->addDay()->format('Y-m-d')]),$this->admin()->id);
        $this->assertSame(AssignmentStatus::Confirmed,$a->status);$this->assertSame(AvailabilityStatus::Assigned,$w->fresh()->availability_status);
        $service->start($a,$this->admin()->id);$this->assertSame(AssignmentStatus::Active,$a->fresh()->status);$this->assertSame(AvailabilityStatus::Working,$w->fresh()->availability_status);
    }

    public function test_overlapping_assignment_is_blocked_even_if_availability_is_manually_reset():void
    {
        [$a,$r,$w]=$this->assigned();$w->update(['availability_status'=>'available']);$other=$this->requirement(['mobile_number'=>'9000000002']);
        $this->expectException(ValidationException::class);app(AssignmentService::class)->create($other,$w,$this->assignmentData(),$this->admin()->id);
    }

    public function test_assignment_completion_releases_worker_and_completes_requirement():void
    {
        [$a,$r,$w]=$this->assigned();app(AssignmentService::class)->complete($a,today()->format('Y-m-d'),'Done',$this->admin()->id);
        $this->assertSame(AssignmentStatus::Completed,$a->fresh()->status);$this->assertSame(AvailabilityStatus::Available,$w->fresh()->availability_status);$this->assertSame('completed',$r->fresh()->requirement_status->value);
    }

    public function test_cancellation_requires_reason_and_releases_worker_for_new_search():void
    {
        [$a,$r,$w]=$this->assigned();$this->actingAs($this->admin())->post(route('admin.assignments.cancel',$a),['still_required'=>1])->assertSessionHasErrors('reason');
        app(AssignmentService::class)->cancel($a,'Customer requested change',true,$this->admin()->id);
        $this->assertSame(AssignmentStatus::Cancelled,$a->fresh()->status);$this->assertSame(AvailabilityStatus::Available,$w->fresh()->availability_status);$this->assertSame('worker_search',$r->fresh()->requirement_status->value);
    }

    public function test_invalid_assignment_status_transition_is_rejected():void
    {
        [$a]=$this->assigned();app(AssignmentService::class)->complete($a,today()->format('Y-m-d'),null,$this->admin()->id);
        $this->expectException(ValidationException::class);app(AssignmentService::class)->start($a,$this->admin()->id);
    }

    public function test_replacement_request_generates_code_and_history():void
    {
        [$a]=$this->assigned();$r=$this->replacement($a);
        $this->assertMatchesRegularExpression('/^HH-REP-\d{6}$/',$r->replacement_code);$this->assertDatabaseHas('replacement_status_history',['replacement_request_id'=>$r->id,'new_status'=>'requested']);
    }

    public function test_old_worker_is_excluded_from_replacement_matches():void
    {
        [$a,$requirement,$old]=$this->assigned();$replacement=$this->replacement($a);$new=$this->worker($this->service());
        $ids=app(WorkerMatchingService::class)->candidates($requirement,[], $replacement->old_worker_id)->pluck('id');
        $this->assertFalse($ids->contains($old->id));$this->assertTrue($ids->contains($new->id));
    }

    public function test_replacement_creates_new_assignment_and_preserves_old_assignment():void
    {
        [$oldAssignment,$requirement,$oldWorker]=$this->assigned();$replacement=$this->replacement($oldAssignment);$replacement=$this->readyReplacement($replacement);$newWorker=$this->worker($this->service());
        $newAssignment=app(ReplacementService::class)->assign($replacement,$newWorker,$this->assignmentData(),$this->admin()->id);
        $this->assertNotSame($oldAssignment->id,$newAssignment->id);$this->assertSame(AssignmentStatus::Replaced,$oldAssignment->fresh()->status);$this->assertSame($newWorker->id,$replacement->fresh()->new_worker_id);$this->assertSame($newAssignment->id,$replacement->fresh()->replacement_assignment_id);$this->assertSame(AvailabilityStatus::Available,$oldWorker->fresh()->availability_status);$this->assertDatabaseCount('assignments',2);
    }

    public function test_replacement_cannot_select_current_worker():void
    {
        [$a,,$old]=$this->assigned();$replacement=$this->readyReplacement($this->replacement($a));$old->update(['availability_status'=>'available']);
        $this->expectException(ValidationException::class);app(ReplacementService::class)->assign($replacement,$old,$this->assignmentData(),$this->admin()->id);
    }

    public function test_replacement_status_transitions_are_controlled_and_recorded():void
    {
        [$a]=$this->assigned();$r=$this->replacement($a);$service=app(ReplacementService::class);$r=$service->updateStatus($r,ReplacementStatus::UnderReview,'Reviewing',$this->admin()->id);$r=$service->updateStatus($r,ReplacementStatus::Approved,'Approved',$this->admin()->id);
        $this->assertNotNull($r->approved_at);$this->assertDatabaseHas('replacement_status_history',['replacement_request_id'=>$r->id,'old_status'=>'under_review','new_status'=>'approved']);
        $this->expectException(ValidationException::class);$service->updateStatus($r,ReplacementStatus::Completed,null,$this->admin()->id);
    }

    public function test_unauthorized_staff_cannot_assign_workers():void
    {
        $r=$this->requirement();$w=$this->worker($this->service());$staff=User::factory()->create(['role_id'=>Role::where('slug','staff')->firstOrFail()->id,'is_active'=>true]);
        $this->actingAs($staff)->post(route('admin.assignments.store',[$r,$w]),$this->assignmentData())->assertForbidden();$this->assertDatabaseCount('assignments',0);
    }

    public function test_stage_four_pages_render():void
    {
        [$a,$r,$w]=$this->assigned();$replacement=$this->replacement($a);$admin=$this->admin();
        foreach([route('admin.worker-matching.index',['requirement'=>$r->id]),route('admin.worker-shortlists.index'),route('admin.assignments.index'),route('admin.assignments.active'),route('admin.assignments.history'),route('admin.assignments.show',$a),route('admin.replacements.index'),route('admin.replacements.show',$replacement),route('admin.workers.show',$w),route('admin.customers.show',$r->customer)] as $url)$this->actingAs($admin)->get($url)->assertOk();
    }

    private function assigned():array{$r=$this->requirement();$w=$this->worker($this->service());$a=app(AssignmentService::class)->create($r,$w,$this->assignmentData(),$this->admin()->id);return [$a,$r,$w];}
    private function replacement(Assignment $a):ReplacementRequest{return app(ReplacementService::class)->create($a,['reason'=>'worker_left','requested_date'=>today()->format('Y-m-d')],$this->admin()->id);}
    private function readyReplacement(ReplacementRequest $r):ReplacementRequest{$s=app(ReplacementService::class);$r=$s->updateStatus($r,ReplacementStatus::UnderReview,null,$this->admin()->id);return $s->updateStatus($r,ReplacementStatus::Approved,null,$this->admin()->id);}
    private function requirement(array $overrides=[]):CustomerRequirement{$data=array_merge(['registration_date'=>today()->format('Y-m-d'),'name'=>'Customer '.uniqid(),'mobile_number'=>(string)random_int(7000000000,9999999999),'country'=>'India','status'=>'active','service_id'=>$this->service()->id,'duty_type_id'=>$this->duty()->id,'required_gender'=>'no_preference','number_of_persons'=>1,'requirement_status'=>'open','monthly_salary_budget'=>20000,'preferred_start_date'=>today()->format('Y-m-d'),'working_hours_text'=>'9 AM - 6 PM'],$overrides);return app(CustomerManager::class)->register($data,$this->admin()->id)->requirements()->firstOrFail();}
    private function worker(Service $service,array $overrides=[]):Worker{$this->workerSequence++;$w=Worker::create(array_merge(['worker_code'=>sprintf('HH-WRK-T%05d',$this->workerSequence),'registration_date'=>today(),'name'=>'Worker '.$this->workerSequence,'mobile_number'=>'800000'.sprintf('%04d',$this->workerSequence),'address_line_1'=>'Address','city'=>'Mumbai','state'=>'Maharashtra','country'=>'India','age'=>30,'gender'=>'female','years_of_experience'=>3,'salary_expectation'=>15000,'preferred_duty_type_id'=>$this->duty()->id,'availability_status'=>'available','worker_status'=>'active'], $overrides));$w->services()->attach($service);return $w;}
    private function assignmentData(array $overrides=[]):array{return array_merge(['assignment_start_date'=>today()->format('Y-m-d'),'assignment_end_date'=>today()->addMonth()->format('Y-m-d'),'duty_type_id'=>$this->duty()->id,'working_hours_text'=>'9 AM - 6 PM','monthly_salary'=>18000,'agency_service_charge'=>2000,'work_location'=>'Mumbai'],$overrides);}
    private function service():Service{return Service::firstOrFail();}private function duty():DutyType{return DutyType::firstOrFail();}private function admin():User{return User::whereHas('role',fn($q)=>$q->where('slug','super-admin'))->firstOrFail();}
}
