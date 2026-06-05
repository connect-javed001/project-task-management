<?php

namespace App\Http\Controllers\Api;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function projectReport(Request $request, Project $project): JsonResponse
    {
        $user = $request->user();

        if ($user->isManager() && $project->assigned_manager_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        } elseif ($user->isEmployee()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $tasks = $project->tasks()->get();
        $completedTasks = $tasks->where('status', 'completed')->count();
        $totalTasks = $tasks->count();
        $completionPercentage = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;

        return response()->json([
            'project_id' => $project->id,
            'project_name' => $project->name,
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'pending_tasks' => $totalTasks - $completedTasks,
            'completion_percentage' => round($completionPercentage, 2),
            'overdue_tasks' => $tasks->filter(fn($t) => $t->isOverdue())->count(),
            'tasks' => $tasks->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'status' => $t->status,
                'priority' => $t->priority,
                'deadline' => $t->deadline,
                'assigned_to' => $t->assignedTo?->name,
                'hours_logged' => $t->getTotalHoursLogged(),
            ]),
        ]);
    }

    public function employeeReport(Request $request, User $employee): JsonResponse
    {
        $user = $request->user();

        if ($user->isEmployee() && $employee->id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        } elseif ($user->isManager()) {
            // Managers can only see reports for employees in their projects
            $hasAccess = $employee->assignedTasks()
                ->whereHas('project', fn($q) => $q->where('assigned_manager_id', $user->id))
                ->exists();
            
            if (!$hasAccess) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $tasks = $employee->assignedTasks()->get();
        $completedTasks = $tasks->where('status', 'completed')->count();
        $totalTasks = $tasks->count();

        $workLogs = $employee->workLogs()->get();
        $totalHoursLogged = $workLogs->sum('hours_worked');

        $completedTasksWithHours = $tasks->where('status', 'completed');
        $avgCompletionTime = $completedTasksWithHours->count() > 0 
            ? $completedTasksWithHours->avg(fn($t) => $t->updated_at->diffInDays($t->created_at))
            : 0;

        return response()->json([
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'total_assigned_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'pending_tasks' => $totalTasks - $completedTasks,
            'total_hours_logged' => round($totalHoursLogged, 2),
            'avg_completion_time_days' => round($avgCompletionTime, 2),
            'work_logs_submitted' => $workLogs->count(),
        ]);
    }

    public function dashboardStats(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return $this->adminDashboard();
        } elseif ($user->isManager()) {
            return $this->managerDashboard($user);
        } else {
            return $this->employeeDashboard($user);
        }
    }

    private function adminDashboard(): JsonResponse
    {
        return response()->json([
            'total_projects' => Project::count(),
            'total_tasks' => Task::count(),
            'active_employees' => User::where('role', 'employee')->where('status', 'active')->count(),
            'overdue_tasks' => Task::where('deadline', '<', now())->where('status', '!=', 'completed')->count(),
            'completed_tasks' => Task::where('status', 'completed')->count(),
            'total_hours_logged' => WorkLog::sum('hours_worked'),
            'project_completion_avg' => Project::all()->avg(fn($p) => $p->getCompletionPercentage()),
        ]);
    }

    private function managerDashboard(User $manager): JsonResponse
    {
        $projects = $manager->projects()->get();
        $tasks = Task::whereHas('project', fn($q) => $q->where('assigned_manager_id', $manager->id))->get();

        return response()->json([
            'managed_projects' => $projects->count(),
            'active_tasks' => $tasks->where('status', '!=', 'completed')->count(),
            'upcoming_deadlines' => $tasks->filter(fn($t) => $t->isOverdueOrUpcoming())->count(),
            'overdue_tasks' => $tasks->filter(fn($t) => $t->isOverdue())->count(),
            'completed_tasks' => $tasks->where('status', 'completed')->count(),
            'employee_productivity' => $tasks->groupBy('assigned_to_id')->map(fn($g) => [
                'employee_id' => $g->first()->assigned_to_id,
                'employee_name' => $g->first()->assignedTo?->name,
                'assigned_tasks' => $g->count(),
                'completed_tasks' => $g->where('status', 'completed')->count(),
            ])->values(),
        ]);
    }

    private function employeeDashboard(User $employee): JsonResponse
    {
        $tasks = $employee->assignedTasks()->get();

        return response()->json([
            'assigned_tasks' => $tasks->count(),
            'tasks_in_progress' => $tasks->where('status', 'in_progress')->count(),
            'completed_tasks' => $tasks->where('status', 'completed')->count(),
            'tasks_due_soon' => $tasks->filter(fn($t) => $t->isOverdueOrUpcoming())->count(),
            'overdue_tasks' => $tasks->filter(fn($t) => $t->isOverdue())->count(),
            'recent_work_logs' => $employee->workLogs()->latest()->take(5)->get(),
        ]);
    }
}
