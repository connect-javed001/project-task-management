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

    /**
     * @OA\Get(
     *     path="/api/tasks",
     *     operationId="tasksIndex",
     *     tags={"Tasks"},
     *     summary="List tasks (role-scoped)",
     *     description="Admins see all tasks; managers see tasks in projects assigned to them; employees see only tasks assigned to them.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="project_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string", enum={"todo","in_progress","in_review","completed","blocked"})),
     *     @OA\Parameter(name="priority", in="query", required=false, @OA\Schema(type="string", enum={"low","medium","high","critical"})),
     *     @OA\Parameter(name="assigned_to", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="deadline_from", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="deadline_to", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", default=1)),
     *     @OA\Response(
     *         response=200,
     *         description="Paginated list of tasks",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/Pagination"),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="array",
     *                         @OA\Items(ref="#/components/schemas/Task")
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthorizedError"))
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Task::query();

        if ($user->isEmployee()) {
            $query->where('assigned_to_id', $user->id);
        } elseif ($user->isManager()) {
            $query->whereHas('project', function ($q) use ($user) {
                $q->where('assigned_manager_id', $user->id);
            });
        }

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

    /**
     * @OA\Get(
     *     path="/api/tasks/{task}",
     *     operationId="tasksShow",
     *     tags={"Tasks"},
     *     summary="Get a task",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="task", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Task", @OA\JsonContent(ref="#/components/schemas/Task")),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")),
     *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ForbiddenError")),
     *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundError"))
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/tasks",
     *     operationId="tasksStore",
     *     tags={"Tasks"},
     *     summary="Create a task (admin or assigned manager)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"project_id","name","priority","status","deadline"},
     *             @OA\Property(property="project_id", type="integer", example=10),
     *             @OA\Property(property="name", type="string", example="Build login page"),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="priority", type="string", enum={"low","medium","high","critical"}),
     *             @OA\Property(property="status", type="string", enum={"todo","in_progress","in_review","completed","blocked"}),
     *             @OA\Property(property="deadline", type="string", format="date-time", example="2026-08-15T17:00:00Z"),
     *             @OA\Property(property="assigned_to_id", type="integer", nullable=true),
     *             @OA\Property(property="estimated_hours", type="number", format="float", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/Task")),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")),
     *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ForbiddenError")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/api/tasks/{task}",
     *     operationId="tasksUpdate",
     *     tags={"Tasks"},
     *     summary="Update a task (admin or assigned manager)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="task", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="priority", type="string", enum={"low","medium","high","critical"}),
     *             @OA\Property(property="status", type="string", enum={"todo","in_progress","in_review","completed","blocked"}),
     *             @OA\Property(property="deadline", type="string", format="date-time"),
     *             @OA\Property(property="assigned_to_id", type="integer", nullable=true),
     *             @OA\Property(property="estimated_hours", type="number", format="float", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/Task")),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")),
     *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ForbiddenError")),
     *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundError")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
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

    /**
     * @OA\Patch(
     *     path="/api/tasks/{task}/status",
     *     operationId="tasksUpdateStatus",
     *     tags={"Tasks"},
     *     summary="Update only the status of a task",
     *     description="Assigned employees, the project manager, or an admin may update task status.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="task", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"todo","in_progress","in_review","completed","blocked"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Status updated", @OA\JsonContent(ref="#/components/schemas/Task")),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")),
     *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ForbiddenError")),
     *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundError")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function updateStatus(Request $request, Task $task): JsonResponse
    {
        $user = $request->user();

        if ($user->isEmployee() && $task->assigned_to_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

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

    /**
     * @OA\Delete(
     *     path="/api/tasks/{task}",
     *     operationId="tasksDestroy",
     *     tags={"Tasks"},
     *     summary="Delete a task (admin or assigned manager)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="task", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Deleted", @OA\JsonContent(ref="#/components/schemas/SuccessMessage")),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")),
     *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ForbiddenError")),
     *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundError"))
     * )
     */
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
