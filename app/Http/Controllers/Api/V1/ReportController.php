<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\FiltersByDateRange;
use App\Http\Controllers\Controller;
use App\Http\Resources\AgendaResource;
use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\Unit;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\DocxExportService;
use App\Services\PdfExportService;
use App\Services\ReportConfigService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    use FiltersByDateRange;

    /**
     * Get aggregate statistics, format distribution, and unit/member participation summaries for reporting.
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Agenda::visibleTo($user);
        $this->applyReportFilters($query, $request, $user);

        $agendaCounts = (clone $query)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN status = 'ongoing' THEN 1 ELSE 0 END) AS ongoing")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed")
            ->selectRaw("SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) AS scheduled")
            ->selectRaw("SUM(CASE WHEN tipe_rapat = 'offline' THEN 1 ELSE 0 END) AS count_offline")
            ->selectRaw("SUM(CASE WHEN tipe_rapat = 'online' THEN 1 ELSE 0 END) AS count_online")
            ->selectRaw("SUM(CASE WHEN tipe_rapat = 'hybrid' THEN 1 ELSE 0 END) AS count_hybrid")
            ->selectRaw("SUM(CASE WHEN notulensi IS NOT NULL AND TRIM(notulensi) != '' THEN 1 ELSE 0 END) AS count_with_notulen")
            ->first();

        $totalAgendas = (int) ($agendaCounts->total ?? 0);
        $completedAgendas = (int) ($agendaCounts->completed ?? 0);
        $ongoingAgendas = (int) ($agendaCounts->ongoing ?? 0);
        $scheduledAgendas = (int) ($agendaCounts->scheduled ?? 0);

        $countOffline = (int) ($agendaCounts->count_offline ?? 0);
        $countOnline = (int) ($agendaCounts->count_online ?? 0);
        $countHybrid = (int) ($agendaCounts->count_hybrid ?? 0);
        $countWithNotulen = (int) ($agendaCounts->count_with_notulen ?? 0);

        $formatStats = [
            'offline' => $countOffline,
            'online' => $countOnline,
            'hybrid' => $countHybrid,
            'pct_offline' => $totalAgendas > 0 ? (int) round(($countOffline / $totalAgendas) * 100) : 0,
            'pct_online' => $totalAgendas > 0 ? (int) round(($countOnline / $totalAgendas) * 100) : 0,
            'pct_hybrid' => $totalAgendas > 0 ? (int) round(($countHybrid / $totalAgendas) * 100) : 0,
        ];

        $governanceRate = $completedAgendas > 0
            ? round(min(($countWithNotulen / $completedAgendas) * 100, 100.0), 1)
            : 100.0;

        $totalAttendances = Attendance::whereHas('agenda', function ($q) use ($user, $request) {
            $q->visibleTo($user);
            $this->applyReportFilters($q, $request, $user);
        })->count();

        $avgAttendances = $totalAgendas > 0 ? round($totalAttendances / $totalAgendas, 1) : 0;

        // Institutional / Unit Attendance Compliance Rate
        $attendanceRate = 100.0;
        $attendanceRateSubtext = 'Belum ada rapat dinas selesai';

        if ($user->isAdministrator()) {
            $totalActiveUsers = User::where('is_active', true)->count();
            $completedAttendances = Attendance::whereHas('agenda', fn ($q) => $q->where('status', 'completed'))->count();
            $expected = $completedAgendas * max($totalActiveUsers, 1);
            $attendanceRate = ($completedAgendas === 0 || $totalActiveUsers === 0)
                ? 100.0
                : round(min(($completedAttendances / max($expected, 1)) * 100, 100.0), 1);
            $attendanceRateSubtext = $completedAgendas > 0
                ? "Rata-rata Seluruh ASN ({$totalActiveUsers} Pegawai)"
                : 'Belum ada rapat dinas selesai';
        } elseif ($user->isAdmin() && $user->unit_id) {
            $unitUsersCount = User::forUnit($user->unit_id)->count();
            $unitCompletedAttendances = Attendance::whereHas('user', fn ($q) => $q->where('unit_id', $user->unit_id))
                ->whereHas('agenda', fn ($q) => $q->where('status', 'completed'))
                ->count();
            $expected = $completedAgendas * max($unitUsersCount, 1);
            $attendanceRate = ($completedAgendas === 0 || $unitUsersCount === 0)
                ? 100.0
                : round(min(($unitCompletedAttendances / max($expected, 1)) * 100, 100.0), 1);
            $attendanceRateSubtext = $completedAgendas > 0
                ? "Kehadiran Unit ({$unitUsersCount} Pegawai)"
                : 'Belum ada rapat dinas selesai';
        }

        // Unit Participation Card (Administrator) or Unit Member Participation Card (Admin Unit)
        $unitStats = [];
        $memberStats = [];

        if ($user->isAdministrator()) {
            $unitAttendanceQuery = Attendance::query()
                ->join('users', 'attendances.user_id', '=', 'users.id')
                ->whereNotNull('users.unit_id')
                ->selectRaw('users.unit_id, count(*) as total')
                ->groupBy('users.unit_id');

            $this->applyDateRange(
                $unitAttendanceQuery,
                'attendances.signed_at',
                $request->query('start_date'),
                $request->query('end_date'),
            );

            $attendanceCountsByUnit = $unitAttendanceQuery->pluck('total', 'users.unit_id');

            $unitStats = Unit::active()
                ->withCount('users')
                ->orderBy('nama_unit')
                ->get()
                ->map(function (Unit $unit) use ($attendanceCountsByUnit) {
                    return [
                        'unit_id' => $unit->id,
                        'kode_unit' => $unit->kode_unit,
                        'nama_unit' => $unit->nama_unit,
                        'attendances_count' => (int) ($attendanceCountsByUnit[$unit->id] ?? 0),
                        'total_users' => (int) $unit->users_count,
                    ];
                })
                ->values()
                ->all();
        } elseif ($user->isAdmin() && $user->unit_id) {
            $memberAttendanceQuery = Attendance::query()
                ->whereHas('user', fn ($q) => $q->where('unit_id', $user->unit_id))
                ->selectRaw('user_id, count(*) as total')
                ->groupBy('user_id');

            $this->applyDateRange(
                $memberAttendanceQuery,
                'signed_at',
                $request->query('start_date'),
                $request->query('end_date'),
            );

            $memberAttendanceCounts = $memberAttendanceQuery->pluck('total', 'user_id');

            $memberStats = User::forUnit($user->unit_id)
                ->orderBy('name')
                ->get()
                ->map(function (User $member) use ($memberAttendanceCounts) {
                    return [
                        'user_id' => $member->id,
                        'name' => $member->name,
                        'nip' => $member->nip,
                        'jabatan' => $member->jabatan,
                        'attendances_count' => (int) ($memberAttendanceCounts[$member->id] ?? 0),
                    ];
                })
                ->values()
                ->all();
        }

        return response()->json([
            'success' => true,
            'message' => 'Ringkasan laporan berhasil diambil.',
            'data' => [
                'total_agendas' => $totalAgendas,
                'completed_agendas' => $completedAgendas,
                'ongoing_agendas' => $ongoingAgendas,
                'scheduled_agendas' => $scheduledAgendas,
                'total_attendances' => $totalAttendances,
                'avg_attendances_per_agenda' => $avgAttendances,
                'format_stats' => $formatStats,
                'governance_rate' => $governanceRate,
                'count_with_notulen' => $countWithNotulen,
                'attendance_rate' => $attendanceRate,
                'attendance_rate_subtext' => $attendanceRateSubtext,
                'unit_stats' => $unitStats,
                'member_stats' => $memberStats,
            ],
        ]);
    }

    /**
     * Get paginated list of agendas for the Executive Reporting & Recap table in mobile client.
     */
    public function agendas(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Agenda::visibleTo($user)
            ->with([
                'creator.unit',
                'pimpinan.unit',
                'notulis.unit',
                'units',
            ])
            ->withCount('attendances');

        $this->applyReportFilters($query, $request, $user);

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->whereLike('judul_rapat', "%{$search}%")
                    ->orWhereLike('lokasi_ruang', "%{$search}%");
            });
        }

        $perPage = min(max((int) $request->query('per_page', 10), 1), 50);
        $agendas = $query->orderBy('waktu_mulai', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar rekapitulasi agenda rapat berhasil diambil.',
            'data' => AgendaResource::collection($agendas),
            'meta' => [
                'current_page' => $agendas->currentPage(),
                'last_page' => $agendas->lastPage(),
                'per_page' => $agendas->perPage(),
                'total' => $agendas->total(),
            ],
        ]);
    }

    /**
     * Get detailed agenda recap with export links for mobile.
     */
    public function agendaRecap(Agenda $agenda, Request $request): JsonResponse
    {
        Gate::authorize('view', $agenda);

        $agenda->load([
            'creator.unit',
            'pimpinan.unit',
            'notulis.unit',
            'units',
            'documentations',
        ]);
        $agenda->loadCount('attendances');

        return response()->json([
            'success' => true,
            'message' => 'Data rekapitulasi agenda berhasil diambil.',
            'data' => [
                'agenda' => new AgendaResource($agenda),
                'report_config' => $agenda->resolved_report_config,
                'export_links' => [
                    'pdf' => url("/api/v1/reports/agendas/{$agenda->id}/export/pdf"),
                    'word' => url("/api/v1/reports/agendas/{$agenda->id}/export/word"),
                    'csv' => url("/api/v1/reports/agendas/{$agenda->id}/export/csv"),
                ],
            ],
        ]);
    }

    /**
     * Download or stream protected PDF meeting document (supports custom ReportConfigService parameters).
     */
    public function exportPdf(Request $request, Agenda $agenda, PdfExportService $pdfService): SymfonyResponse
    {
        Gate::authorize('view', $agenda);

        $config = $this->extractReportConfig($request, $agenda);

        ActivityLogger::log(
            type: 'EXPORT_PDF_API',
            description: "Mengekspor Berita Acara & Daftar Hadir PDF via API Mobile untuk agenda: {$agenda->judul_rapat}",
            targetModel: Agenda::class,
            targetId: $agenda->id,
            properties: ['custom_config' => ! empty($request->all())]
        );

        try {
            return $pdfService->exportBinaryPdf($agenda, $config);
        } catch (\Throwable) {
            return $pdfService->exportBeritaAcara($agenda, $config);
        }
    }

    /**
     * Download or stream protected Microsoft Word (.doc) meeting document (supports custom ReportConfigService parameters).
     */
    public function exportWord(Request $request, Agenda $agenda, DocxExportService $docxService): SymfonyResponse
    {
        Gate::authorize('view', $agenda);

        $config = $this->extractReportConfig($request, $agenda);

        ActivityLogger::log(
            type: 'EXPORT_WORD_API',
            description: "Mengekspor Berita Acara & Daftar Hadir Word via API Mobile untuk agenda: {$agenda->judul_rapat}",
            targetModel: Agenda::class,
            targetId: $agenda->id,
            properties: ['custom_config' => ! empty($request->all())]
        );

        return $docxService->exportBeritaAcara($agenda, $config);
    }

    /**
     * Export attendance tabulation of a specific agenda to CSV spreadsheet for mobile clients.
     */
    public function exportAgendaCsv(Request $request, Agenda $agenda): StreamedResponse
    {
        Gate::authorize('view', $agenda);

        ActivityLogger::log(
            type: 'EXPORT_AGENDA_CSV_API',
            description: "Mengekspor tabulasi kehadiran CSV via API Mobile untuk agenda: {$agenda->judul_rapat}",
            targetModel: Agenda::class,
            targetId: $agenda->id
        );

        $safeSlug = Str::slug($agenda->judul_rapat, '_');
        $filename = "Daftar_Hadir_{$safeSlug}_" . date('Ymd_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($agenda) {
            $file = fopen('php://output', 'w');
            // Add UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // CSV Header Row
            fputcsv($file, [
                'No',
                'Nama ASN',
                'NIP',
                'Jabatan',
                'Unit Kerja',
                'Waktu Presensi',
                'Format Kehadiran',
                'Status Presensi',
                'IP Address',
            ]);

            $index = 0;
            $formatKehadiran = strtoupper($agenda->tipe_rapat ?? 'LURING');

            $agenda->attendances()
                ->with(['user.unit'])
                ->oldest('signed_at')
                ->chunk(200, function ($attendances) use ($file, &$index, $formatKehadiran) {
                    foreach ($attendances as $attendance) {
                        $index++;

                        $safeName = $this->sanitizeCsvValue($attendance->user?->name ?? 'Pegawai');
                        $safeNip = $this->sanitizeCsvValue($attendance->user?->nip ?? '-');
                        $safeJabatan = $this->sanitizeCsvValue($attendance->user?->jabatan ?? '-');
                        $safeUnit = $this->sanitizeCsvValue($attendance->user?->unit?->nama_unit ?? '-');
                        $waktuPresensi = $attendance->signed_at ? $attendance->signed_at->format('d/m/Y H:i:s') : '-';
                        $safeIp = $this->sanitizeCsvValue($attendance->ip_address ?? '-');

                        fputcsv($file, [
                            $index,
                            $safeName,
                            $safeNip,
                            $safeJabatan,
                            $safeUnit,
                            $waktuPresensi,
                            $formatKehadiran,
                            'Hadir Sah',
                            $safeIp,
                        ]);
                    }
                });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export summary table of meetings to CSV spreadsheet for mobile clients (13 Indonesian columns, matching Web).
     */
    public function exportSummaryCsv(Request $request): StreamedResponse
    {
        $user = $request->user();
        $query = Agenda::visibleTo($user)
            ->with([
                'creator:id,name',
                'pimpinan:id,name',
                'notulis:id,name',
            ])
            ->withCount('attendances');

        ActivityLogger::log(
            type: 'EXPORT_CSV_API',
            description: 'Mengekspor rekapitulasi data agenda rapat ke format CSV via API Mobile',
            targetModel: Agenda::class
        );

        $this->applyReportFilters($query, $request, $user);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="Rekapitulasi_Agenda_LLDIKTI_' . date('Ymd_His') . '.csv"',
        ];

        $callback = function () use ($query) {
            $file = fopen('php://output', 'w');
            // Add UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // CSV Header Row (13 Columns matching Web ReportController::exportSummaryCsv)
            fputcsv($file, [
                'No',
                'Judul Rapat',
                'Jenis Pertemuan',
                'Format',
                'Lokasi / Ruang',
                'Waktu Mulai',
                'Waktu Selesai',
                'Status Rapat',
                'Status Notulensi',
                'Pemimpin Rapat',
                'Notulis Rapat',
                'Penyelenggara / Creator',
                'Jumlah Peserta Hadir',
            ]);

            $index = 0;

            $query->orderBy('waktu_mulai', 'desc')->chunk(500, function ($agendas) use ($file, &$index) {
                foreach ($agendas as $agenda) {
                    $index++;

                    $safeTitle = $this->sanitizeCsvValue($agenda->judul_rapat);
                    $safeLocation = $this->sanitizeCsvValue($agenda->lokasi_ruang ?? 'Daring / Online');
                    $safeCreator = $this->sanitizeCsvValue($agenda->creator?->name ?? 'Sistem');

                    $statusRapat = match ($agenda->status) {
                        'completed' => 'Selesai',
                        'ongoing' => 'Sedang Berlangsung',
                        'scheduled' => 'Terjadwal',
                        'cancelled' => 'Dibatalkan',
                        'draft' => 'Draf',
                        default => ucfirst($agenda->status),
                    };

                    $hasNotulen = ! empty(trim(strip_tags((string) $agenda->notulensi)));
                    $statusNotulensi = match (true) {
                        $hasNotulen => 'Lengkap',
                        $agenda->status === 'completed' => 'Belum Diisi',
                        $agenda->status === 'ongoing' => 'Sedang Berlangsung',
                        default => 'Belum Dimulai',
                    };

                    $safePimpinan = $this->sanitizeCsvValue($agenda->nama_pimpinan);
                    $safeNotulis = $this->sanitizeCsvValue($agenda->nama_notulis);

                    fputcsv($file, [
                        $index,
                        $safeTitle,
                        ucfirst($agenda->jenis_rapat),
                        strtoupper($agenda->tipe_rapat),
                        $safeLocation,
                        $agenda->waktu_mulai->format('d/m/Y H:i'),
                        $agenda->waktu_selesai ? $agenda->waktu_selesai->format('d/m/Y H:i') : '-',
                        $statusRapat,
                        $statusNotulensi,
                        $safePimpinan,
                        $safeNotulis,
                        $safeCreator,
                        $agenda->attendances_count,
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Reset report configuration for an agenda to system defaults via API.
     */
    public function resetReportConfig(Agenda $agenda, Request $request): JsonResponse
    {
        Gate::authorize('update', $agenda);

        $agenda->update(['report_config' => null]);

        ActivityLogger::log(
            type: 'RESET_REPORT_CONFIG_API',
            description: "Konfigurasi dokumen laporan agenda '{$agenda->judul_rapat}' direset ke standar sistem via API Mobile.",
            targetModel: Agenda::class,
            targetId: $agenda->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Konfigurasi dokumen laporan berhasil direset ke standar sistem.',
            'data' => new AgendaResource($agenda->refresh()->load(['creator.unit', 'pimpinan.unit', 'notulis.unit', 'units'])),
        ]);
    }

    /**
     * Apply common report filters (date range, status, unit_id, tipe_rapat/tipe).
     */
    private function applyReportFilters(Builder $query, Request $request, User $user): void
    {
        $this->applyDateRange(
            $query,
            'waktu_mulai',
            $request->input('start_date'),
            $request->input('end_date'),
        );

        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        if ($unitId = $request->input('unit_id')) {
            if ($user->isAdministrator() && $unitId !== 'all') {
                $query->where(function ($q) use ($unitId) {
                    $q->where('is_all_units', true)
                        ->orWhereHas('units', function ($sub) use ($unitId) {
                            $sub->where('units.id', (int) $unitId);
                        });
                });
            }
        }

        $tipe = $request->input('tipe_rapat') ?? $request->input('tipe');
        if ($tipe && $tipe !== 'all') {
            $query->where('tipe_rapat', $tipe);
        }
    }

    /**
     * Extract, validate, and sanitize custom report configuration from request.
     *
     * @return array<string,mixed>
     */
    protected function extractReportConfig(Request $request, Agenda $agenda): array
    {
        if ($request->isMethod('get') && empty($request->query())) {
            return $agenda->resolved_report_config;
        }

        $configService = app(ReportConfigService::class);
        $config = $configService->extractFromRequest($request, $agenda);

        if ($request->boolean('save_as_default') && Gate::allows('manageMinutes', $agenda)) {
            $agenda->update(['report_config' => $config]);
        }

        return $config;
    }

    /**
     * Sanitize string values to prevent CSV / Formula Injection (CWE-1236).
     */
    private function sanitizeCsvValue(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (preg_match('/^[=\+\-@\t\r]/', $value)) {
            return "'" . $value;
        }

        return $value;
    }
}


