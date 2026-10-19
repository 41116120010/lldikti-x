<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\FiltersByDateRange;
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
    use FiltersByDateRange;

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

        if ($category = $request->input('category')) {
            $query->forCategory($category);
        }

        $this->applyDateRange(
            $query,
            'created_at',
            $request->input('start_date'),
            $request->input('end_date'),
        );

        if ($search = $request->input('search')) {
            $search = trim((string) $search);
            $query->where(function ($q) use ($search) {
                $q->whereLike('description', "%{$search}%")
                    ->orWhereLike('ip_address', "%{$search}%");
            });
        }

        $perPage = min(max((int) $request->query('per_page', 15), 1), 50);
        $logs = $query->paginate($perPage);

        // Base metrics query for the authenticated user
        $baseMetricsQuery = ActivityLog::where('user_id', $user->id);
        $this->applyDateRange(
            $baseMetricsQuery,
            'created_at',
            $request->input('start_date'),
            $request->input('end_date'),
        );
        if ($search = $request->input('search')) {
            $search = trim((string) $search);
            $baseMetricsQuery->where(function ($q) use ($search) {
                $q->whereLike('description', "%{$search}%")
                    ->orWhereLike('ip_address', "%{$search}%");
            });
        }

        $summary = [
            'total_logs' => $logs->total(),
            'auth_success' => (clone $baseMetricsQuery)->forCategory(ActivityLog::CATEGORY_LOGIN)->whereLike('activity_type', '%LOGIN%')->count(),
            'incident_count' => 0,
            'today_count' => (clone $baseMetricsQuery)->where('created_at', '>=', now('Asia/Jakarta')->startOfDay())->count(),
            'categories' => [
                'login' => (clone $baseMetricsQuery)->forCategory(ActivityLog::CATEGORY_LOGIN)->count(),
                'agenda' => (clone $baseMetricsQuery)->forCategory(ActivityLog::CATEGORY_AGENDA)->count(),
                'presensi' => (clone $baseMetricsQuery)->forCategory(ActivityLog::CATEGORY_PRESENSI)->count(),
                'user' => (clone $baseMetricsQuery)->forCategory(ActivityLog::CATEGORY_USER)->count(),
            ],
        ];

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
            'summary' => $summary,
        ]);
    }
}
