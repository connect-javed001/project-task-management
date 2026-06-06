<?php

namespace App\Http\Controllers\Api;

use App\Models\ActivityLog;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * @OA\Get(
     *     path="/api/projects",
     *     operationId="projectsIndex",
     *     tags={"Projects"},
     *     summary="List projects (role-scoped)",
     *     description="Admins see all projects; managers see only the ones assigned to them; employees are forbidden.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string", enum={"planning","active","completed","archived"})),
     *     @OA\Parameter(name="manager_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="date_from", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_to", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", default=1)),
     *     @OA\Response(
     *         response=200,
     *         description="Paginated list of projects",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/Pagination"),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="array",
     *                         @OA\Items(ref="#/components/schemas/Project")
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")),
     *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ForbiddenError"))
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Project::query();

        if ($user->isEmployee()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        } elseif ($user->isManager()) {
            $query->where('assigned_manager_id', $user->id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('manager_id')) {
            $query->where('assigned_manager_id', $request->manager_id);
        }
        if ($request->has('date_from')) {
            $query->whereDate('start_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('end_date', '<=', $request->date_to);
        }

        $projects = $query->with(['assignedManager', 'creator', 'tasks'])
            ->paginate($request->get('per_page', 15));

        return response()->json($projects);
    }

    /**
     * @OA\Get(
     *     path="/api/projects/{project}",
     *     operationId="projectsShow",
     *     tags={"Projects"},
     *     summary="Get a project",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="project", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Project", @OA\JsonContent(ref="#/components/schemas/Project")),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")),
     *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ForbiddenError")),
     *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundError"))
     * )
     */
    public function show(Request $request, Project $project): JsonResponse
    {
        $user = $request->user();

        if ($user->isEmployee()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($user->isManager() && $project->assigned_manager_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($project->load(['assignedManager', 'creator', 'tasks']));
    }

    /**
     * @OA\Post(
     *     path="/api/projects",
     *     operationId="projectsStore",
     *     tags={"Projects"},
     *     summary="Create a project (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","start_date","end_date","status"},
     *             @OA\Property(property="name", type="string", example="Website Redesign"),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="start_date", type="string", format="date", example="2026-06-01"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2026-09-30"),
     *             @OA\Property(property="status", type="string", enum={"planning","active","completed","archived"}),
     *             @OA\Property(property="assigned_manager_id", type="integer", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/Project")),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")),
     *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ForbiddenError")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:planning,active,completed,archived',
            'assigned_manager_id' => 'nullable|exists:users,id',
        ]);

        $validated['created_by_id'] = $user->id;

        $project = Project::create($validated);

        ActivityLog::log('created', 'Project', $project->id, null, $project->toArray(), $user->id);

        return response()->json($project->load(['assignedManager', 'creator']), 201);
    }

    /**
     * @OA\Put(
     *     path="/api/projects/{project}",
     *     operationId="projectsUpdate",
     *     tags={"Projects"},
     *     summary="Update a project (admin, or assigned manager)",
     *     description="Only admins may (re)assign `assigned_manager_id`.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="project", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="start_date", type="string", format="date"),
     *             @OA\Property(property="end_date", type="string", format="date"),
     *             @OA\Property(property="status", type="string", enum={"planning","active","completed","archived"}),
     *             @OA\Property(property="assigned_manager_id", type="integer", nullable=true, description="Admins only")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/Project")),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")),
     *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ForbiddenError")),
     *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundError")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function update(Request $request, Project $project): JsonResponse
    {
        $user = $request->user();

        if (!$user->isAdmin()
            && !($user->isManager() && $project->assigned_manager_id === $user->id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $rules = [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date',
            'status' => 'sometimes|in:planning,active,completed,archived',
        ];

        if ($user->isAdmin()) {
            $rules['assigned_manager_id'] = 'nullable|exists:users,id';
        }

        $validated = $request->validate($rules);

        $previousValues = $project->toArray();
        $project->update($validated);

        ActivityLog::log('updated', 'Project', $project->id, $previousValues, $validated, $request->user()->id);

        return response()->json($project->load(['assignedManager', 'creator']));
    }

    /**
     * @OA\Delete(
     *     path="/api/projects/{project}",
     *     operationId="projectsDestroy",
     *     tags={"Projects"},
     *     summary="Delete a project (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="project", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Deleted", @OA\JsonContent(ref="#/components/schemas/SuccessMessage")),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")),
     *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ForbiddenError")),
     *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundError"))
     * )
     */
    public function destroy(Request $request, Project $project): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        ActivityLog::log('deleted', 'Project', $project->id, $project->toArray(), null, $request->user()->id);

        $project->delete();

        return response()->json(['message' => 'Project deleted successfully']);
    }
}
