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

    public function index(Request $request, WorkLog $workLog): JsonResponse
    {
        $user = $request->user();

        // Verify access to work log
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

    public function store(Request $request, WorkLog $workLog): JsonResponse
    {
        $user = $request->user();

        // Only managers/admins can comment on work logs
        if ($user->isEmployee()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Managers can only comment on work logs in their projects
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

    public function update(Request $request, WorkLog $workLog, WorkLogComment $comment): JsonResponse
    {
        $user = $request->user();

        if ($comment->work_log_id !== $workLog->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        // Only the comment author can update
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
