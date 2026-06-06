<?php

namespace App\Http\Controllers\Api;

/**
 * @OA\Info(
 *     title="Task Management API",
 *     version="1.0.0",
 *     description="API documentation for the Task Management project. Authenticate via /api/auth/login and pass the returned token as `Authorization: Bearer <token>` on protected endpoints."
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Sanctum-Token"
 * )
 *
 * @OA\Tag(name="Health", description="Service health")
 * @OA\Tag(name="Auth", description="Authentication and session management")
 * @OA\Tag(name="Users", description="User management")
 * @OA\Tag(name="Projects", description="Project management")
 * @OA\Tag(name="Tasks", description="Task management")
 * @OA\Tag(name="Work Logs", description="Work log entries for tasks")
 * @OA\Tag(name="Work Log Comments", description="Comments on work logs")
 * @OA\Tag(name="Activity Logs", description="Audit trail (admin only)")
 * @OA\Tag(name="Reports", description="Reports and dashboard statistics")
 *
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Jane Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="jane@example.com"),
 *     @OA\Property(property="role", type="string", enum={"admin","manager","employee"}, example="employee"),
 *     @OA\Property(property="status", type="string", enum={"active","inactive"}, example="active"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Project",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=10),
 *     @OA\Property(property="name", type="string", example="Website Redesign"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="start_date", type="string", format="date"),
 *     @OA\Property(property="end_date", type="string", format="date"),
 *     @OA\Property(property="status", type="string", enum={"planning","active","completed","archived"}),
 *     @OA\Property(property="assigned_manager_id", type="integer", nullable=true),
 *     @OA\Property(property="created_by_id", type="integer"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Task",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=100),
 *     @OA\Property(property="project_id", type="integer", example=10),
 *     @OA\Property(property="name", type="string", example="Build login page"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="priority", type="string", enum={"low","medium","high","critical"}),
 *     @OA\Property(property="status", type="string", enum={"todo","in_progress","in_review","completed","blocked"}),
 *     @OA\Property(property="deadline", type="string", format="date-time"),
 *     @OA\Property(property="assigned_to_id", type="integer", nullable=true),
 *     @OA\Property(property="created_by_id", type="integer"),
 *     @OA\Property(property="estimated_hours", type="number", format="float", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="WorkLog",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=200),
 *     @OA\Property(property="task_id", type="integer", example=100),
 *     @OA\Property(property="employee_id", type="integer", example=5),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="hours_worked", type="number", format="float", example=4.5),
 *     @OA\Property(property="attachment_path", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="WorkLogComment",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=300),
 *     @OA\Property(property="work_log_id", type="integer", example=200),
 *     @OA\Property(property="user_id", type="integer", example=2),
 *     @OA\Property(property="comment", type="string"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="ActivityLog",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=400),
 *     @OA\Property(property="user_id", type="integer", nullable=true),
 *     @OA\Property(property="action", type="string", example="created"),
 *     @OA\Property(property="entity_type", type="string", example="Task"),
 *     @OA\Property(property="entity_id", type="integer", example=100),
 *     @OA\Property(property="previous_values", type="object", nullable=true),
 *     @OA\Property(property="new_values", type="object", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Pagination",
 *     type="object",
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="last_page", type="integer", example=5),
 *     @OA\Property(property="per_page", type="integer", example=15),
 *     @OA\Property(property="total", type="integer", example=72),
 *     @OA\Property(property="from", type="integer", nullable=true, example=1),
 *     @OA\Property(property="to", type="integer", nullable=true, example=15)
 * )
 *
 * @OA\Schema(
 *     schema="ValidationError",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="The given data was invalid."),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         example={"email": {"The email field is required."}}
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="UnauthorizedError",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="Unauthenticated.")
 * )
 *
 * @OA\Schema(
 *     schema="ForbiddenError",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="Unauthorized")
 * )
 *
 * @OA\Schema(
 *     schema="NotFoundError",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="Not found")
 * )
 *
 * @OA\Schema(
 *     schema="SuccessMessage",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="Operation successful")
 * )
 *
 * @OA\Get(
 *     path="/api/health",
 *     operationId="health",
 *     tags={"Health"},
 *     summary="Service health check",
 *     @OA\Response(
 *         response=200,
 *         description="Service is up",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="ok")
 *         )
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/user",
 *     operationId="getAuthenticatedUserSimple",
 *     tags={"Auth"},
 *     summary="Get the currently authenticated user (Sanctum default route)",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Authenticated user", @OA\JsonContent(ref="#/components/schemas/User")),
 *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthorizedError"))
 * )
 */
class OpenApi {}
