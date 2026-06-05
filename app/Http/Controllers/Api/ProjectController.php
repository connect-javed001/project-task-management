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

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $query = Project::query();

        // Filter by role
        if ($user->isEmployee()) {
            // Employees cannot view projects
            return response()->json(['message' => 'Unauthorized'], 403);
        } elseif ($user->isManager()) {
            // Managers only see their assigned projects
            $query->where('assigned_manager_id', $user->id);
        }
        // Admins see all projects

        // Apply filters
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

        // Only admins may (re)assign a project's manager.
        if ($user->isAdmin()) {
            $rules['assigned_manager_id'] = 'nullable|exists:users,id';
        }

        $validated = $request->validate($rules);

        $previousValues = $project->toArray();
        $project->update($validated);

        ActivityLog::log('updated', 'Project', $project->id, $previousValues, $validated, $request->user()->id);

        return response()->json($project->load(['assignedManager', 'creator']));
    }

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
