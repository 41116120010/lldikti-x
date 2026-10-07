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

        // Filter by Activity Type
        if ($type = $request->query('type')) {
            $query->where('activity_type', $type);
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
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('nip', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);
        $logs = $query->paginate($perPage);

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
        ]);
    }
}
