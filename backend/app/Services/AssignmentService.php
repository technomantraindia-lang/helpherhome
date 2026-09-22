<?php
namespace App\Services;

use App\Enums\AssignmentStatus;use App\Enums\AvailabilityStatus;use App\Enums\CustomerStatus;use App\Enums\RequirementStatus;use App\Enums\ShortlistStatus;use App\Enums\WorkerStatus;
use App\Models\Assignment;use App\Models\CustomerRequirement;use App\Models\Worker;
use Illuminate\Support\Facades\DB;use Illuminate\Support\Str;use Illuminate\Validation\ValidationException;

class AssignmentService
{
    public function create(CustomerRequirement $requirement,Worker $worker,array $data,int $actorId):Assignment
    {
        return DB::transaction(function()use($requirement,$worker,$data,$actorId){
            $requirement=CustomerRequirement::lockForUpdate()->with('customer')->findOrFail($requirement->id);$worker=Worker::lockForUpdate()->findOrFail($worker->id);
            if($requirement->customer->status!==CustomerStatus::Active)throw ValidationException::withMessages(['customer'=>'Customer must be active.']);
            if(in_array($requirement->requirement_status,[RequirementStatus::Completed,RequirementStatus::Cancelled]))throw ValidationException::withMessages(['requirement'=>'Completed or cancelled requirements cannot be assigned.']);
            if($worker->worker_status!==WorkerStatus::Active||in_array($worker->availability_status,[AvailabilityStatus::Working,AvailabilityStatus::Unavailable]))throw ValidationException::withMessages(['worker'=>'Worker is not currently available for assignment.']);
            if(!$worker->services()->whereKey($requirement->service_id)->exists()&&!($data['service_override']??false))throw ValidationException::withMessages(['worker'=>'Worker does not provide the required service.']);
            if(($data['service_override']??false)&&blank($data['override_reason']??null))throw ValidationException::withMessages(['override_reason'=>'An override reason is required.']);
            $start=$data['assignment_start_date'];$end=$data['assignment_end_date']??null;
            $overlap=Assignment::where('worker_id',$worker->id)->whereIn('status',AssignmentStatus::active())->whereDate('assignment_start_date','<=',$end?:'9999-12-31')->where(fn($q)=>$q->whereNull('assignment_end_date')->orWhereDate('assignment_end_date','>=',$start))->exists();
            if($overlap)throw ValidationException::withMessages(['worker'=>'This worker already has an active assignment during the selected period.']);
            $startsNow=$start<=today()->format('Y-m-d');$status=$startsNow?AssignmentStatus::Active:AssignmentStatus::Confirmed;
            $assignment=Assignment::create(['assignment_code'=>'PENDING-'.Str::uuid(),'customer_id'=>$requirement->customer_id,'customer_requirement_id'=>$requirement->id,'worker_id'=>$worker->id,'service_id'=>$requirement->service_id,'duty_type_id'=>$data['duty_type_id']??$requirement->duty_type_id,'assignment_start_date'=>$start,'assignment_end_date'=>$end,'working_hours_text'=>$data['working_hours_text']??null,'monthly_salary'=>$data['monthly_salary']??null,'agency_service_charge'=>$data['agency_service_charge']??null,'work_location'=>$data['work_location']??null,'status'=>$status,'confirmed_at'=>now(),'started_at'=>$startsNow?now():null,'assigned_by'=>$actorId,'notes'=>$data['notes']??null]);
            $assignment->forceFill(['assignment_code'=>sprintf('HH-ASG-%06d',$assignment->id)])->saveQuietly();
            $assignment->statusHistory()->create(['old_status'=>null,'new_status'=>$status->value,'changed_by'=>$actorId,'reason'=>'Assignment created']);
            $this->setRequirementStatus($requirement,RequirementStatus::Assigned,$actorId,'Worker assigned');
            $worker->update(['availability_status'=>$startsNow?AvailabilityStatus::Working:AvailabilityStatus::Assigned,'updated_by'=>$actorId]);
            $requirement->workerShortlists()->where('worker_id',$worker->id)->update(['status'=>ShortlistStatus::Selected->value]);
            return $assignment->fresh();
        });
    }
    public function start(Assignment $assignment,int $actorId):Assignment{return DB::transaction(function()use($assignment,$actorId){$assignment=Assignment::lockForUpdate()->findOrFail($assignment->id);$this->transition($assignment,AssignmentStatus::Active,[AssignmentStatus::Confirmed],$actorId,'Assignment started',['started_at'=>now()]);Worker::lockForUpdate()->findOrFail($assignment->worker_id)->update(['availability_status'=>AvailabilityStatus::Working,'updated_by'=>$actorId]);return $assignment->fresh();});}
    public function complete(Assignment $assignment,string $completionDate,?string $reason,int $actorId):Assignment{return DB::transaction(function()use($assignment,$completionDate,$reason,$actorId){$assignment=Assignment::lockForUpdate()->findOrFail($assignment->id);$this->transition($assignment,AssignmentStatus::Completed,[AssignmentStatus::Active],$actorId,$reason?:'Assignment completed',['completed_at'=>$completionDate.' 23:59:59','assignment_end_date'=>$completionDate]);Worker::lockForUpdate()->findOrFail($assignment->worker_id)->update(['availability_status'=>AvailabilityStatus::Available,'updated_by'=>$actorId]);$this->setRequirementStatus(CustomerRequirement::lockForUpdate()->findOrFail($assignment->customer_requirement_id),RequirementStatus::Completed,$actorId,'Assignment completed');return $assignment->fresh();});}
    public function cancel(Assignment $assignment,string $reason,bool $stillRequired,int $actorId):Assignment{return DB::transaction(function()use($assignment,$reason,$stillRequired,$actorId){$assignment=Assignment::lockForUpdate()->findOrFail($assignment->id);$this->transition($assignment,AssignmentStatus::Cancelled,[AssignmentStatus::Confirmed,AssignmentStatus::Active],$actorId,$reason,['cancelled_at'=>now()]);Worker::lockForUpdate()->findOrFail($assignment->worker_id)->update(['availability_status'=>AvailabilityStatus::Available,'updated_by'=>$actorId]);$this->setRequirementStatus(CustomerRequirement::lockForUpdate()->findOrFail($assignment->customer_requirement_id),$stillRequired?RequirementStatus::WorkerSearch:RequirementStatus::Cancelled,$actorId,$reason);return $assignment->fresh();});}
    public function markReplaced(Assignment $assignment,int $actorId,string $reason):void{$this->transition($assignment,AssignmentStatus::Replaced,[AssignmentStatus::Active,AssignmentStatus::Confirmed],$actorId,$reason,['cancelled_at'=>now()]);}
    private function transition(Assignment $a,AssignmentStatus $to,array $allowed,int $actorId,string $reason,array $extra=[]):void{if(!in_array($a->status,$allowed,true))throw ValidationException::withMessages(['status'=>"Cannot change {$a->status->value} assignment to {$to->value}."]);$old=$a->status->value;$a->update(array_merge(['status'=>$to],$extra));$a->statusHistory()->create(['old_status'=>$old,'new_status'=>$to->value,'changed_by'=>$actorId,'reason'=>$reason]);}
    private function setRequirementStatus(CustomerRequirement $r,RequirementStatus $to,int $actorId,string $reason):void{$old=$r->requirement_status->value;if($old===$to->value)return;$r->update(['requirement_status'=>$to,'updated_by'=>$actorId]);$r->statusHistory()->create(['old_status'=>$old,'new_status'=>$to->value,'changed_by'=>$actorId,'reason'=>$reason]);}
}
