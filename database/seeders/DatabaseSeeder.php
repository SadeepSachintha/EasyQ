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
                'name' => 'Haircut & Styling',
                'description' => 'Classic haircut with wash and blow dry.',
                'price' => 35.00,
                'duration_minutes' => 30,
            ]);

            Service::create([
                'name' => 'Hair Coloring & Highlights',
                'description' => 'Professional full head color or highlights.',
                'price' => 85.00,
                'duration_minutes' => 90,
            ]);

            Service::create([
                'name' => 'Manicure & Pedicure',
                'description' => 'Complete nail care treatment and polish.',
                'price' => 45.00,
                'duration_minutes' => 45,
            ]);

            Service::create([
                'name' => 'Facial & Skincare',
                'description' => 'Deep cleansing facial and hydration mask.',
                'price' => 60.00,
                'duration_minutes' => 60,
            ]);
        }
    }
}
