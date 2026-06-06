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

    /**
     * @OA\Get(
     *     path="/api/tasks/{task}/work-logs",
     *     operationId="workLogsIndex",
     *     tags={"Work Logs"},
     *     summary="List work logs for a task",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="task", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", default=1)),
     *     @OA\Response(
     *         response=200,
     *         description="Paginated list of work logs",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/Pagination"),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="array",
     *                         @OA\Items(ref="#/components/schemas/WorkLog")
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")),
     *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ForbiddenError")),
     *     @OA\Response(response=404, description="Task not found", @OA\JsonContent(ref="#/components/schemas/NotFoundError"))
     * )
     */
    public function index(Request $request, Task $task): JsonResponse
    {
        $user = $request->user();

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

    /**
     * @OA\Get(
     *     path="/api/tasks/{task}/work-logs/{workLog}",
     *     operationId="workLogsShow",
     *     tags={"Work Logs"},
     *     summary="Get a single work log",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="task", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="workLog", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Work log", @OA\JsonContent(ref="#/components/schemas/WorkLog")),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")),
     *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ForbiddenError")),
     *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundError"))
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/tasks/{task}/work-logs",
     *     operationId="workLogsStore",
     *     tags={"Work Logs"},
     *     summary="Submit a work log entry for a task (assigned employee only)",
     *     description="Accepts multipart/form-data so that an optional `attachment` file (max 5MB) can be uploaded.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="task", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"description","hours_worked"},
     *                 @OA\Property(property="description", type="string", example="Implemented login form and validation"),
     *                 @OA\Property(property="hours_worked", type="number", format="float", minimum=0.5, maximum=24, example=4.5),
     *                 @OA\Property(property="attachment", type="string", format="binary", description="Optional file upload (max 5MB)")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/WorkLog")),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")),
     *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ForbiddenError")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function store(Request $request, Task $task): JsonResponse
    {
        $user = $request->user();

        if (!$user->isEmployee()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($task->assigned_to_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'description' => 'required|string',
            'hours_worked' => 'required|numeric|min:0.5|max:24',
            'attachment' => 'nullable|file|max:5120',
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

    /**
     * @OA\Put(
     *     path="/api/tasks/{task}/work-logs/{workLog}",
     *     operationId="workLogsUpdate",
     *     tags={"Work Logs"},
     *     summary="Update a work log (author only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="task", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="workLog", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="hours_worked", type="number", format="float", minimum=0.5, maximum=24)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/WorkLog")),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")),
     *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ForbiddenError")),
     *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundError")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function update(Request $request, Task $task, WorkLog $workLog): JsonResponse
    {
        $user = $request->user();

        if ($workLog->task_id !== $task->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

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

    /**
     * @OA\Delete(
     *     path="/api/tasks/{task}/work-logs/{workLog}",
     *     operationId="workLogsDestroy",
     *     tags={"Work Logs"},
     *     summary="Delete a work log (author only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="task", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="workLog", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Deleted", @OA\JsonContent(ref="#/components/schemas/SuccessMessage")),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")),
     *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ForbiddenError")),
     *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundError"))
     * )
     */
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
