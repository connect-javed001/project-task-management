<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $manager1 = User::where('email', 'manager1@example.com')->first();
        $employees = User::where('role', 'employee')->get();

        $project1 = Project::where('name', 'Website Redesign')->first();
        $project2 = Project::where('name', 'Mobile App Development')->first();

        // Tasks for Project 1
        Task::firstOrCreate(
            ['name' => 'Design Homepage Mockup', 'project_id' => $project1->id],
            [
                'description' => 'Create high-fidelity mockups for homepage',
                'priority' => 'high',
                'status' => 'in_progress',
                'deadline' => now()->addDays(5),
                'assigned_to_id' => $employees[0]->id,
                'estimated_hours' => 16,
                'created_by_id' => $admin->id,
            ]
        );

        Task::firstOrCreate(
            ['name' => 'Develop Frontend Components', 'project_id' => $project1->id],
            [
                'description' => 'Build React components for website',
                'priority' => 'high',
                'status' => 'todo',
                'deadline' => now()->addDays(15),
                'assigned_to_id' => $employees[1]->id,
                'estimated_hours' => 40,
                'created_by_id' => $admin->id,
            ]
        );

        Task::firstOrCreate(
            ['name' => 'Setup Database', 'project_id' => $project1->id],
            [
                'description' => 'Configure database schema and setup',
                'priority' => 'critical',
                'status' => 'in_progress',
                'deadline' => now()->addDays(3),
                'assigned_to_id' => $employees[2]->id,
                'estimated_hours' => 8,
                'created_by_id' => $admin->id,
            ]
        );

        // Tasks for Project 2
        Task::firstOrCreate(
            ['name' => 'Design Mobile UI', 'project_id' => $project2->id],
            [
                'description' => 'Design user interface for mobile app',
                'priority' => 'high',
                'status' => 'completed',
                'deadline' => now()->subDays(10),
                'assigned_to_id' => $employees[3]->id,
                'estimated_hours' => 20,
                'created_by_id' => $admin->id,
            ]
        );

        Task::firstOrCreate(
            ['name' => 'Develop Backend APIs', 'project_id' => $project2->id],
            [
                'description' => 'Build RESTful APIs for mobile app',
                'priority' => 'critical',
                'status' => 'in_progress',
                'deadline' => now()->addDays(20),
                'assigned_to_id' => $employees[4]->id,
                'estimated_hours' => 60,
                'created_by_id' => $admin->id,
            ]
        );
    }
}
