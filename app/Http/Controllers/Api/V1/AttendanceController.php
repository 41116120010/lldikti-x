<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\StoreAttendanceRequest;
use App\Http\Resources\AgendaResource;
use App\Http\Resources\AttendanceResource;
use App\Http\Resources\UserResource;
use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
     * List user's attendance check-in history with advanced filtering and summary telemetry.
     */
    public function myHistory(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = min(max((int) $request->query('per_page', 15), 1), 50);

        $query = Attendance::where('user_id', $user->id)
            ->with(['agenda.creator.unit']);

        // 1. Filter Search (Judul Rapat, Lokasi Ruang, atau Nomor BA/ID)
        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->whereHas('agenda', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->whereLike('judul_rapat', "%{$search}%")
                        ->orWhereLike('lokasi_ruang', "%{$search}%");

                    if (is_numeric($search)) {
                        $sub->orWhere('id', (int) $search);
                    }
                });
            });
        }

        // 2. Filter Periode (Bulan/Tahun 'YYYY-MM' atau Rentang Tanggal)
        $periodStartDate = null;
        $periodEndDate = null;
        $periodLabel = 'Tahun Anggaran ' . Carbon::now('Asia/Jakarta')->year;

        if ($request->filled('period') && $request->query('period') !== 'all') {
            $periodStr = trim((string) $request->query('period'));
            if (preg_match('/^\d{4}-\d{2}$/', $periodStr)) {
                try {
                    $carbonPeriod = Carbon::createFromFormat('Y-m', $periodStr, 'Asia/Jakarta');
                    $periodStartDate = $carbonPeriod->copy()->startOfMonth();
                    $periodEndDate = $carbonPeriod->copy()->endOfMonth();

                    $months = [
                        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
                        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
                    ];
                    $monthNum = (int) $carbonPeriod->format('n');
                    $periodLabel = ($months[$monthNum] ?? $carbonPeriod->format('M')) . ' ' . $carbonPeriod->year;
                } catch (\Throwable) {
                    $periodStartDate = null;
                    $periodEndDate = null;
                }
            }
        } elseif ($request->filled('start_date') || $request->filled('end_date')) {
            if ($request->filled('start_date')) {
                try {
                    $periodStartDate = Carbon::parse($request->query('start_date'), 'Asia/Jakarta')->startOfDay();
                } catch (\Throwable) {}
            }
            if ($request->filled('end_date')) {
                try {
                    $periodEndDate = Carbon::parse($request->query('end_date'), 'Asia/Jakarta')->endOfDay();
                } catch (\Throwable) {}
            }
            if ($periodStartDate && $periodEndDate) {
                $periodLabel = $periodStartDate->format('d/m/Y') . ' - ' . $periodEndDate->format('d/m/Y');
            } elseif ($periodStartDate) {
                $periodLabel = 'Sejak ' . $periodStartDate->format('d/m/Y');
            } elseif ($periodEndDate) {
                $periodLabel = 'Sampai ' . $periodEndDate->format('d/m/Y');
            }
        }

        if ($periodStartDate && $periodEndDate) {
            $query->whereBetween('signed_at', [$periodStartDate, $periodEndDate]);
        } elseif ($periodStartDate) {
            $query->where('signed_at', '>=', $periodStartDate);
        } elseif ($periodEndDate) {
            $query->where('signed_at', '<=', $periodEndDate);
        }

        // 3. Filter Tipe Rapat (offline/luring, online/daring, hybrid)
        $tipeRapat = null;
        if ($request->filled('tipe_rapat') && $request->query('tipe_rapat') !== 'all') {
            $tipe = strtolower(trim((string) $request->query('tipe_rapat')));
            if ($tipe === 'luring') {
                $tipe = 'offline';
            } elseif ($tipe === 'daring') {
                $tipe = 'online';
            }
            if (in_array($tipe, ['offline', 'online', 'hybrid'], true)) {
                $tipeRapat = $tipe;
                $query->whereHas('agenda', fn ($q) => $q->where('tipe_rapat', $tipe));
            }
        }

        // 4. Eksekusi Paginasi
        $attendances = $query->latest('signed_at')->paginate($perPage);

        // 5. Kalkulasi Agregat Telemetri Summary
        $eligibleAgendasQuery = Agenda::visibleTo($user)->where('status', 'completed');
        if ($periodStartDate && $periodEndDate) {
            $eligibleAgendasQuery->whereBetween('waktu_mulai', [$periodStartDate, $periodEndDate]);
        } elseif ($periodStartDate) {
            $eligibleAgendasQuery->where('waktu_mulai', '>=', $periodStartDate);
        } elseif ($periodEndDate) {
            $eligibleAgendasQuery->where('waktu_mulai', '<=', $periodEndDate);
        }
        if ($tipeRapat !== null) {
            $eligibleAgendasQuery->where('tipe_rapat', $tipeRapat);
        }
        $totalEligibleAgendas = $eligibleAgendasQuery->count();

        $attendedCompletedQuery = Attendance::where('user_id', $user->id)
            ->whereHas('agenda', function ($q) use ($periodStartDate, $periodEndDate, $tipeRapat) {
                $q->where('status', 'completed');
                if ($periodStartDate && $periodEndDate) {
                    $q->whereBetween('waktu_mulai', [$periodStartDate, $periodEndDate]);
                } elseif ($periodStartDate) {
                    $q->where('waktu_mulai', '>=', $periodStartDate);
                } elseif ($periodEndDate) {
                    $q->where('waktu_mulai', '<=', $periodEndDate);
                }
                if ($tipeRapat !== null) {
                    $q->where('tipe_rapat', $tipeRapat);
                }
            });
        $attendedCompletedCount = $attendedCompletedQuery->count();

        $rate = ($totalEligibleAgendas === 0)
            ? 100.0
            : round(min(($attendedCompletedCount / $totalEligibleAgendas) * 100, 100.0), 1);

        $summary = [
            'total_attended' => $attendances->total(),
            'total_eligible_agendas' => $totalEligibleAgendas,
            'attendance_rate' => $rate,
            'attendance_rate_label' => "{$rate}%",
            'period_label' => $periodLabel,
        ];

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
            'summary' => $summary,
        ]);
    }

    /**
     * List all attendances for a specific agenda (Admin / Leader access).
     * Supports filtering by status: 'all' / 'attended' (default), 'unattended' (invited but not yet checked in).
     */
    public function agendaAttendances(Agenda $agenda, Request $request): JsonResponse
    {
        Gate::authorize('view', $agenda);

        $perPage = min(max((int) $request->query('per_page', 25), 1), 100);
        $status = $request->query('status', 'all');

        $summary = [
            'total_invited' => $agenda->total_invited,
            'total_attended' => $agenda->total_attended,
            'unattended_count' => $agenda->unattended_count,
            'participation_rate' => $agenda->participation_rate,
            'participation_rate_label' => $agenda->participation_rate_label,
        ];

        if ($status === 'unattended') {
            $attendedUserIds = $agenda->attendances()->pluck('user_id');

            $unattendedQuery = User::where('is_active', true)
                ->with('unit')
                ->whereNotIn('id', $attendedUserIds);

            if (! $agenda->is_all_units) {
                $unitIds = $agenda->units()->pluck('units.id');
                $unattendedQuery->whereIn('unit_id', $unitIds);
            }

            if ($request->filled('search')) {
                $search = trim((string) $request->query('search'));
                $unattendedQuery->where(function ($q) use ($search) {
                    $q->whereLike('name', "%{$search}%")
                        ->orWhereLike('nip', "%{$search}%");
                });
            }

            $unattendedPaginated = $unattendedQuery->orderBy('name')->paginate($perPage);

            $data = $unattendedPaginated->getCollection()->map(function (User $user) use ($agenda) {
                return [
                    'id' => $user->id,
                    'agenda_id' => $agenda->id,
                    'user_id' => $user->id,
                    'user' => (new UserResource($user))->resolve(),
                    'signed_at' => null,
                    'selfie_url' => null,
                    'signature_url' => null,
                    'ip_address' => null,
                    'user_agent' => null,
                    'status' => 'belum_hadir',
                    'created_at' => null,
                    'updated_at' => null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Daftar ASN yang belum presensi berhasil diambil.',
                'data' => $data,
                'meta' => [
                    'current_page' => $unattendedPaginated->currentPage(),
                    'last_page' => $unattendedPaginated->lastPage(),
                    'per_page' => $unattendedPaginated->perPage(),
                    'total' => $unattendedPaginated->total(),
                ],
                'summary' => $summary,
            ]);
        }

        $attendancesQuery = $agenda->attendances()
            ->with(['user.unit'])
            ->latest('signed_at');

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $attendancesQuery->whereHas('user', function ($q) use ($search) {
                $q->whereLike('name', "%{$search}%")
                    ->orWhereLike('nip', "%{$search}%");
            });
        }

        $attendances = $attendancesQuery->paginate($perPage);

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
            'summary' => $summary,
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
