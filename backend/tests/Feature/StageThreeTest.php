<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerHouseholdDetail;
use App\Models\CustomerRequirement;
use App\Models\DutyType;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Services\CustomerManager;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class StageThreeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void { parent::setUp(); $this->seed(DatabaseSeeder::class); }

    public function test_super_admin_can_view_customers(): void { $this->actingAs($this->admin())->get('/admin/customers')->assertOk(); }
    public function test_unauthorized_staff_cannot_view_customers(): void { $this->actingAs($this->staff())->get('/admin/customers')->assertForbidden(); }

    public function test_customer_and_all_nested_details_can_be_created(): void
    {
        $this->actingAs($this->admin())->post('/admin/customers',$this->payload())->assertSessionHasNoErrors()->assertRedirect();
        $customer=Customer::firstOrFail(); $requirement=$customer->requirements()->firstOrFail();
        $this->assertMatchesRegularExpression('/^HH-CUS-\d{6}$/',$customer->customer_code);
        $this->assertMatchesRegularExpression('/^HH-REQ-\d{6}$/',$requirement->requirement_code);
        $this->assertSame(Service::first()->id,$requirement->service_id);
        $this->assertSame(DutyType::first()->id,$requirement->duty_type_id);
        $this->assertNotNull($requirement->householdDetail);
        $this->assertNotNull($requirement->accommodationDetail);
        $this->assertNotNull($requirement->workingCondition);
        $this->assertNotNull($requirement->workerPreference);
        $this->assertDatabaseHas('customer_requirement_status_history',['customer_requirement_id'=>$requirement->id,'new_status'=>'open']);
    }

    public function test_customer_code_cannot_be_modified(): void
    {
        $customer=$this->makeCustomer();$original=$customer->customer_code;$customer->update(['customer_code'=>'CHANGED']);
        $this->assertSame($original,$customer->fresh()->customer_code);
    }

    public function test_one_customer_can_have_multiple_requirements(): void
    {
        $customer=$this->makeCustomer();$manager=app(CustomerManager::class);$manager->createRequirement($customer,$this->requirementData(),$this->admin()->id);$manager->createRequirement($customer,$this->requirementData(),$this->admin()->id);
        $this->assertCount(3,$customer->requirements()->get());
    }

    public function test_registration_rolls_back_when_nested_save_fails(): void
    {
        CustomerHouseholdDetail::creating(fn()=>throw new RuntimeException('Nested failure'));
        try { app(CustomerManager::class)->register($this->payload(),$this->admin()->id); } catch (RuntimeException) {}
        $this->assertDatabaseCount('customers',0);$this->assertDatabaseCount('customer_requirements',0);
    }

    public function test_requirement_status_change_creates_history(): void
    {
        $r=$this->makeCustomer()->requirements()->firstOrFail();
        $this->actingAs($this->admin())->put('/admin/customer-requirements/'.$r->id,array_merge($this->requirementData(),['requirement_status'=>'worker_search','status_reason'=>'Search started']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('customer_requirement_status_history',['customer_requirement_id'=>$r->id,'old_status'=>'open','new_status'=>'worker_search','reason'=>'Search started']);
    }

    public function test_customer_search_works(): void
    {
        $this->makeCustomer(['name'=>'Unique Sharma']);
        $this->actingAs($this->admin())->get('/admin/customers?search=Unique')->assertOk()->assertSee('Unique Sharma');
        $this->actingAs($this->admin())->get('/admin/customers?search=Missing')->assertOk()->assertDontSee('Unique Sharma');
    }

    public function test_requirement_filters_and_open_quick_filter_work(): void
    {
        $customer=$this->makeCustomer();$r=$customer->requirements()->first();$r->update(['requirement_status'=>'completed']);
        $this->actingAs($this->admin())->get('/admin/customer-requirements?status=completed')->assertOk()->assertSee($r->requirement_code);
        $this->actingAs($this->admin())->get('/admin/customer-requirements?open=1')->assertOk()->assertDontSee($r->requirement_code);
    }

    public function test_duplicate_mobile_shows_warning_and_does_not_create_silently(): void
    {
        $customer=$this->makeCustomer();$before=Customer::count();
        $this->actingAs($this->admin())->from('/admin/customers/create')->post('/admin/customers',$this->payload(['mobile_number'=>$customer->mobile_number]))->assertRedirect('/admin/customers/create')->assertSessionHas('duplicate_customer_id',$customer->id);
        $this->assertSame($before,Customer::count());
    }

    public function test_dashboard_uses_real_customer_counts(): void
    {
        $this->makeCustomer();
        $this->actingAs($this->admin())->get('/admin/dashboard')->assertOk()->assertSee('Total Customers')->assertSee('Open Requirements');
    }

    public function test_customer_and_requirement_pages_render(): void
    {
        $customer=$this->makeCustomer();$requirement=$customer->requirements()->firstOrFail();$admin=$this->admin();
        $this->actingAs($admin)->get('/admin/customers/create')->assertOk()->assertSee('Register Customer');
        $this->actingAs($admin)->get('/admin/customers/'.$customer->id)->assertOk()->assertSee($customer->customer_code);
        $this->actingAs($admin)->get('/admin/customers/'.$customer->id.'/edit')->assertOk()->assertSee('Requirement history remains unchanged');
        $this->actingAs($admin)->get('/admin/customers/'.$customer->id.'/requirements/create')->assertOk()->assertSee('Add Service Requirement');
        $this->actingAs($admin)->get('/admin/customer-requirements/'.$requirement->id)->assertOk()->assertSee($requirement->requirement_code);
        $this->actingAs($admin)->get('/admin/customer-requirements/'.$requirement->id.'/edit')->assertOk()->assertSee('Edit Service Requirement');
    }

    private function makeCustomer(array $overrides=[]): Customer { return app(CustomerManager::class)->register($this->payload($overrides),$this->admin()->id); }
    private function payload(array $overrides=[]): array { return array_merge(['registration_date'=>today()->format('Y-m-d'),'name'=>'Test Customer','mobile_number'=>'9876543210','country'=>'India','status'=>'active'], $this->requirementData(), $overrides); }
    private function requirementData(): array { return ['service_id'=>Service::firstOrFail()->id,'duty_type_id'=>DutyType::firstOrFail()->id,'required_gender'=>'no_preference','number_of_persons'=>1,'requirement_status'=>'open','house_type'=>'2_bhk','total_family_members'=>4,'accommodation_provided'=>'yes','food_provided'=>'yes','breakfast_provided'=>true,'working_hours_text'=>'8 AM - 6 PM','experience_requirement'=>'1_2_years']; }
    private function admin(): User { return User::whereHas('role',fn($q)=>$q->where('slug','super-admin'))->firstOrFail(); }
    private function staff(): User { return User::factory()->create(['role_id'=>Role::where('slug','staff')->firstOrFail()->id,'is_active'=>true]); }
}
