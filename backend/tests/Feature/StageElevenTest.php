<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DutyType;
use App\Models\Enquiry;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StageElevenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void { parent::setUp(); $this->seed(DatabaseSeeder::class); }

    public function test_public_catalog_and_contact_endpoints_are_safe(): void
    {
        $this->getJson('/api/public/services')->assertOk()->assertJsonPath('success', true)->assertJsonPath('data.0.name', 'Maid');
        $this->getJson('/api/public/duty-types')->assertOk()->assertJsonPath('data.0.name', 'Part Time');
        $this->getJson('/api/public/settings/contact')->assertOk()->assertJsonMissingPath('data.bank_account_number')->assertJsonMissingPath('data.signature_path');
    }

    public function test_public_enquiry_stores_utm_and_generates_code(): void
    {
        $response = $this->postJson('/api/public/enquiries', ['name'=>'Website Visitor','mobile_number'=>'9876543210','email'=>'visitor@example.com','city'=>'Ahmedabad','service_slug'=>'maid','duty_type_slug'=>'part-time','source_page'=>'https://example.test/maid-service.html','utm_source'=>'google','utm_campaign'=>'care']);
        $response->assertCreated()->assertJsonPath('success', true)->assertJsonStructure(['data'=>['enquiry_code']]);
        $this->assertDatabaseHas('enquiries',['enquiry_code'=>$response->json('data.enquiry_code'),'utm_source'=>'google','source'=>'website']);
    }

    public function test_invalid_public_enquiry_and_rate_limit_are_handled(): void
    {
        $this->postJson('/api/public/enquiries', ['name'=>'x'])->assertStatus(422);
        $payload=['name'=>'Rate Test','mobile_number'=>'9876543211','service_slug'=>'maid','duty_type_slug'=>'part-time'];
        for($i=0;$i<4;$i++) $this->postJson('/api/public/enquiries',$payload)->assertCreated();
        $this->postJson('/api/public/enquiries',$payload)->assertStatus(429);
    }

    public function test_admin_can_convert_enquiry_once_and_reuse_customer_mobile(): void
    {
        $admin=User::whereHas('role',fn($q)=>$q->where('slug','super-admin'))->firstOrFail();
        $enquiry=Enquiry::create(['enquiry_code'=>'HH-ENQ-TEST-1','name'=>'Lead','mobile_number'=>'9876543222','service_id'=>Service::where('slug','maid')->value('id'),'duty_type_id'=>DutyType::where('slug','part-time')->value('id'),'status'=>'new']);
        $this->actingAs($admin)->post(route('admin.enquiries.convert',$enquiry))->assertRedirect();
        $enquiry->refresh();
        $this->assertSame('converted',$enquiry->status->value);
        $this->assertNotNull($enquiry->converted_customer_id);
        $this->assertNotNull($enquiry->converted_requirement_id);
        $this->actingAs($admin)->post(route('admin.enquiries.convert',$enquiry))->assertRedirect();
        $this->assertSame(1, Customer::where('mobile_number','9876543222')->count());
    }

    public function test_staff_without_enquiry_permissions_is_blocked(): void
    {
        $staff=User::factory()->create(['role_id'=>Role::where('slug','staff')->value('id'),'is_active'=>true]);
        $this->actingAs($staff)->get(route('admin.enquiries.index'))->assertForbidden();
    }
}
