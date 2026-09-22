<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StageTenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_authorized_admin_can_open_reports_and_global_search(): void
    {
        $admin = User::whereHas('role', fn ($q) => $q->where('slug', 'super-admin'))->firstOrFail();
        $this->actingAs($admin)->get(route('admin.reports.overview'))->assertOk()->assertSee('Reports Overview');
        $this->actingAs($admin)->get(route('admin.reports.workers', ['quick' => 'month']))->assertOk()->assertSee('Worker Reports');
        $this->actingAs($admin)->get(route('admin.reports.invoices'))->assertOk()->assertSee('Invoice Reports');
        foreach (['customers','requirements','assignments','replacements','agreements','payments','outstanding','documents'] as $report) {
            $this->actingAs($admin)->get(route('admin.reports.'.$report))->assertOk();
        }
        $this->actingAs($admin)->get(route('admin.search', ['q' => 'HH']))->assertOk()->assertSee('Global Search');
    }

    public function test_staff_without_report_permissions_is_forbidden(): void
    {
        $staff = User::factory()->create(['role_id' => Role::where('slug', 'staff')->firstOrFail()->id, 'is_active' => true]);
        $this->actingAs($staff)->get(route('admin.reports.overview'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.search', ['q' => 'x']))->assertForbidden();
    }

    public function test_csv_export_is_available_to_authorized_admin(): void
    {
        $admin = User::whereHas('role', fn ($q) => $q->where('slug', 'super-admin'))->firstOrFail();
        $response = $this->actingAs($admin)->get(route('admin.reports.payments.export', ['start_date' => today()->subMonth()->toDateString(), 'end_date' => today()->toDateString()]));
        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
