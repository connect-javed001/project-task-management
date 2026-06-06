<?php

namespace App\Http\Controllers\Api;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * @OA\Get(
     *     path="/api/users",
     *     operationId="usersIndex",
     *     tags={"Users"},
     *     summary="List users",
     *     description="Admins receive a paginated list; non-admins receive a lean active-users picker payload (up to 100).",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="role", in="query", required=false, @OA\Schema(type="string", enum={"admin","manager","employee"})),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string", enum={"active","inactive"})),
     *     @OA\Parameter(name="search", in="query", required=false, description="Search by name or email", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=20)),
     *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", default=1)),
     *     @OA\Response(
     *         response=200,
     *         description="Users list (paginated for admins, flat array for others)",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     allOf={
     *                         @OA\Schema(ref="#/components/schemas/Pagination"),
     *                         @OA\Schema(
     *                             @OA\Property(
     *                                 property="data",
     *                                 type="array",
     *                                 @OA\Items(ref="#/components/schemas/User")
     *                             )
     *                         )
     *                     }
     *                 ),
     *                 @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/User"))
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthorizedError"))
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = User::query();

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)->orWhere('email', 'like', $term);
            });
        }

        if (!$user->isAdmin()) {
            $query->select(['id', 'name', 'email', 'role', 'status'])
                ->where('status', 'active');
            return response()->json($query->orderBy('name')->limit(100)->get());
        }

        $users = $query->orderBy('name')->paginate($request->get('per_page', 20));

        return response()->json($users);
    }

    /**
     * @OA\Get(
     *     path="/api/users/{user}",
     *     operationId="usersShow",
     *     tags={"Users"},
     *     summary="Get a user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="User", @OA\JsonContent(ref="#/components/schemas/User")),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")),
     *     @OA\Response(response=403, description="Forbidden (non-admin requesting another user)", @OA\JsonContent(ref="#/components/schemas/ForbiddenError")),
     *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundError"))
     * )
     */
    public function show(Request $request, User $user): JsonResponse
    {
        if (!$request->user()->isAdmin() && $request->user()->id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($user);
    }

    /**
     * @OA\Post(
     *     path="/api/users",
     *     operationId="usersStore",
     *     tags={"Users"},
     *     summary="Create a user (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","role"},
     *             @OA\Property(property="name", type="string", example="Jane Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="jane@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="secret123"),
     *             @OA\Property(property="role", type="string", enum={"admin","manager","employee"}, example="employee"),
     *             @OA\Property(property="status", type="string", enum={"active","inactive"}, example="active")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/User")),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")),
     *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ForbiddenError")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function store(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in(['admin', 'manager', 'employee'])],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $created = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'status' => $validated['status'] ?? 'active',
        ]);

        ActivityLog::log('created', 'User', $created->id, null, $created->toArray(), $request->user()->id);

        return response()->json($created, 201);
    }

    /**
     * @OA\Put(
     *     path="/api/users/{user}",
     *     operationId="usersUpdate",
     *     tags={"Users"},
     *     summary="Update a user (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password", nullable=true, description="Leave null/empty to keep current password"),
     *             @OA\Property(property="role", type="string", enum={"admin","manager","employee"}),
     *             @OA\Property(property="status", type="string", enum={"active","inactive"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/User")),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")),
     *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ForbiddenError")),
     *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundError")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function update(Request $request, User $user): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'sometimes|nullable|string|min:8',
            'role' => ['sometimes', Rule::in(['admin', 'manager', 'employee'])],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ]);

        if (array_key_exists('password', $validated)) {
            if ($validated['password']) {
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']);
            }
        }

        $previous = $user->toArray();
        $user->update($validated);

        ActivityLog::log('updated', 'User', $user->id, $previous, $validated, $request->user()->id);

        return response()->json($user);
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{user}",
     *     operationId="usersDestroy",
     *     tags={"Users"},
     *     summary="Delete a user (admin only; cannot delete self)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Deleted", @OA\JsonContent(ref="#/components/schemas/SuccessMessage")),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")),
     *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ForbiddenError")),
     *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundError")),
     *     @OA\Response(response=422, description="Cannot delete own account", @OA\JsonContent(ref="#/components/schemas/SuccessMessage"))
     * )
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }

        ActivityLog::log('deleted', 'User', $user->id, $user->toArray(), null, $request->user()->id);

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
