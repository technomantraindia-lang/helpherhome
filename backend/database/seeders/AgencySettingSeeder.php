<?php

namespace Database\Seeders;

use App\Models\AgencySetting;
use Illuminate\Database\Seeder;

class AgencySettingSeeder extends Seeder
{
    public function run(): void
    {
        $logoPath = null;
        if (file_exists(public_path('images/helper-home-logo.png'))) {
            \Illuminate\Support\Facades\Storage::disk('public')->put('agency/logo/helper-home-logo.png', file_get_contents(public_path('images/helper-home-logo.png')));
            $logoPath = 'agency/logo/helper-home-logo.png';
        }

        AgencySetting::firstOrCreate([], [
            'business_name' => 'Helper Home', 'tagline' => 'Home Care & Domestic Services',
            'owner_authorized_person' => 'Jatin Prajapati', 'phone_primary' => '08799544275',
            'whatsapp_number' => '08799544275', 'email' => 'helperhomeahmedabad@gmail.com',
            'country' => 'India', 'default_currency' => 'INR',
            'logo_path' => $logoPath,
        ]);
    }
}
