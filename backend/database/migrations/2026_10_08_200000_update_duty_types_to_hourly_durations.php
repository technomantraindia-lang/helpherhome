<?php

use App\Models\DutyType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Deactivate old generic duty types without deleting them, preserving all historical associations
        DB::table('duty_types')
            ->whereIn('slug', ['part-time', 'full-time', 'day-shift', 'night-shift', 'custom'])
            ->update(['is_active' => false]);

        $newTypes = [
            ['name' => '2 Hours', 'slug' => '2-hours', 'sort_order' => 1],
            ['name' => '3 Hours', 'slug' => '3-hours', 'sort_order' => 2],
            ['name' => '4 Hours', 'slug' => '4-hours', 'sort_order' => 3],
            ['name' => '5 Hours', 'slug' => '5-hours', 'sort_order' => 4],
            ['name' => '6 Hours', 'slug' => '6-hours', 'sort_order' => 5],
            ['name' => '7 Hours', 'slug' => '7-hours', 'sort_order' => 6],
            ['name' => '8 Hours', 'slug' => '8-hours', 'sort_order' => 7],
            ['name' => '9 Hours', 'slug' => '9-hours', 'sort_order' => 8],
            ['name' => '10 Hours', 'slug' => '10-hours', 'sort_order' => 9],
            ['name' => '11 Hours', 'slug' => '11-hours', 'sort_order' => 10],
            ['name' => '12 Hours', 'slug' => '12-hours', 'sort_order' => 11],
            ['name' => '24 Hours / Live-In', 'slug' => 'live-in-24-hours', 'sort_order' => 12],
        ];

        foreach ($newTypes as $type) {
            DB::table('duty_types')->updateOrInsert(
                ['slug' => $type['slug']],
                [
                    'name' => $type['name'],
                    'sort_order' => $type['sort_order'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        // In reverse, restore active status to legacy duty types
        DB::table('duty_types')
            ->whereIn('slug', ['part-time', 'full-time', 'live-in-24-hours', 'day-shift', 'night-shift', 'custom'])
            ->update(['is_active' => true]);
    }
};
