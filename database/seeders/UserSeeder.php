<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password123'),
                'role' => 'admin',
                'status' => 'active',
            ]
        );

        // Project managers
        User::firstOrCreate(
            ['email' => 'manager1@example.com'],
            [
                'name' => 'John Manager',
                'password' => bcrypt('password123'),
                'role' => 'manager',
                'status' => 'active',
            ]
        );

        User::firstOrCreate(
            ['email' => 'manager2@example.com'],
            [
                'name' => 'Jane Manager',
                'password' => bcrypt('password123'),
                'role' => 'manager',
                'status' => 'active',
            ]
        );

        // Employees
        for ($i = 1; $i <= 5; $i++) {
            User::firstOrCreate(
                ['email' => "employee{$i}@example.com"],
                [
                    'name' => "Employee {$i}",
                    'password' => bcrypt('password123'),
                    'role' => 'employee',
                    'status' => 'active',
                ]
            );
        }
    }
}
