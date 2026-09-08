<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GradingSystem;

class GradingSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        GradingSystem::insert([
            [
                'grade' => 'A+',
                'grade_point' => 5.00,
                'min_percentage' => 80.00,
            ],
            [
                'grade' => 'A',
                'grade_point' => 4.00,
                'min_percentage' => 70.00,
            ],
            [
                'grade' => 'A-',
                'grade_point' => 3.50,
                'min_percentage' => 60.00,
            ],
            [
                'grade' => 'B',
                'grade_point' => 3.00,
                'min_percentage' => 50.00,
            ],
            [
                'grade' => 'C',
                'grade_point' => 2.00,
                'min_percentage' => 40.00,
            ],
            [
                'grade' => 'D',
                'grade_point' => 1.00,
                'min_percentage' => 33.00,
            ],
            [
                'grade' => 'F',
                'grade_point' => 0.00,
                'min_percentage' => 0.00,
            ],
        ]);
    }
}
