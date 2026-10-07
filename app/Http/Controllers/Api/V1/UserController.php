<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    /**
     * Display a listing of users scoped by role and unit.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', User::class);

        $currentUser = $request->user();
        $query = User::with('unit');

        // Scoping: Admin Unit is restricted to their own unit only
        if ($currentUser->isAdmin()) {
            $query->where('unit_id', $currentUser->unit_id);
        }

        // Search: Name, NIP, Username, or Email
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter: Unit (Administrator only)
        if ($unitId = $request->input('unit_id')) {
            if ($currentUser->isAdministrator()) {
                if ($unitId === 'none') {
                    $query->whereNull('unit_id');
                } else {
                    $query->where('unit_id', (int) $unitId);
                }
            }
        }

        // Filter: Role
        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        // Filter: Status
        if ($status = $request->input('status')) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $perPage = min(max((int) $request->query('per_page', 15), 1), 50);
        $users = $query->orderBy('name', 'asc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar pengguna berhasil diambil.',
            'data' => UserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request, UserService $userService): JsonResponse
    {
        $currentUser = $request->user();
        $validated = $request->validated();
        $isActive = $request->boolean('is_active', true);

        $user = $userService->createUser($currentUser, $validated, $isActive);
        $user->load('unit');

        return response()->json([
            'success' => true,
            'message' => "Pengguna '{$user->name}' berhasil ditambahkan.",
            'data' => new UserResource($user),
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user, Request $request): JsonResponse
    {
        Gate::authorize('view', $user);

        return response()->json([
            'success' => true,
            'message' => 'Detail pengguna berhasil diambil.',
            'data' => new UserResource($user->load('unit')),
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, User $user, UserService $userService): JsonResponse
    {
        Gate::authorize('update', $user);

        $currentUser = $request->user();
        $validated = $request->validated();
        $isActive = $request->boolean('is_active', true);

        $userService->updateUser($user, $currentUser, $validated, $isActive);

        return response()->json([
            'success' => true,
            'message' => "Data pengguna '{$user->name}' berhasil diperbarui.",
            'data' => new UserResource($user->fresh()->load('unit')),
        ]);
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user, Request $request, UserService $userService): JsonResponse
    {
        Gate::authorize('delete', $user);

        $name = $user->name;

        try {
            $userService->deleteUser($user, $request->user());
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Pengguna '{$name}' berhasil dihapus.",
        ]);
    }

    /**
     * Toggle active/inactive user account status.
     */
    public function toggleStatus(User $user, Request $request, UserService $userService): JsonResponse
    {
        Gate::authorize('update', $user);

        try {
            $statusLabel = $userService->toggleStatus($user, $request->user());
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Akun '{$user->name}' berhasil {$statusLabel}.",
            'data' => new UserResource($user->fresh()->load('unit')),
        ]);
    }
}
