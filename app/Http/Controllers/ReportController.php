<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersByDateRange;
use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\Unit;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\AttendanceMetricsService;
use App\Services\PdfExportService;
use App\Services\ReportConfigService;
use App\Services\DocxExportService;
use App\Services\WordExportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    use FiltersByDateRange;

    /**
     * Display the Executive Reporting & Meeting Recap Dashboard.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Agenda::class);

        $user = Auth::user();

        // Base scoped agenda query.
        // 'attendances' is intentionally NOT eager loaded: the view only needs the
        // tally, which withCount() resolves in a single subquery. Loading the full
        // relation would drag selfie_path, signature_path, ip_address and
        // user_agent (TEXT) for every attendee just to render a number.
        $query = Agenda::visibleTo($user)
            ->with(['creator.unit', 'units'])
            ->withCount('attendances');

        $this->applyReportFilters($query, $request, $user);

        $agendas = $query->orderBy('waktu_mulai', 'desc')->paginate(10)->withQueryString();
        Agenda::preloadTotalInvited($agendas->getCollection());

        // Direct database aggregate calculations (O(1) memory footprint).
        // Apply the same active filters so KPI cards and format distribution stay consistent with the table.
        $baseScopedQuery = Agenda::visibleTo($user);
        $this->applyReportFilters($baseScopedQuery, $request, $user);
        $agendaCounts = (clone $baseScopedQuery)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed")
            ->selectRaw("SUM(CASE WHEN status = 'ongoing' THEN 1 ELSE 0 END) AS ongoing")
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
            'pct_offline' => $totalAgendas > 0 ? round(($countOffline / $totalAgendas) * 100) : 0,
            'pct_online' => $totalAgendas > 0 ? round(($countOnline / $totalAgendas) * 100) : 0,
            'pct_hybrid' => $totalAgendas > 0 ? round(($countHybrid / $totalAgendas) * 100) : 0,
        ];

        // Tata kelola dokumen (Governance Compliance): persentase rapat selesai yang telah dilengkapi notulensi
        $governanceRate = $completedAgendas > 0
            ? round(min(($countWithNotulen / $completedAgendas) * 100, 100.0), 1)
            : 100.0;

        // Correlated EXISTS instead of `whereIn('agenda_id', <subquery>)`, which
        // forced MySQL to materialise the entire scoped agenda id list.
        $totalPresensi = Attendance::whereHas('agenda', function ($q) use ($user, $request) {
            $q->visibleTo($user);
            $this->applyReportFilters($q, $request, $user);
        })->count();
        $avgPresensi = $totalAgendas > 0 ? round($totalPresensi / $totalAgendas, 1) : 0;

        // Tingkat Kepatuhan Presensi ASN (terpusat di AttendanceMetricsService)
        $attendanceMetrics = AttendanceMetricsService::forDashboardOrReport($user);
        $attendanceRate = $attendanceMetrics['attendance_rate'];
        $attendanceRateSubtext = $attendanceMetrics['attendance_rate_subtext'];

        $units = $user->isAdministrator() ? Unit::active()->orderBy('nama_unit')->get() : collect();
        $unitStats = null;
        $memberStats = null;

        if ($user->isAdministrator()) {
            // Eliminate N+1 query: Fetch unit attendance stats with a single group-by
            // aggregation. Scoped to the requested window and to the Administrator
            // branch that actually consumes it — previously this ran for every role
            // on every page load, scanning the whole attendances table.
            $unitAttendanceQuery = Attendance::query()
                ->join('users', 'attendances.user_id', '=', 'users.id')
                ->whereNotNull('users.unit_id')
                ->selectRaw('users.unit_id, count(*) as total')
                ->groupBy('users.unit_id');

            $this->applyDateRange(
                $unitAttendanceQuery,
                'attendances.signed_at',
                $request->input('start_date'),
                $request->input('end_date'),
            );

            $attendanceCountsByUnit = $unitAttendanceQuery->pluck('total', 'users.unit_id');

            // Paginate unit participation stats (5 per page) for Administrator
            $unitStats = Unit::active()
                ->withCount('users')
                ->orderBy('nama_unit')
                ->paginate(5, ['*'], 'page_units')
                ->withQueryString()
                ->through(function ($unit) use ($attendanceCountsByUnit) {
                    return [
                        'unit' => $unit,
                        'attendances_count' => $attendanceCountsByUnit[$unit->id] ?? 0,
                        'total_users' => $unit->users_count,
                    ];
                });
        } elseif ($user->isAdmin() && $user->unit_id) {
            $memberAttendanceQuery = Attendance::query()
                ->whereHas('user', function ($q) use ($user) {
                    $q->where('unit_id', $user->unit_id);
                })
                ->selectRaw('user_id, count(*) as total')
                ->groupBy('user_id');

            $this->applyDateRange(
                $memberAttendanceQuery,
                'signed_at',
                $request->input('start_date'),
                $request->input('end_date'),
            );

            $memberAttendanceCounts = $memberAttendanceQuery->pluck('total', 'user_id');

            $memberStats = User::forUnit($user->unit_id)
                ->orderBy('name')
                ->paginate(5, ['*'], 'page_members')
                ->withQueryString()
                ->through(function ($member) use ($memberAttendanceCounts) {
                    return [
                        'user' => $member,
                        'attendances_count' => $memberAttendanceCounts[$member->id] ?? 0,
                    ];
                });
        }

        return view('reports.index', compact(
            'agendas',
            'units',
            'totalAgendas',
            'completedAgendas',
            'ongoingAgendas',
            'scheduledAgendas',
            'totalPresensi',
            'avgPresensi',
            'unitStats',
            'memberStats',
            'formatStats',
            'governanceRate',
            'countWithNotulen',
            'attendanceRate',
            'attendanceRateSubtext',
            'user'
        ));
    }

    /**
     * Display a comprehensive recap for a single agenda.
     */
    public function show(Agenda $agenda): View
    {
        Gate::authorize('view', $agenda);

        $agenda->load([
            'creator.unit',
            'pimpinan.unit',
            'notulis.unit',
            'units',
            'invitedUsers.unit',
        ]);

        // Preload attendance records for effective pimpinan & notulis so signature blocks do not issue extra queries
        $roleUserIds = array_values(array_unique(array_filter([
            $agenda->effective_pimpinan?->id,
            $agenda->effective_notulis?->id,
        ])));

        if (! empty($roleUserIds)) {
            $agenda->load([
                'attendances' => fn ($q) => $q->whereIn('user_id', $roleUserIds),
            ]);
            $agenda->attendances->each(fn ($att) => $att->setRelation('agenda', $agenda));
        } else {
            $agenda->setRelation('attendances', collect());
        }

        $attendances = $agenda->attendances()
            ->with('user.unit')
            ->orderBy('signed_at', 'asc')
            ->paginate(10, ['*'], 'page_attendees')
            ->withQueryString();
        $attendances->getCollection()->each(fn ($att) => $att->setRelation('agenda', $agenda));

        $documentations = $agenda->documentations()
            ->latest('id')
            ->paginate(6, ['*'], 'page_docs')
            ->withQueryString();

        return view('reports.show', compact('agenda', 'attendances', 'documentations'));
    }

    /**
     * Export Official Meeting Minutes & Attendance to PDF (Direct Binary Download).
     *
     * Uses headless LibreOffice to produce a true PDF binary. If LibreOffice is
     * unavailable, falls back to an inline print-ready HTML response so the user
     * can still use the browser's built-in "Save as PDF" / Ctrl+P.
     */
    public function exportPdf(Request $request, Agenda $agenda, PdfExportService $pdfService): Response|RedirectResponse
    {
        Gate::authorize('view', $agenda);

        $config = $this->extractReportConfig($request, $agenda);

        ActivityLogger::log(
            type: 'EXPORT_PDF',
            description: "Mengekspor Berita Acara & Daftar Hadir PDF untuk agenda: {$agenda->judul_rapat}",
            targetModel: Agenda::class,
            targetId: $agenda->id,
            properties: ['custom_config' => !empty($request->all())]
        );

        try {
            // Always attempt true binary PDF download first (via LibreOffice headless)
            return $pdfService->exportBinaryPdf($agenda, $config);
        } catch (\Throwable $e) {
            Log::warning('Gagal mengekspor PDF biner (LibreOffice tidak tersedia), menggunakan fallback cetak browser: ' . $e->getMessage(), [
                'agenda_id' => $agenda->id,
            ]);

            try {
                // Fallback: return inline print-ready HTML — user can Ctrl+P / Save as PDF
                return $pdfService->exportBeritaAcara($agenda, $config);
            } catch (\Throwable $fallbackException) {
                Log::error('Gagal memuat fallback pratinjau PDF: ' . $fallbackException->getMessage(), [
                    'agenda_id' => $agenda->id,
                    'exception' => $fallbackException,
                ]);

                return $this->redirectExportFailure(
                    $request,
                    $agenda,
                    'Terjadi kesalahan saat mengekspor dokumen Berita Acara. Silakan periksa kelengkapan data agenda dan coba kembali.'
                );
            }
        }
    }

    /**
     * Export Official Meeting Minutes & Attendance to Microsoft Word (.docx with .doc fallback).
     */
    public function exportWord(
        Request $request,
        Agenda $agenda,
        DocxExportService $docxService,
        WordExportService $wordExportService
    ): Response|RedirectResponse {
        Gate::authorize('view', $agenda);

        $config = $this->extractReportConfig($request, $agenda);

        try {
            ActivityLogger::log(
                type: 'EXPORT_WORD',
                description: "Mengekspor Berita Acara & Daftar Hadir Microsoft Word (.doc) untuk agenda: {$agenda->judul_rapat}",
                targetModel: Agenda::class,
                targetId: $agenda->id,
                properties: ['custom_config' => !empty($request->all())]
            );
        } catch (\Throwable $logEx) {
            Log::warning('Gagal mencatat log aktivitas ekspor Word: ' . $logEx->getMessage());
        }

        try {
            return $docxService->exportBeritaAcara($agenda, $config);
        } catch (\Throwable $e) {
            Log::warning('Gagal mengekspor OpenXML Word (.docx), mencoba fallback format Word (.doc): ' . $e->getMessage(), [
                'agenda_id' => $agenda->id,
                'exception' => $e,
            ]);

            try {
                return $wordExportService->exportBeritaAcara($agenda, $config);
            } catch (\Throwable $fallbackException) {
                Log::error('Gagal mengekspor dokumen Word fallback (.doc): ' . $fallbackException->getMessage(), [
                    'agenda_id' => $agenda->id,
                    'exception' => $fallbackException,
                ]);

                return $this->redirectExportFailure(
                    $request,
                    $agenda,
                    'Terjadi kesalahan saat mengekspor dokumen Word. Silakan coba kembali atau gunakan format Pratinjau Dokumen.'
                );
            }
        }
    }

    /**
     * Redirect failed document export back to the originating detail page without risking an export loop.
     */
    private function redirectExportFailure(Request $request, Agenda $agenda, string $message): RedirectResponse
    {
        $referer = (string) $request->headers->get('referer', '');
        if ($referer !== '') {
            $refererHost = parse_url($referer, PHP_URL_HOST);
            $refererPath = (string) parse_url($referer, PHP_URL_PATH);

            if (
                ($refererHost === null || $refererHost === $request->getHost())
                && str_contains($refererPath, '/admin/agendas/')
            ) {
                return redirect()->route('admin.agendas.show', $agenda)->with('error', $message);
            }
        }

        return redirect()->route('admin.reports.show', $agenda)->with('error', $message);
    }

    /**
     * Reset report configuration for an agenda to system defaults.
     */
    public function resetReportConfig(Agenda $agenda): RedirectResponse
    {
        Gate::authorize('update', $agenda);

        $agenda->update(['report_config' => null]);

        ActivityLogger::log(
            type: 'RESET_REPORT_CONFIG',
            description: "Konfigurasi dokumen laporan agenda '{$agenda->judul_rapat}' direset ke standar sistem.",
            targetModel: Agenda::class,
            targetId: $agenda->id
        );

        return back()->with('success', 'Konfigurasi dokumen laporan berhasil direset ke standar sistem.');
    }

    /**
     * Extract, validate, and sanitize custom report configuration from request.
     *
     * @return array<string,mixed>
     */
    protected function extractReportConfig(Request $request, Agenda $agenda): array
    {
        // If request is standard GET with no query parameters, use resolved config
        if ($request->isMethod('get') && empty($request->query())) {
            return $agenda->resolved_report_config;
        }

        $configService = app(ReportConfigService::class);
        $config = $configService->extractFromRequest($request, $agenda);

        // Check if authorized user opted to save this config as default for this agenda
        if ($request->boolean('save_as_default') && Gate::allows('manageMinutes', $agenda)) {
            $agenda->update(['report_config' => $config]);
        }

        return $config;
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
     * Export summary table of meetings to CSV / Excel spreadsheet.
     */
    public function exportSummaryCsv(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', Agenda::class);

        $user = Auth::user();
        $query = Agenda::visibleTo($user)
            ->with([
                'creator:id,name',
                'pimpinan:id,name',
                'notulis:id,name',
            ])
            ->withCount('attendances');

        ActivityLogger::log(
            type: 'EXPORT_CSV',
            description: "Mengekspor rekapitulasi data agenda rapat kedinasan ke format CSV / Excel",
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

            // CSV Header Row
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

            /*
             * chunk() is used instead of cursor() on purpose.
             *
             * Eloquent's cursor() never invokes eagerLoadRelations(), so the
             * `with('creator')` above was silently discarded and every row issued
             * its own `SELECT * FROM users WHERE id = ?` — one query per agenda.
             * chunk() goes through get(), which does honour eager loading, keeps
             * the requested ordering, and still bounds memory to $chunkSize rows.
             */
            $query->orderBy('waktu_mulai', 'desc')->chunk(500, function ($agendas) use ($file, &$index) {
                foreach ($agendas as $agenda) {
                    $index++;

                    // Sanitize potential CSV Formula Injection characters (=, +, -, @, \t, \r)
                    $safeTitle = $this->sanitizeCsvValue($agenda->judul_rapat);
                    $safeLocation = $this->sanitizeCsvValue($agenda->lokasi_ruang ?? 'Daring / Online');
                    $safeCreator = $this->sanitizeCsvValue($agenda->creator?->name ?? 'Sistem');

                    // Status Rapat dalam Bahasa Indonesia Baku
                    $statusRapat = match ($agenda->status) {
                        'completed' => 'Selesai',
                        'ongoing' => 'Sedang Berlangsung',
                        'scheduled' => 'Terjadwal',
                        'cancelled' => 'Dibatalkan',
                        default => ucfirst($agenda->status),
                    };

                    // Status Notulensi Resmi
                    $hasNotulen = !empty(trim(strip_tags((string) $agenda->notulensi)));
                    $statusNotulensi = match (true) {
                        $hasNotulen => 'Lengkap',
                        $agenda->status === 'completed' => 'Belum Diisi',
                        $agenda->status === 'ongoing' => 'Sedang Berlangsung',
                        default => 'Belum Dimulai',
                    };

                    // Pemimpin Rapat dan Notulis Rapat
                    $safePimpinan = $this->sanitizeCsvValue($agenda->nama_pimpinan);
                    $safeNotulis = $this->sanitizeCsvValue($agenda->nama_notulis);

                    fputcsv($file, [
                        $index,
                        $safeTitle,
                        ucfirst($agenda->jenis_rapat),
                        strtoupper($agenda->tipe_rapat),
                        $safeLocation,
                        $agenda->waktu_mulai->format('d/m/Y H:i'),
                        $agenda->waktu_selesai ? $agenda->waktu_selesai->format('d/m/Y H:i') : 'Hingga Selesai',
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
     * Sanitize string values to prevent CSV / Formula Injection (CWE-1236).
     */
    private function sanitizeCsvValue(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        // If string starts with formula characters, prepend single quote
        if (preg_match('/^[=\+\-@\t\r]/', $value)) {
            return "'" . $value;
        }

        return $value;
    }
}
