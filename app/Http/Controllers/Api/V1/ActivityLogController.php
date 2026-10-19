<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\FiltersByDateRange;
use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ActivityLogController extends Controller
{
    use FiltersByDateRange;

    /**
     * Display a paginated listing of agency-wide activity logs (Superadmin only).
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', ActivityLog::class);

        $query = ActivityLog::with('user.unit')->latest('id');

        // Filter by Specific Activity Type
        if ($type = $request->query('type')) {
            $query->where('activity_type', $type);
        }

        // Filter by Macro Category (login, agenda, presensi, user)
        if ($category = $request->query('category')) {
            $query->forCategory($category);
        }

        // Filter by Specific User ID
        if ($userId = $request->query('user_id')) {
            $query->where('user_id', $userId);
        }

        // Filter by Date Range (indexed timestamp comparison)
        $this->applyDateRange(
            $query,
            'created_at',
            $request->query('start_date'),
            $request->query('end_date'),
        );

        // Search in description, IP address, or user name / NIP
        if ($search = $request->query('search')) {
            $search = trim((string) $search);
            $query->where(function ($q) use ($search) {
                $q->whereLike('description', "%{$search}%")
                    ->orWhereLike('ip_address', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->whereLike('name', "%{$search}%")
                            ->orWhereLike('nip', "%{$search}%");
                    });
            });
        }

        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);
        $logs = $query->paginate($perPage);

        // Base metrics query (respecting user_id, date range, and search, but independent of category pill)
        $baseMetricsQuery = ActivityLog::query();
        if ($userId = $request->query('user_id')) {
            $baseMetricsQuery->where('user_id', $userId);
        }
        $this->applyDateRange(
            $baseMetricsQuery,
            'created_at',
            $request->query('start_date'),
            $request->query('end_date'),
        );
        if ($search = $request->query('search')) {
            $search = trim((string) $search);
            $baseMetricsQuery->where(function ($q) use ($search) {
                $q->whereLike('description', "%{$search}%")
                    ->orWhereLike('ip_address', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->whereLike('name', "%{$search}%")
                            ->orWhereLike('nip', "%{$search}%");
                    });
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
            'message' => 'Daftar log aktivitas sistem berhasil diambil.',
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
