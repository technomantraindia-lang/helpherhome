<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['Maid', 'maid'], ['Servant', 'servant'], ['Babysitter', 'babysitter'],
            ['Japa Maid / Nanny', 'japa-maid-nanny'], ['Elderly Caretaker', 'elderly-caretaker'],
            ['Patient Caretaker', 'patient-caretaker'], ['Cook', 'cook'], ['Driver', 'driver'],
            ['Domestic Couple', 'domestic-couple'], ['Office Boy / Peon', 'office-boy-peon'], ['Deep Cleaning', 'deep-cleaning'],
        ];

        foreach ($services as $index => [$name, $slug]) {
            Service::updateOrCreate(['slug' => $slug], ['name' => $name, 'sort_order' => $index + 1, 'is_active' => true]);
        }
    }
}
