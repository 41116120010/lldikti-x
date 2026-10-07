<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgendaResource;
use App\Http\Resources\AttendanceResource;
use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get dashboard summary and active agendas for authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // 1. Status tallies in a single pass
        $agendaCounts = Agenda::visibleTo($user)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN status = 'ongoing' THEN 1 ELSE 0 END) AS ongoing")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed")
            ->first();

        $stats = [
            'total_agendas' => (int) ($agendaCounts->total ?? 0),
            'ongoing_agendas' => (int) ($agendaCounts->ongoing ?? 0),
            'completed_agendas' => (int) ($agendaCounts->completed ?? 0),
            'upcoming_agendas' => Agenda::visibleTo($user)->upcoming()->count(),
        ];

        if ($user->isAdministrator()) {
            $stats['total_users'] = User::count();
            $stats['total_units'] = Unit::count();
            $stats['total_attendances'] = Attendance::count();
        } elseif ($user->isAdmin()) {
            $stats['total_users'] = User::forUnit($user->unit_id)->count();
            $stats['total_attendances'] = Attendance::whereHas('user', function ($q) use ($user) {
                $q->where('unit_id', $user->unit_id);
            })->count();
        } else {
            $stats['my_attendances'] = Attendance::where('user_id', $user->id)->count();
        }

        // 2. Active / relevant agendas for dashboard
        $activeAgendas = Agenda::visibleTo($user)
            ->with([
                'creator.unit',
                'pimpinan',
                'notulis',
                'units',
                'attendances' => fn ($q) => $q->where('user_id', $user->id),
            ])
            ->withCount('attendances')
            ->relevantForDashboard()
            ->orderByRaw("CASE WHEN status = 'ongoing' THEN 1 ELSE 2 END ASC")
            ->orderBy('waktu_mulai', 'asc')
            ->take(10)
            ->get();

        // 3. User recent attendances
        $recentAttendances = Attendance::where('user_id', $user->id)
            ->with('agenda')
            ->latest('signed_at')
            ->take(5)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data dashboard berhasil diambil.',
            'data' => [
                'stats' => $stats,
                'active_agendas' => AgendaResource::collection($activeAgendas),
                'recent_attendances' => AttendanceResource::collection($recentAttendances),
            ],
        ]);
    }
}
