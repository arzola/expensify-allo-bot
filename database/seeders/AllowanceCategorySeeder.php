<?php

namespace Database\Seeders;

use App\Models\AllowanceCategory;
use Illuminate\Database\Seeder;

class AllowanceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AllowanceCategory::create([
            'id' => 1,
            'name' => 'Tech Allowance (TX)',
            'annual_limit' => 1200,
            'description' => '👩‍💻',
            'is_active' => true,
        ]);

        AllowanceCategory::create([
            'id' => 2,
            'name' => 'Book Allowance (TX)',
            'annual_limit' => 300,
            'description' => '📚',
            'is_active' => true,
        ]);

        AllowanceCategory::create([
            'id' => 3,
            'name' => 'Home Office Stipend (TX)',
            'annual_limit' => 1000,
            'description' => '🏠',
            'is_active' => true,
        ]);

        AllowanceCategory::create([
            'id' => 4,
            'name' => 'Wellness Benefit (TX)',
            'annual_limit' => 1500,
            'description' => '🚲',
            'is_active' => true,
        ]);
    }
} 