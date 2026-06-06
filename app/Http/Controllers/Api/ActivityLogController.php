<?php

namespace App\Http\Controllers\Api;

use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ActivityLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * @OA\Get(
     *     path="/api/activity-logs",
     *     operationId="activityLogsIndex",
     *     tags={"Activity Logs"},
     *     summary="List activity logs (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="user_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="action", in="query", required=false, @OA\Schema(type="string", example="created")),
     *     @OA\Parameter(name="entity_type", in="query", required=false, @OA\Schema(type="string", example="Task")),
     *     @OA\Parameter(name="date_from", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_to", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=50)),
     *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", default=1)),
     *     @OA\Response(
     *         response=200,
     *         description="Paginated list of activity logs",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/Pagination"),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="array",
     *                         @OA\Items(ref="#/components/schemas/ActivityLog")
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

        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = ActivityLog::query();

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->has('action')) {
            $query->where('action', $request->action);
        }
        if ($request->has('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));

        return response()->json($logs);
    }

    /**
     * @OA\Get(
     *     path="/api/activity-logs/{activityLog}",
     *     operationId="activityLogsShow",
     *     tags={"Activity Logs"},
     *     summary="Get a single activity log (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="activityLog", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Activity log", @OA\JsonContent(ref="#/components/schemas/ActivityLog")),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")),
     *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ForbiddenError")),
     *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundError"))
     * )
     */
    public function show(ActivityLog $activityLog): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($activityLog->load('user'));
    }
}
