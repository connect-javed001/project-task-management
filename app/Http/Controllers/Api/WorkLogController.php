<?php

namespace App\Http\Controllers\Api;

use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\WorkLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WorkLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request, Task $task): JsonResponse
    {
        $user = $request->user();

        // Verify task access
        if ($user->isEmployee() && $task->assigned_to_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        } elseif ($user->isManager() && $task->project->assigned_manager_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $workLogs = $task->workLogs()
            ->with(['employee', 'comments'])
            ->paginate($request->get('per_page', 15));

        return response()->json($workLogs);
    }

    public function show(Request $request, Task $task, WorkLog $workLog): JsonResponse
    {
        $user = $request->user();

        if ($workLog->task_id !== $task->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if ($user->isEmployee() && $workLog->employee_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->isManager()
            && (!$task->project || $task->project->assigned_manager_id !== $user->id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($workLog->load(['employee', 'task', 'comments']));
    }

    public function store(Request $request, Task $task): JsonResponse
    {
        $user = $request->user();

        // Only employees can submit work logs, and only for their own tasks
        if (!$user->isEmployee()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($task->assigned_to_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'description' => 'required|string',
            'hours_worked' => 'required|numeric|min:0.5|max:24',
            'attachment' => 'nullable|file|max:5120', // 5MB max
        ]);

        $attachment_path = null;
        if ($request->hasFile('attachment')) {
            $attachment_path = $request->file('attachment')->store('work-logs', 'private');
        }

        $workLog = $task->workLogs()->create([
            'employee_id' => $user->id,
            'description' => $validated['description'],
            'hours_worked' => $validated['hours_worked'],
            'attachment_path' => $attachment_path,
        ]);

        ActivityLog::log('work_log_submitted', 'WorkLog', $workLog->id, null, $workLog->toArray(), $user->id);

        return response()->json($workLog->load(['employee', 'comments']), 201);
    }

    public function update(Request $request, Task $task, WorkLog $workLog): JsonResponse
    {
        $user = $request->user();

        if ($workLog->task_id !== $task->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        // Only the original employee can update their own logs
        if ($workLog->employee_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'description' => 'sometimes|string',
            'hours_worked' => 'sometimes|numeric|min:0.5|max:24',
        ]);

        $previousValues = $workLog->toArray();
        $workLog->update($validated);

        ActivityLog::log('work_log_updated', 'WorkLog', $workLog->id, $previousValues, $validated, $user->id);

        return response()->json($workLog->load(['employee', 'comments']));
    }

    public function destroy(Request $request, Task $task, WorkLog $workLog): JsonResponse
    {
        $user = $request->user();

        if ($workLog->task_id !== $task->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if ($workLog->employee_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        ActivityLog::log('work_log_deleted', 'WorkLog', $workLog->id, $workLog->toArray(), null, $user->id);

        $workLog->delete();

        return response()->json(['message' => 'Work log deleted successfully']);
    }
}
