<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (tenant('id') !== null) {
            Service::create([
                'name' => 'General Consultation',
                'description' => 'A basic session with a specialist.',
                'price' => 50.00,
                'duration_minutes' => 15,
            ]);

            Service::create([
                'name' => 'Premium Service & Treatment',
                'description' => 'Extended care with advanced methods.',
                'price' => 120.00,
                'duration_minutes' => 45,
            ]);

            Service::create([
                'name' => 'Quick Express Service',
                'description' => 'Fast turnaround for urgent needs.',
                'price' => 30.00,
                'duration_minutes' => 10,
            ]);
        }
    }
}
