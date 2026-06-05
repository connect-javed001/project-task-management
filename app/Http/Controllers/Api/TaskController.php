<?php

namespace App\Http\Controllers\Api;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TaskController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $query = Task::query();

        // Filter by role
        if ($user->isEmployee()) {
            // Employees only see tasks assigned to them
            $query->where('assigned_to_id', $user->id);
        } elseif ($user->isManager()) {
            // Managers see tasks in their assigned projects
            $query->whereHas('project', function ($q) use ($user) {
                $q->where('assigned_manager_id', $user->id);
            });
        }
        // Admins see all tasks

        // Apply filters
        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->has('assigned_to')) {
            $query->where('assigned_to_id', $request->assigned_to);
        }
        if ($request->has('deadline_from')) {
            $query->whereDate('deadline', '>=', $request->deadline_from);
        }
        if ($request->has('deadline_to')) {
            $query->whereDate('deadline', '<=', $request->deadline_to);
        }

        $tasks = $query->with(['project', 'assignedTo', 'creator', 'workLogs'])
            ->orderBy('deadline')
            ->paginate($request->get('per_page', 15));

        return response()->json($tasks);
    }

    public function show(Request $request, Task $task): JsonResponse
    {
        $user = $request->user();

        if ($user->isEmployee() && $task->assigned_to_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($user->isManager()
            && (!$task->project || $task->project->assigned_manager_id !== $user->id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($task->load(['project', 'assignedTo', 'creator', 'workLogs', 'workLogs.comments']));
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isAdmin() && !$user->isManager()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,critical',
            'status' => 'required|in:todo,in_progress,in_review,completed,blocked',
            'deadline' => 'required|date',
            'assigned_to_id' => 'nullable|exists:users,id',
            'estimated_hours' => 'nullable|numeric|min:0',
        ]);

        // Managers may only create tasks inside projects assigned to them.
        if ($user->isManager()) {
            $project = Project::find($validated['project_id']);
            if (!$project || $project->assigned_manager_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $validated['created_by_id'] = $user->id;

        $task = Task::create($validated);

        ActivityLog::log('created', 'Task', $task->id, null, $task->toArray(), $user->id);

        return response()->json($task->load(['project', 'assignedTo', 'creator']), 201);
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        $user = $request->user();

        if (!$user->isAdmin()
            && !($user->isManager()
                && $task->project
                && $task->project->assigned_manager_id === $user->id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'sometimes|in:low,medium,high,critical',
            'status' => 'sometimes|in:todo,in_progress,in_review,completed,blocked',
            'deadline' => 'sometimes|date',
            'assigned_to_id' => 'nullable|exists:users,id',
            'estimated_hours' => 'nullable|numeric|min:0',
        ]);

        $previousValues = $task->toArray();
        $task->update($validated);

        ActivityLog::log('updated', 'Task', $task->id, $previousValues, $validated, $request->user()->id);

        return response()->json($task->load(['project', 'assignedTo', 'creator']));
    }

    public function updateStatus(Request $request, Task $task): JsonResponse
    {
        $user = $request->user();

        // Employees can only update status if task is assigned to them.
        if ($user->isEmployee() && $task->assigned_to_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Managers can only update tasks in their assigned projects.
        if ($user->isManager()
            && (!$task->project || $task->project->assigned_manager_id !== $user->id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:todo,in_progress,in_review,completed,blocked',
        ]);

        $previousStatus = $task->status;
        $task->update($validated);

        ActivityLog::log('status_changed', 'Task', $task->id, ['status' => $previousStatus], $validated, $user->id);

        return response()->json($task);
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        $user = $request->user();

        if (!$user->isAdmin()
            && !($user->isManager()
                && $task->project
                && $task->project->assigned_manager_id === $user->id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        ActivityLog::log('deleted', 'Task', $task->id, $task->toArray(), null, $request->user()->id);
        
        $task->delete();

        return response()->json(['message' => 'Task deleted successfully']);
    }
}
