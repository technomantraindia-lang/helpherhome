<?php

namespace Tests\Feature;

use App\Models\AgencySetting;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StageOneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_login_works_and_tracks_last_login(): void
    {
        $user = User::where('email', 'admin@helperhome.local')->firstOrFail();

        $this->post('/login', ['email' => $user->email, 'password' => 'ChangeMe123!'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $user->id, 'action' => 'login']);
    }

    public function test_inactive_user_cannot_log_in_or_access_admin(): void
    {
        $user = $this->makeUser('inactive@example.com', 'staff', false);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->actingAs($user)->get('/admin/dashboard')->assertRedirect(route('login'));
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect(route('login'));
    }

    public function test_super_admin_can_access_settings(): void
    {
        $this->actingAs($this->superAdmin())->get('/admin/settings/agency')->assertOk();
    }

    public function test_staff_without_permission_cannot_access_settings(): void
    {
        $this->actingAs($this->makeUser('staff@example.com', 'staff'))->get('/admin/settings/agency')->assertForbidden();
    }

    public function test_services_list_loads(): void
    {
        $this->actingAs($this->superAdmin())->get('/admin/services')->assertOk()->assertSee('Maid');
    }

    public function test_service_can_be_created(): void
    {
        $this->actingAs($this->superAdmin())->post('/admin/services', [
            'name' => 'Home Nurse', 'slug' => 'home-nurse', 'sort_order' => 20, 'is_active' => 1,
        ])->assertRedirect(route('admin.services.index'));
        $this->assertDatabaseHas('services', ['slug' => 'home-nurse', 'is_active' => true]);
    }

    public function test_duty_type_can_be_created(): void
    {
        $this->actingAs($this->superAdmin())->post('/admin/duty-types', [
            'name' => 'Weekend', 'slug' => 'weekend', 'sort_order' => 10, 'is_active' => 1,
        ])->assertRedirect(route('admin.duty-types.index'));
        $this->assertDatabaseHas('duty_types', ['slug' => 'weekend']);
    }

    public function test_user_can_be_created_with_role(): void
    {
        $role = Role::where('slug', 'staff')->firstOrFail();
        $this->actingAs($this->superAdmin())->post('/admin/users', [
            'name' => 'Operations User', 'email' => 'operations@example.com', 'mobile' => '9876543210',
            'role_id' => $role->id, 'password' => 'Secure123!', 'password_confirmation' => 'Secure123!', 'is_active' => 1,
        ])->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'operations@example.com')->firstOrFail();
        $this->assertSame($role->id, $user->role_id);
        $this->assertTrue(Hash::check('Secure123!', $user->password));
    }

    public function test_agency_settings_and_logo_can_be_updated(): void
    {
        Storage::fake('public');
        $this->actingAs($this->superAdmin())->put('/admin/settings/agency', [
            'business_name' => 'Helper Home Ahmedabad', 'country' => 'India', 'default_currency' => 'INR',
            'email' => 'office@example.com', 'logo' => UploadedFile::fake()->image('logo.png'),
        ])->assertSessionHasNoErrors()->assertRedirect();

        $settings = AgencySetting::firstOrFail();
        $this->assertSame('Helper Home Ahmedabad', $settings->business_name);
        Storage::disk('public')->assertExists($settings->logo_path);
    }

    public function test_last_active_super_admin_cannot_be_deactivated(): void
    {
        $admin = $this->superAdmin();
        $this->actingAs($admin)->put('/admin/users/'.$admin->id, [
            'name' => $admin->name, 'email' => $admin->email, 'role_id' => $admin->role_id,
            'is_active' => 0,
        ])->assertSessionHasErrors('is_active');

        $this->assertTrue($admin->fresh()->is_active);
    }

    private function superAdmin(): User
    {
        return User::whereHas('role', fn ($query) => $query->where('slug', 'super-admin'))->firstOrFail();
    }

    private function makeUser(string $email, string $role, bool $active = true): User
    {
        return User::factory()->create([
            'email' => $email, 'password' => 'password', 'role_id' => Role::where('slug', $role)->firstOrFail()->id,
            'is_active' => $active,
        ]);
    }
}
