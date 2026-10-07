<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\StoreAttendanceRequest;
use App\Http\Resources\AgendaResource;
use App\Http\Resources\AttendanceResource;
use App\Models\Agenda;
use App\Models\Attendance;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class AttendanceController extends Controller
{
    /**
     * Get attendance portal feed containing ongoing agendas, upcoming agendas, and recent attendances.
     */
    public function portalFeed(Request $request): JsonResponse
    {
        $user = $request->user();

        // Agendas currently open for attendance
        $ongoingAgendas = Agenda::visibleTo($user)
            ->where('status', 'ongoing')
            ->with([
                'creator.unit',
                'units',
                'attendances' => fn ($q) => $q->where('user_id', $user->id),
            ])
            ->withCount('attendances')
            ->orderBy('waktu_mulai', 'desc')
            ->get();

        // Upcoming scheduled meetings that have not passed yet
        $scheduledAgendas = Agenda::visibleTo($user)
            ->upcoming()
            ->with(['creator.unit', 'units'])
            ->orderBy('waktu_mulai', 'asc')
            ->take(10)
            ->get();

        // Personal recent attendances
        $recentAttendances = Attendance::where('user_id', $user->id)
            ->with(['agenda.creator.unit'])
            ->latest('signed_at')
            ->take(5)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Feed portal presensi berhasil diambil.',
            'data' => [
                'ongoing_agendas' => AgendaResource::collection($ongoingAgendas),
                'upcoming_agendas' => AgendaResource::collection($scheduledAgendas),
                'recent_attendances' => AttendanceResource::collection($recentAttendances),
            ],
        ]);
    }

    /**
     * Check attendance portal eligibility and current status for an agenda.
     */
    public function portal(Agenda $agenda, Request $request): JsonResponse
    {
        $user = $request->user();

        $canView = Gate::allows('view', $agenda) || Gate::allows('viewStaff', $agenda);
        if (!$canView) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat agenda rapat ini.');
        }

        $isEligible = $agenda->isUserEligible($user);
        $existingAttendance = Attendance::where('agenda_id', $agenda->id)
            ->where('user_id', $user->id)
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Status portal presensi berhasil diperiksa.',
            'data' => [
                'agenda' => new AgendaResource($agenda->load(['creator.unit', 'units'])),
                'is_ongoing' => $agenda->status === 'ongoing',
                'is_eligible' => $isEligible,
                'has_attended' => $existingAttendance !== null,
                'my_attendance' => $existingAttendance ? new AttendanceResource($existingAttendance) : null,
            ],
        ]);
    }

    /**
     * Store attendee check-in with biometric selfie and digital signature.
     */
    public function store(StoreAttendanceRequest $request, Agenda $agenda, AttendanceService $attendanceService): JsonResponse
    {
        $user = $request->user();

        $result = $attendanceService->recordAttendance(
            agenda: $agenda,
            user: $user,
            data: array_merge($request->validated(), [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'selfie_file' => $request->file('selfie_file'),
                'signature_file' => $request->file('signature_file'),
            ])
        );

        $attendance = $result['attendance']->load(['user.unit', 'agenda']);

        if ($result['is_duplicate']) {
            return response()->json([
                'success' => true,
                'message' => 'Presensi Anda pada agenda rapat ini sudah pernah tercatat.',
                'data' => new AttendanceResource($attendance),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Presensi pada agenda '{$agenda->judul_rapat}' berhasil disimpan.",
            'data' => new AttendanceResource($attendance),
        ], 201);
    }

    /**
     * List user's attendance check-in history.
     */
    public function myHistory(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = min(max((int) $request->query('per_page', 15), 1), 50);

        $attendances = Attendance::where('user_id', $user->id)
            ->with(['agenda.creator.unit'])
            ->latest('signed_at')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Riwayat presensi berhasil diambil.',
            'data' => AttendanceResource::collection($attendances),
            'meta' => [
                'current_page' => $attendances->currentPage(),
                'last_page' => $attendances->lastPage(),
                'per_page' => $attendances->perPage(),
                'total' => $attendances->total(),
            ],
        ]);
    }

    /**
     * List all attendances for a specific agenda (Admin / Leader access).
     */
    public function agendaAttendances(Agenda $agenda, Request $request): JsonResponse
    {
        Gate::authorize('view', $agenda);

        $perPage = min(max((int) $request->query('per_page', 25), 1), 100);

        $attendances = $agenda->attendances()
            ->with(['user.unit'])
            ->latest('signed_at')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar kehadiran agenda rapat berhasil diambil.',
            'data' => AttendanceResource::collection($attendances),
            'meta' => [
                'current_page' => $attendances->currentPage(),
                'last_page' => $attendances->lastPage(),
                'per_page' => $attendances->perPage(),
                'total' => $attendances->total(),
            ],
        ]);
    }

    /**
     * Stream protected selfie file for authorized clients.
     */
    public function selfie(Attendance $attendance): Response
    {
        Gate::authorize('view', $attendance);

        if (!$attendance->selfie_path || !Storage::disk('public')->exists($attendance->selfie_path)) {
            abort(404, 'Berkas foto selfie tidak ditemukan.');
        }

        return Storage::disk('public')->response(
            $attendance->selfie_path,
            headers: [
                'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            ]
        );
    }

    /**
     * Stream protected signature file for authorized clients.
     */
    public function signature(Attendance $attendance): Response
    {
        Gate::authorize('view', $attendance);

        if (!$attendance->signature_path || !Storage::disk('public')->exists($attendance->signature_path)) {
            abort(404, 'Berkas tanda tangan digital tidak ditemukan.');
        }

        return Storage::disk('public')->response(
            $attendance->signature_path,
            headers: [
                'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            ]
        );
    }
}
