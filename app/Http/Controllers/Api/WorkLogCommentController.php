<?php

namespace App\Http\Controllers\Api;

use App\Models\ActivityLog;
use App\Models\WorkLog;
use App\Models\WorkLogComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WorkLogCommentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * @OA\Get(
     *     path="/api/work-logs/{workLog}/comments",
     *     operationId="workLogCommentsIndex",
     *     tags={"Work Log Comments"},
     *     summary="List comments on a work log",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="workLog", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Array of comments",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/WorkLogComment"))
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")),
     *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ForbiddenError")),
     *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundError"))
     * )
     */
    public function index(Request $request, WorkLog $workLog): JsonResponse
    {
        $user = $request->user();

        if ($user->isEmployee() && $workLog->employee_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        } elseif ($user->isManager() && $workLog->task->project->assigned_manager_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comments = $workLog->comments()
            ->with('user')
            ->orderBy('created_at')
            ->get();

        return response()->json($comments);
    }

    /**
     * @OA\Post(
     *     path="/api/work-logs/{workLog}/comments",
     *     operationId="workLogCommentsStore",
     *     tags={"Work Log Comments"},
     *     summary="Add a comment to a work log (admin or managing manager)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="workLog", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"comment"},
     *             @OA\Property(property="comment", type="string", maxLength=1000, example="Please add screenshots for QA.")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/WorkLogComment")),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")),
     *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ForbiddenError")),
     *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundError")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function store(Request $request, WorkLog $workLog): JsonResponse
    {
        $user = $request->user();

        if ($user->isEmployee()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->isManager() && $workLog->task->project->assigned_manager_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        $comment = $workLog->comments()->create([
            'user_id' => $user->id,
            'comment' => $validated['comment'],
        ]);

        ActivityLog::log('work_log_comment_added', 'WorkLogComment', $comment->id, null, $comment->toArray(), $user->id);

        return response()->json($comment->load('user'), 201);
    }

    /**
     * @OA\Put(
     *     path="/api/work-logs/{workLog}/comments/{comment}",
     *     operationId="workLogCommentsUpdate",
     *     tags={"Work Log Comments"},
     *     summary="Update a comment (author only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="workLog", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="comment", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"comment"},
     *             @OA\Property(property="comment", type="string", maxLength=1000)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/WorkLogComment")),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")),
     *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ForbiddenError")),
     *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundError")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function update(Request $request, WorkLog $workLog, WorkLogComment $comment): JsonResponse
    {
        $user = $request->user();

        if ($comment->work_log_id !== $workLog->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if ($comment->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        $previousValues = $comment->toArray();
        $comment->update($validated);

        ActivityLog::log('work_log_comment_updated', 'WorkLogComment', $comment->id, $previousValues, $validated, $user->id);

        return response()->json($comment->load('user'));
    }

    /**
     * @OA\Delete(
     *     path="/api/work-logs/{workLog}/comments/{comment}",
     *     operationId="workLogCommentsDestroy",
     *     tags={"Work Log Comments"},
     *     summary="Delete a comment (author only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="workLog", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="comment", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Deleted", @OA\JsonContent(ref="#/components/schemas/SuccessMessage")),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")),
     *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ForbiddenError")),
     *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundError"))
     * )
     */
    public function destroy(Request $request, WorkLog $workLog, WorkLogComment $comment): JsonResponse
    {
        $user = $request->user();

        if ($comment->work_log_id !== $workLog->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if ($comment->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        ActivityLog::log('work_log_comment_deleted', 'WorkLogComment', $comment->id, $comment->toArray(), null, $user->id);

        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully']);
    }
}
