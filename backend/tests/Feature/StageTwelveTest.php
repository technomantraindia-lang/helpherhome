<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StageTwelveTest extends TestCase
{
    use RefreshDatabase;

    private int $outputBufferLevel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->outputBufferLevel = ob_get_level();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        Storage::fake('local');
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > $this->outputBufferLevel) ob_end_clean();
        parent::tearDown();
    }

    public function test_worker_operations_pages_render_and_identity_documents_are_private(): void
    {
        $admin = User::whereHas('role', fn ($q) => $q->where('slug', 'super-admin'))->firstOrFail();
        $this->actingAs($admin);
        foreach (['/admin/worker-availability', '/admin/worker-documents', '/admin/worker-interviews', '/admin/worker-verification'] as $uri) {
            $this->get($uri)->assertOk();
        }

        $worker = Worker::create([
            'worker_code' => 'HH-WRK-QA-001', 'registration_date' => today(), 'name' => 'QA Worker',
            'mobile_number' => '9876543210', 'address_line_1' => 'QA Address', 'city' => 'Ahmedabad', 'state' => 'Gujarat',
        ]);
        $this->post('/admin/workers/'.$worker->id.'/documents', [
            'document_type' => 'identity_proof', 'document_name' => 'ID proof',
            'file' => UploadedFile::fake()->image('id-proof.png'),
        ])->assertRedirect();
        $path = $worker->fresh()->documents()->firstOrFail()->file_path;
        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_security_headers_are_present_on_public_api(): void
    {
        $this->getJson('/api/public/services')->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }
}
