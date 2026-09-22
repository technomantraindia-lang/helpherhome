<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $name = env('SUPER_ADMIN_NAME');
        $email = env('SUPER_ADMIN_EMAIL');
        $password = env('SUPER_ADMIN_PASSWORD');

        if (! $name || ! $email || ! $password) {
            if (app()->environment('production')) {
                throw new RuntimeException('SUPER_ADMIN_NAME, SUPER_ADMIN_EMAIL and SUPER_ADMIN_PASSWORD are required in production.');
            }
            $name = $name ?: 'Local Super Admin';
            $email = $email ?: 'admin@helperhome.local';
            $password = $password ?: 'ChangeMe123!';
        }

        User::updateOrCreate(['email' => $email], [
            'name' => $name,
            'password' => $password,
            'role_id' => Role::where('slug', 'super-admin')->firstOrFail()->id,
            'is_active' => true,
        ]);
    }
}
