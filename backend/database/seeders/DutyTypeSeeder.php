<?php

namespace Database\Seeders;

use App\Models\DutyType;
use Illuminate\Database\Seeder;

class DutyTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['Part Time', 'part-time'], ['Full Time', 'full-time'], ['24 Hours / Live-In', 'live-in-24-hours'],
            ['Day Shift', 'day-shift'], ['Night Shift', 'night-shift'], ['Custom / Other', 'custom'],
        ];

        foreach ($types as $index => [$name, $slug]) {
            DutyType::updateOrCreate(['slug' => $slug], ['name' => $name, 'sort_order' => $index + 1, 'is_active' => true]);
        }
    }
}
