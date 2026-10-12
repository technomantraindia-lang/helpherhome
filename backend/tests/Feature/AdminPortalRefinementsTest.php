<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DutyType;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Models\Worker;
use App\Services\CustomerManager;
use App\Services\WorkerManager;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPortalRefinementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_customer_registration_number_is_auto_generated_sequentially(): void
    {
        $manager = app(CustomerManager::class);
        $service = Service::firstOrFail();
        $duty = DutyType::where('is_active', true)->firstOrFail();

        $c1 = $manager->register([
            'name' => 'Cust One',
            'mobile_number' => '9111111111',
            'registration_date' => today(),
            'service_id' => $service->id,
            'duty_type_id' => $duty->id,
            'number_of_persons' => 1,
            'children_count' => 2,
            'elderly_count' => 0,
        ], $this->admin()->id);

        $c2 = $manager->register([
            'name' => 'Cust Two',
            'mobile_number' => '9222222222',
            'registration_date' => today(),
            'service_id' => $service->id,
            'duty_type_id' => $duty->id,
            'number_of_persons' => 1,
            'children_count' => 0,
            'elderly_count' => 1,
        ], $this->admin()->id);

        $this->assertSame('HH-CUST-000001', $c1->registration_number);
        $this->assertSame('HH-CUST-000002', $c2->registration_number);

        // Verify house & family derived values
        $this->assertTrue((bool) $c1->requirements->first()->householdDetail->children_at_home);
        $this->assertFalse((bool) $c1->requirements->first()->householdDetail->elderly_at_home);
        $this->assertFalse((bool) $c2->requirements->first()->householdDetail->children_at_home);
        $this->assertTrue((bool) $c2->requirements->first()->householdDetail->elderly_at_home);
    }

    public function test_worker_registration_number_is_auto_generated_sequentially(): void
    {
        $manager = app(WorkerManager::class);
        $service = Service::firstOrFail();

        $w1 = $manager->create([
            'name' => 'Worker One',
            'mobile_number' => '8111111111',
            'registration_date' => today(),
            'address_line_1' => 'Street 1',
            'services' => [$service->id],
            'primary_service_id' => $service->id,
            'city' => 'Mumbai',
            'state' => 'MH',
            'registration_source' => 'other',
            'registration_source_other' => 'Friend Referral',
        ], $this->admin()->id);

        $w2 = $manager->create([
            'name' => 'Worker Two',
            'mobile_number' => '8222222222',
            'registration_date' => today(),
            'address_line_1' => 'Street 2',
            'services' => [$service->id],
            'primary_service_id' => $service->id,
            'city' => 'Mumbai',
            'state' => 'MH',
            'registration_source' => 'direct_walkin',
        ], $this->admin()->id);

        $this->assertSame('HH-WRK-REG-000001', $w1->registration_number);
        $this->assertSame('HH-WRK-REG-000002', $w2->registration_number);
        $this->assertSame('Friend Referral', $w1->registration_source_other);
        $this->assertNull($w2->registration_source_other);
    }

    public function test_worker_pdf_download_routes_work(): void
    {
        $manager = app(WorkerManager::class);
        $service = Service::firstOrFail();

        $worker = $manager->create([
            'name' => 'Worker Download Test',
            'mobile_number' => '8333333333',
            'registration_date' => today(),
            'address_line_1' => 'Street 3',
            'services' => [$service->id],
            'primary_service_id' => $service->id,
            'city' => 'Mumbai',
            'state' => 'MH',
            'registration_source' => 'other',
            'registration_source_other' => 'Agency Partner',
        ], $this->admin()->id);

        $this->actingAs($this->admin())
            ->get(route('admin.workers.registration-form.download', $worker))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($this->admin())
            ->get(route('admin.workers.resume.download', $worker))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_active_duty_types_are_hourly_durations(): void
    {
        $activeNames = DutyType::where('is_active', true)->orderBy('sort_order')->pluck('name')->all();

        $expected = [
            '2 Hours', '3 Hours', '4 Hours', '5 Hours', '6 Hours',
            '7 Hours', '8 Hours', '9 Hours', '10 Hours', '11 Hours',
            '12 Hours', '24 Hours / Live-In'
        ];

        $this->assertSame($expected, $activeNames);
    }

    private function admin(): User
    {
        return User::whereHas('role', fn ($q) => $q->where('slug', 'super-admin'))->firstOrFail();
    }
}
