<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WorkLogCommentController;
use App\Http\Controllers\Api\WorkLogController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Health check
Route::get('/health', fn() => response()->json(['status' => 'ok']));

// Public authentication routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

// Authentication required routes
Route::middleware('auth:sanctum')->group(function () {
    // Authentication
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // User profile
    Route::get('/user', fn(Request $request) => response()->json($request->user()));

    // Users (admin manages; others can read for picker)
    Route::apiResource('users', UserController::class);

    // Projects
    Route::apiResource('projects', ProjectController::class);

    // Tasks
    Route::apiResource('tasks', TaskController::class);
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus']);

    // Work Logs
    Route::get('/tasks/{task}/work-logs', [WorkLogController::class, 'index']);
    Route::post('/tasks/{task}/work-logs', [WorkLogController::class, 'store']);
    Route::get('/tasks/{task}/work-logs/{workLog}', [WorkLogController::class, 'show']);
    Route::put('/tasks/{task}/work-logs/{workLog}', [WorkLogController::class, 'update']);
    Route::delete('/tasks/{task}/work-logs/{workLog}', [WorkLogController::class, 'destroy']);

    // Work Log Comments
    Route::get('/work-logs/{workLog}/comments', [WorkLogCommentController::class, 'index']);
    Route::post('/work-logs/{workLog}/comments', [WorkLogCommentController::class, 'store']);
    Route::put('/work-logs/{workLog}/comments/{comment}', [WorkLogCommentController::class, 'update']);
    Route::delete('/work-logs/{workLog}/comments/{comment}', [WorkLogCommentController::class, 'destroy']);

    // Activity Logs
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);
    Route::get('/activity-logs/{activityLog}', [ActivityLogController::class, 'show']);

    // Reports
    Route::get('/reports/projects/{project}', [ReportController::class, 'projectReport']);
    Route::get('/reports/employees/{employee}', [ReportController::class, 'employeeReport']);
    Route::get('/dashboard/stats', [ReportController::class, 'dashboardStats']);
});
