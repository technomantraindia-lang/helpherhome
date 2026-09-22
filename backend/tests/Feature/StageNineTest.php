<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerGeneratedDocument;
use App\Models\CustomerRequirement;
use App\Models\DocumentShareLink;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Services\CustomerDocumentGenerationService;
use App\Services\CustomerManager;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\WorkerDocumentGenerationService;
use App\Services\WorkerManager;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StageNineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_admin_generates_customer_registration_form_from_selected_requirement(): void
    {
        [$customer, $requirement] = $this->customerRequirement();
        $document = app(CustomerDocumentGenerationService::class)->generateRegistrationForm($requirement, $this->admin()->id);
        $snapshot = $document->snapshot_json;
        $this->assertSame($customer->name, $snapshot['customer']['name']);
        $this->assertSame($requirement->requirement_code, $snapshot['requirement']['requirement_code']);
        $this->assertSame('Maid', $snapshot['requirement']['service_name']);
        $this->assertSame(4, $snapshot['household']['adults_count']);
        $this->assertSame('separate', $snapshot['accommodation']['room_type']);
        $this->assertSame('09:00', $snapshot['working']['work_start_time']);
        $this->assertSame('5_plus_years', $snapshot['worker_preferences']['experience_requirement']);
        Storage::disk('local')->assertExists($document->pdf_path);
    }

    public function test_multiple_requirements_require_explicit_selection(): void
    {
        [$customer, $requirement] = $this->customerRequirement();
        app(CustomerManager::class)->createRequirement($customer, ['service_id' => Service::where('name', 'Cook')->firstOrFail()->id, 'duty_type_id' => null, 'required_gender' => 'female', 'number_of_persons' => 1, 'requirement_status' => 'open'], $this->admin()->id);
        $this->actingAs($this->admin())->get(route('admin.customers.registration-form.preview', $customer))->assertStatus(422);
        $this->actingAs($this->admin())->get(route('admin.customer-requirements.registration-form.preview', $requirement))->assertOk()->assertSee('CUSTOMER REGISTRATION FORM');
    }

    public function test_customer_document_versions_keep_old_snapshot_after_customer_and_requirement_edits(): void
    {
        [$customer, $requirement] = $this->customerRequirement();
        $service = app(CustomerDocumentGenerationService::class);
        $first = $service->generateRegistrationForm($requirement, $this->admin()->id);
        $customer->update(['name' => 'Updated Customer']);
        $requirement->update(['monthly_salary_budget' => 25000]);
        $second = $service->generateRegistrationForm($requirement->fresh(), $this->admin()->id);
        $this->assertSame(1, $first->version_number);
        $this->assertSame(2, $second->version_number);
        $this->assertSame('Test Customer', $first->fresh()->snapshot_json['customer']['name']);
        $this->assertSame('18000.00', (string) $first->fresh()->snapshot_json['requirement']['monthly_salary_budget']);
        $this->assertSame('Updated Customer', $second->snapshot_json['customer']['name']);
        $this->assertSame('25000.00', (string) $second->snapshot_json['requirement']['monthly_salary_budget']);
        Storage::disk('local')->assertExists($first->pdf_path);
        Storage::disk('local')->assertExists($second->pdf_path);
    }

    public function test_customer_download_and_whatsapp_share_use_private_expiring_link(): void
    {
        [$customer, $requirement] = $this->customerRequirement();
        $document = app(CustomerDocumentGenerationService::class)->generateRegistrationForm($requirement, $this->admin()->id);
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.customer-generated-documents.download', $document))->assertOk();
        $this->actingAs($admin)->post(route('admin.customer-generated-documents.share.whatsapp', $document))->assertRedirect();
        $link = DocumentShareLink::where('document_type', 'customer_generated_document')->firstOrFail();
        $this->assertStringNotContainsString('storage/app', route('shared.customer-generated-documents.download', $link->token));
        $this->get(route('shared.customer-generated-documents.download', $link->token))->assertOk();
    }

    public function test_unauthorized_staff_cannot_generate_or_download_customer_form(): void
    {
        [$customer, $requirement] = $this->customerRequirement();
        $staff = User::factory()->create(['role_id' => Role::where('slug', 'staff')->firstOrFail()->id, 'is_active' => true]);
        $this->actingAs($staff)->get(route('admin.customer-requirements.registration-form.preview', $requirement))->assertForbidden();
        $this->actingAs($staff)->post(route('admin.customer-requirements.registration-form.generate', $requirement))->assertForbidden();
    }

    public function test_customer_and_worker_documents_are_searchable_in_document_center(): void
    {
        [$customer, $requirement] = $this->customerRequirement();
        app(CustomerDocumentGenerationService::class)->generateRegistrationForm($requirement, $this->admin()->id);
        $worker = $this->worker();
        app(WorkerDocumentGenerationService::class)->generateResume($worker, $this->admin()->id);
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.documents.index'))->assertOk()->assertSee('Customer Registration Form')->assertSee('Worker Resume');
        $this->actingAs($admin)->get(route('admin.documents.index', ['search' => $customer->name]))->assertOk()->assertSee($customer->customer_code);
        $this->actingAs($admin)->get(route('admin.customers.show', $customer))->assertOk()->assertSee('Customer Documents');
        $this->actingAs($admin)->get(route('admin.customer-requirements.show', $requirement))->assertOk()->assertSee('Customer Registration Form');
    }

    private function customerRequirement(): array
    {
        $service = Service::where('name', 'Maid')->firstOrFail();
        $customer = app(CustomerManager::class)->register(['registration_date' => today()->format('Y-m-d'), 'name' => 'Test Customer', 'mobile_number' => '9876543210', 'alternate_mobile_number' => '9876543211', 'email' => 'customer@example.com', 'location' => 'Andheri', 'address_line_1' => '10 Main Road', 'city' => 'Mumbai', 'state' => 'Maharashtra', 'pincode' => '400001', 'country' => 'India', 'status' => 'active', 'service_id' => $service->id, 'duty_type_id' => null, 'required_gender' => 'female', 'number_of_persons' => 1, 'requirement_status' => 'open', 'monthly_salary_budget' => 18000, 'monthly_agency_service_charge' => 2500, 'preferred_start_date' => today()->addDays(7)->format('Y-m-d'), 'detailed_work_description' => 'Cleaning and cooking', 'special_instructions' => 'No smoking', 'house_type' => '2bhk', 'total_family_members' => 5, 'adults_count' => 4, 'children_count' => 1, 'elderly_count' => 1, 'patients_count' => 0, 'food_preference' => 'both', 'pets_at_home' => true, 'children_at_home' => true, 'elderly_at_home' => true, 'patient_care_required' => false, 'lift_available' => true, 'outside_travel_required' => false, 'accommodation_provided' => 'yes', 'room_type' => 'separate', 'bathroom_type' => 'separate', 'food_provided' => 'yes', 'breakfast_provided' => true, 'lunch_provided' => true, 'dinner_provided' => true, 'tea_snacks_provided' => false, 'other_food_details' => 'Home food', 'accommodation_notes' => 'Separate room', 'working_hours_text' => '9 hours', 'work_start_time' => '09:00', 'work_end_time' => '18:00', 'expected_wakeup_time' => '06:00', 'expected_sleep_time' => '22:00', 'afternoon_rest' => true, 'rest_duration_hours' => 1, 'monthly_leave_days' => 4, 'leave_details' => 'Sunday', 'night_duty_required' => false, 'early_morning_work_required' => true, 'early_morning_work_details' => 'Breakfast preparation', 'preferred_age_min' => 25, 'preferred_age_max' => 45, 'experience_requirement' => '5_plus_years', 'language_preference' => 'Hindi', 'specific_skills' => 'Cooking', 'other_work_expected' => 'Laundry', 'worker_preference_notes' => 'Experienced worker'], $this->admin()->id);
        return [$customer, $customer->requirements->firstOrFail()];
    }

    private function worker(): \App\Models\Worker
    {
        $service = Service::firstOrFail();
        $references = collect(range(1, 5))->map(fn ($i) => ['name' => 'Reference '.$i, 'mobile_number' => '987654321'.$i, 'relation' => 'Friend'])->all();
        return app(WorkerManager::class)->create(['registration_date' => today(), 'name' => 'Center Worker', 'mobile_number' => '9876543220', 'address_line_1' => 'Worker Road', 'city' => 'Mumbai', 'state' => 'Maharashtra', 'country' => 'India', 'availability_status' => 'available', 'worker_status' => 'active', 'services' => [$service->id], 'references' => $references], $this->admin()->id);
    }

    private function admin(): User
    {
        return User::whereHas('role', fn ($query) => $query->where('slug', 'super-admin'))->firstOrFail();
    }
}
