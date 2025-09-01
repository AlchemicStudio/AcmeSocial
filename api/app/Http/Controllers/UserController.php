<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AssignPermissionsRequest;
use App\Http\Requests\RemovePermissionsRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\SyncPermissionsRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    /**
     * Display a listing of users (admin only).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = User::query();

        // Add search functionality
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        // Filter by admin status
        if ($request->has('is_admin')) {
            $query->where('is_admin', $request->get('is_admin'));
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);

        return UserResource::collection($users);
    }

    /**
     * Store a newly created user (admin only).
     */
    public function store(StoreUserRequest $request): UserResource
    {
        $user = User::create($request->validated());

        activity()
            ->performedOn($user)
            ->causedBy(Auth::user())
            ->log('User created');

        return new UserResource($user);
    }

    /**
     * Display the specified user (admin only).
     */
    public function show(User $user): UserResource
    {
        return new UserResource($user);
    }

    /**
     * Update the specified user (admin only).
     */
    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $user->update($request->validated());

        activity()
            ->performedOn($user)
            ->causedBy(Auth::user())
            ->log('User updated');

        return new UserResource($user);
    }

    /**
     * Remove the specified user (admin only).
     */
    public function destroy(User $user): Response
    {
        activity()
            ->performedOn($user)
            ->causedBy(Auth::user())
            ->log('User deleted');

        $user->delete();

        return response()->noContent();
    }

    /**
     * Search users - same rules as index (admin only).
     */
    public function search(Request $request): AnonymousResourceCollection
    {
        return $this->index($request);
    }

    /**
     * Get user permissions (admin only).
     */
    public function getUserPermissions(User $user): JsonResponse
    {
        $directPermissions = $user->getDirectPermissions();
        $rolePermissions = $user->getPermissionsViaRoles();
        $allPermissions = $user->getAllPermissions();

        return response()->json([
            'data' => [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'direct_permissions' => $directPermissions->pluck('name'),
                'role_permissions' => $rolePermissions->pluck('name'),
                'all_permissions' => $allPermissions->pluck('name'),
                'roles' => $user->getRoleNames(),
            ],
        ]);
    }

    /**
     * Assign permissions to user (admin only).
     */
    public function assignPermissions(AssignPermissionsRequest $request, User $user): JsonResponse
    {
        $permissions = $request->validated()['permissions'];

        $user->givePermissionTo($permissions);

        activity()
            ->performedOn($user)
            ->causedBy(Auth::user())
            ->withProperties(['permissions' => $permissions])
            ->log('Permissions assigned to user');

        return response()->json([
            'message' => 'Permissions assigned successfully.',
            'data' => [
                'user_id' => $user->id,
                'assigned_permissions' => $permissions,
            ],
        ]);
    }

    /**
     * Sync user permissions (admin only).
     */
    public function syncPermissions(SyncPermissionsRequest $request, User $user): JsonResponse
    {
        $permissions = $request->validated()['permissions'];

        $user->syncPermissions($permissions);

        activity()
            ->performedOn($user)
            ->causedBy(Auth::user())
            ->withProperties(['permissions' => $permissions])
            ->log('User permissions synchronized');

        return response()->json([
            'message' => 'Permissions synchronized successfully.',
            'data' => [
                'user_id' => $user->id,
                'current_permissions' => $permissions,
            ],
        ]);
    }

    /**
     * Remove permissions from user (admin only).
     */
    public function removePermissions(RemovePermissionsRequest $request, User $user): JsonResponse
    {
        $permissions = $request->validated()['permissions'];

        $user->revokePermissionTo($permissions);

        activity()
            ->performedOn($user)
            ->causedBy(Auth::user())
            ->withProperties(['permissions' => $permissions])
            ->log('Permissions revoked from user');

        return response()->json([
            'message' => 'Permissions removed successfully.',
            'data' => [
                'user_id' => $user->id,
                'removed_permissions' => $permissions,
            ],
        ]);
    }
}
