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

    /**
     * @OA\Get(
     *     path="/api/reports/projects/{project}",
     *     operationId="reportsProject",
     *     tags={"Reports"},
     *     summary="Project report — task counts, completion %, overdue, per-task breakdown",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="project", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Project report",
     *         @OA\JsonContent(
     *             @OA\Property(property="project_id", type="integer", example=10),
     *             @OA\Property(property="project_name", type="string", example="Website Redesign"),
     *             @OA\Property(property="total_tasks", type="integer", example=24),
     *             @OA\Property(property="completed_tasks", type="integer", example=18),
     *             @OA\Property(property="pending_tasks", type="integer", example=6),
     *             @OA\Property(property="completion_percentage", type="number", format="float", example=75.0),
     *             @OA\Property(property="overdue_tasks", type="integer", example=2),
     *             @OA\Property(
     *                 property="tasks",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="status", type="string"),
     *                     @OA\Property(property="priority", type="string"),
     *                     @OA\Property(property="deadline", type="string", format="date-time"),
     *                     @OA\Property(property="assigned_to", type="string", nullable=true),
     *                     @OA\Property(property="hours_logged", type="number", format="float")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")),
     *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ForbiddenError")),
     *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundError"))
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/reports/employees/{employee}",
     *     operationId="reportsEmployee",
     *     tags={"Reports"},
     *     summary="Employee report — assigned tasks, hours logged, avg completion",
     *     description="Employees may view only their own report; managers only employees with tasks in their projects; admins may view any.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="employee", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Employee report",
     *         @OA\JsonContent(
     *             @OA\Property(property="employee_id", type="integer", example=5),
     *             @OA\Property(property="employee_name", type="string", example="Jane Doe"),
     *             @OA\Property(property="total_assigned_tasks", type="integer", example=12),
     *             @OA\Property(property="completed_tasks", type="integer", example=8),
     *             @OA\Property(property="pending_tasks", type="integer", example=4),
     *             @OA\Property(property="total_hours_logged", type="number", format="float", example=42.5),
     *             @OA\Property(property="avg_completion_time_days", type="number", format="float", example=3.4),
     *             @OA\Property(property="work_logs_submitted", type="integer", example=18)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")),
     *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ForbiddenError")),
     *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundError"))
     * )
     */
    public function employeeReport(Request $request, User $employee): JsonResponse
    {
        $user = $request->user();

        if ($user->isEmployee() && $employee->id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        } elseif ($user->isManager()) {
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

    /**
     * @OA\Get(
     *     path="/api/dashboard/stats",
     *     operationId="dashboardStats",
     *     tags={"Reports"},
     *     summary="Dashboard statistics for the authenticated user (shape varies by role)",
     *     description="Returns a different payload depending on the caller's role: admin, manager, or employee.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Role-specific dashboard payload",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     description="Admin dashboard",
     *                     @OA\Property(property="total_projects", type="integer"),
     *                     @OA\Property(property="total_tasks", type="integer"),
     *                     @OA\Property(property="active_employees", type="integer"),
     *                     @OA\Property(property="overdue_tasks", type="integer"),
     *                     @OA\Property(property="completed_tasks", type="integer"),
     *                     @OA\Property(property="total_hours_logged", type="number", format="float"),
     *                     @OA\Property(property="project_completion_avg", type="number", format="float")
     *                 ),
     *                 @OA\Schema(
     *                     description="Manager dashboard",
     *                     @OA\Property(property="managed_projects", type="integer"),
     *                     @OA\Property(property="active_tasks", type="integer"),
     *                     @OA\Property(property="upcoming_deadlines", type="integer"),
     *                     @OA\Property(property="overdue_tasks", type="integer"),
     *                     @OA\Property(property="completed_tasks", type="integer"),
     *                     @OA\Property(
     *                         property="employee_productivity",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="employee_id", type="integer", nullable=true),
     *                             @OA\Property(property="employee_name", type="string", nullable=true),
     *                             @OA\Property(property="assigned_tasks", type="integer"),
     *                             @OA\Property(property="completed_tasks", type="integer")
     *                         )
     *                     )
     *                 ),
     *                 @OA\Schema(
     *                     description="Employee dashboard",
     *                     @OA\Property(property="assigned_tasks", type="integer"),
     *                     @OA\Property(property="tasks_in_progress", type="integer"),
     *                     @OA\Property(property="completed_tasks", type="integer"),
     *                     @OA\Property(property="tasks_due_soon", type="integer"),
     *                     @OA\Property(property="overdue_tasks", type="integer"),
     *                     @OA\Property(
     *                         property="recent_work_logs",
     *                         type="array",
     *                         @OA\Items(ref="#/components/schemas/WorkLog")
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthorizedError"))
     * )
     */
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
