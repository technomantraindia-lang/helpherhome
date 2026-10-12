<?php

namespace Database\Seeders;

use App\Models\DutyType;
use Illuminate\Database\Seeder;

class DutyTypeSeeder extends Seeder
{
    public function run(): void
    {
        $legacy = [
            ['Part Time', 'part-time', 101],
            ['Full Time', 'full-time', 102],
            ['Day Shift', 'day-shift', 103],
            ['Night Shift', 'night-shift', 104],
            ['Custom / Other', 'custom', 105],
        ];

        foreach ($legacy as [$name, $slug, $order]) {
            DutyType::updateOrCreate(['slug' => $slug], ['name' => $name, 'sort_order' => $order, 'is_active' => false]);
        }

        $hourly = [
            ['2 Hours', '2-hours', 1],
            ['3 Hours', '3-hours', 2],
            ['4 Hours', '4-hours', 3],
            ['5 Hours', '5-hours', 4],
            ['6 Hours', '6-hours', 5],
            ['7 Hours', '7-hours', 6],
            ['8 Hours', '8-hours', 7],
            ['9 Hours', '9-hours', 8],
            ['10 Hours', '10-hours', 9],
            ['11 Hours', '11-hours', 10],
            ['12 Hours', '12-hours', 11],
            ['24 Hours / Live-In', 'live-in-24-hours', 12],
        ];

        foreach ($hourly as [$name, $slug, $order]) {
            DutyType::updateOrCreate(['slug' => $slug], ['name' => $name, 'sort_order' => $order, 'is_active' => true]);
        }
    }
}
