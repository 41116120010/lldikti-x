<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\ActivityLogResource;
use App\Http\Resources\UserResource;
use App\Models\ActivityLog;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Get authenticated user profile.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('unit');

        return response()->json([
            'success' => true,
            'message' => 'Profil pengguna berhasil diambil.',
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Update authenticated user profile and password.
     */
    public function update(UpdateProfileRequest $request, UserService $userService): JsonResponse
    {
        $user = $request->user();

        $userService->updateProfile($user, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Profil akun Anda berhasil diperbarui.',
            'data' => new UserResource($user->fresh()->load('unit')),
        ]);
    }

    /**
     * Get authenticated user activity audit logs.
     */
    public function logs(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = ActivityLog::where('user_id', $user->id)->latest('id');

        if ($type = $request->input('type')) {
            $query->where('activity_type', $type);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        $perPage = min(max((int) $request->query('per_page', 15), 1), 50);
        $logs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Riwayat aktivitas berhasil diambil.',
            'data' => ActivityLogResource::collection($logs),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }
}
