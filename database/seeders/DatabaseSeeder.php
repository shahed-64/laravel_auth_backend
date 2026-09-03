<?php

namespace Database\Seeders;

use App\Models\Staff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Staff::create([
            'name' => 'Super Manager',
            'user_name' => 'manager',
            'skill' => 'Administration',
            'role' => 'Manager',
            'email' => 'manager@coaching.com',
            'password' => Hash::make('12345678'),
            'image' => null,
            'salary' => 000
                 ]);
        Staff::create([
            'name' => 'Jubair Ahmed Masum',
            'user_name' => 'jubair',
            'skill' => 'Administration',
            'role' => 'Manager',
            'email' => 'masum@coaching.com',
            'password' => Hash::make('12345678'),
            'image' => null,
            'salary' => 000
                 ]);
    }
}
