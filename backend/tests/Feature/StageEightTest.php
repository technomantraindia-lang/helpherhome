<?php

namespace Tests\Feature;

use App\Enums\WorkerGeneratedDocumentType;
use App\Models\DocumentShareLink;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Worker;
use App\Models\WorkerDocument;
use App\Models\WorkerGeneratedDocument;
use App\Services\WorkerDocumentGenerationService;
use App\Services\WorkerManager;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StageEightTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_authorized_admin_generates_versioned_resume_with_snapshot_and_pdf(): void
    {
        $worker = $this->worker();
        $worker->update(['photo_path' => 'workers/'.$worker->id.'/photo/photo.jpg']);
        Storage::disk('public')->put($worker->photo_path, 'photo');
        $admin = $this->admin();

        $document = app(WorkerDocumentGenerationService::class)->generateResume($worker, $admin->id);

        $this->assertSame(WorkerGeneratedDocumentType::Resume, $document->document_type);
        $this->assertSame(1, $document->version_number);
        $this->assertSame($worker->name, $document->snapshot_json['worker']['name']);
        $this->assertArrayNotHasKey('identity_documents', $document->snapshot_json);
        $this->assertStringNotContainsString('Internal note', json_encode($document->snapshot_json));
        Storage::disk('local')->assertExists($document->pdf_path);

        $worker->update(['name' => 'Changed Worker', 'salary_expectation' => 22000]);
        $newDocument = app(WorkerDocumentGenerationService::class)->generateResume($worker->fresh(), $admin->id);
        $this->assertSame(2, $newDocument->version_number);
        $this->assertSame('Test Worker', $document->fresh()->snapshot_json['worker']['name']);
        $this->assertSame('Changed Worker', $newDocument->snapshot_json['worker']['name']);
        Storage::disk('local')->assertExists($document->pdf_path);
        Storage::disk('local')->assertExists($newDocument->pdf_path);
    }

    public function test_registration_form_contains_identity_and_five_reference_rows(): void
    {
        $worker = $this->worker();
        WorkerDocument::create(['worker_id' => $worker->id, 'document_type' => 'identity_proof', 'document_name' => 'Aadhaar Card', 'document_number' => '123456789012', 'verification_status' => 'verified']);
        $document = app(WorkerDocumentGenerationService::class)->generateRegistrationForm($worker, $this->admin()->id);

        $this->assertSame(WorkerGeneratedDocumentType::RegistrationForm, $document->document_type);
        $this->assertSame('123456789012', $document->snapshot_json['identity_documents'][0]['document_number']);
        $this->assertCount(5, $document->snapshot_json['references']);
        Storage::disk('local')->assertExists($document->pdf_path);
    }

    public function test_routes_permissions_and_secure_share_work_for_resume(): void
    {
        $worker = $this->worker();
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.workers.resume.preview', $worker))->assertOk()->assertSee('WORKER PROFILE / RESUME');
        $response = $this->actingAs($admin)->post(route('admin.workers.resume.generate', $worker));
        $response->assertRedirect();
        $document = WorkerGeneratedDocument::firstOrFail();
        $this->actingAs($admin)->get(route('admin.worker-generated-documents.download', $document))->assertOk();
        $this->actingAs($admin)->post(route('admin.worker-generated-documents.share.whatsapp', $document))->assertRedirect();
        $link = DocumentShareLink::where('document_type', 'worker_generated_document')->firstOrFail();
        $this->get(route('shared.worker-generated-documents.download', $link->token))->assertOk();
        $this->actingAs($admin)->get(route('admin.worker-generated-documents.index'))->assertOk()->assertSee($document->document_number);
    }

    public function test_staff_without_explicit_resume_permission_is_forbidden(): void
    {
        $worker = $this->worker();
        $staff = User::factory()->create(['role_id' => Role::where('slug', 'staff')->firstOrFail()->id, 'is_active' => true]);
        $this->actingAs($staff)->get(route('admin.workers.resume.preview', $worker))->assertForbidden();
        $this->actingAs($staff)->post(route('admin.workers.resume.generate', $worker))->assertForbidden();
    }

    public function test_type_specific_download_permission_can_access_private_pdf(): void
    {
        $worker = $this->worker();
        $document = app(WorkerDocumentGenerationService::class)->generateResume($worker, $this->admin()->id);
        $staff = User::factory()->create(['role_id' => Role::where('slug', 'staff')->firstOrFail()->id, 'is_active' => true]);
        $staff->role->permissions()->attach(Permission::where('slug', 'worker-resumes.download')->value('id'));
        $this->actingAs($staff)->get(route('admin.worker-generated-documents.download', $document))->assertOk();
    }

    private function worker(): Worker
    {
        $service = \App\Models\Service::firstOrFail();
        $references = collect(range(1, 5))->map(fn ($i) => ['name' => 'Reference '.$i, 'mobile_number' => '987654321'.$i, 'relation' => 'Friend', 'occupation' => 'Worker'])->all();
        return app(WorkerManager::class)->create([
            'registration_date' => today()->format('Y-m-d'), 'name' => 'Test Worker', 'mobile_number' => '9876543210',
            'address_line_1' => '1 Test Street', 'city' => 'Mumbai', 'state' => 'Maharashtra', 'country' => 'India',
            'date_of_birth' => '1994-01-01', 'gender' => 'female', 'marital_status' => 'single', 'years_of_experience' => 5,
            'experience_notes' => 'Domestic care experience', 'salary_expectation' => 18000, 'preferred_work_location' => 'Mumbai',
            'availability_status' => 'available', 'worker_status' => 'active', 'notes' => 'Internal note', 'services' => [$service->id], 'references' => $references,
        ], $this->admin()->id);
    }

    private function admin(): User
    {
        return User::whereHas('role', fn ($query) => $query->where('slug', 'super-admin'))->firstOrFail();
    }
}
