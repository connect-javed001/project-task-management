<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $manager1 = User::where('email', 'manager1@example.com')->first();
        $manager2 = User::where('email', 'manager2@example.com')->first();

        // Project 1
        Project::firstOrCreate(
            ['name' => 'Website Redesign'],
            [
                'description' => 'Redesign company website with modern UI/UX',
                'start_date' => now()->subDays(30),
                'end_date' => now()->addDays(30),
                'status' => 'active',
                'assigned_manager_id' => $manager1->id,
                'created_by_id' => $admin->id,
            ]
        );

        // Project 2
        Project::firstOrCreate(
            ['name' => 'Mobile App Development'],
            [
                'description' => 'Develop iOS and Android mobile application',
                'start_date' => now()->subDays(15),
                'end_date' => now()->addDays(60),
                'status' => 'active',
                'assigned_manager_id' => $manager2->id,
                'created_by_id' => $admin->id,
            ]
        );

        // Project 3
        Project::firstOrCreate(
            ['name' => 'API Integration'],
            [
                'description' => 'Integrate third-party APIs',
                'start_date' => now()->subDays(5),
                'end_date' => now()->addDays(45),
                'status' => 'planning',
                'assigned_manager_id' => $manager1->id,
                'created_by_id' => $admin->id,
            ]
        );
    }
}
