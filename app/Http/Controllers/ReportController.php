<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersByDateRange;
use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\Unit;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\PdfExportService;
use App\Services\ReportConfigService;
use App\Services\DocxExportService;
use App\Services\WordExportService;
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
        $user = Auth::user();

        // Base scoped agenda query.
        // 'attendances' is intentionally NOT eager loaded: the view only needs the
        // tally, which withCount() resolves in a single subquery. Loading the full
        // relation would drag selfie_path, signature_path, ip_address and
        // user_agent (TEXT) for every attendee just to render a number.
        $query = Agenda::visibleTo($user)
            ->with(['creator.unit', 'units'])
            ->withCount('attendances');

        // Filter: Date Range (timestamp comparison — keeps the index usable)
        $this->applyDateRange(
            $query,
            'waktu_mulai',
            $request->input('start_date'),
            $request->input('end_date'),
        );

        // Filter: Status
        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        // Filter: Unit (Superadmin or Admin Unit filter)
        if ($unitId = $request->input('unit_id')) {
            if ($user->isAdministrator()) {
                $query->where(function ($q) use ($unitId) {
                    $q->where('is_all_units', true)
                      ->orWhereHas('units', function ($sub) use ($unitId) {
                          $sub->where('units.id', $unitId);
                      });
                });
            }
        }

        // Filter: Format
        if ($tipe = $request->input('tipe')) {
            $query->where('tipe_rapat', $tipe);
        }

        $agendas = $query->orderBy('waktu_mulai', 'desc')->paginate(10)->withQueryString();

        // Direct database aggregate calculations (O(1) memory footprint).
        // The three simple status tallies collapse into a single pass; `upcoming`
        // keeps its own query because its predicate is not a plain equality.
        $baseScopedQuery = Agenda::visibleTo($user);
        $agendaCounts = (clone $baseScopedQuery)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed")
            ->selectRaw("SUM(CASE WHEN status = 'ongoing' THEN 1 ELSE 0 END) AS ongoing")
            ->first();

        $totalAgendas = (int) ($agendaCounts->total ?? 0);
        $completedAgendas = (int) ($agendaCounts->completed ?? 0);
        $ongoingAgendas = (int) ($agendaCounts->ongoing ?? 0);

        // Correlated EXISTS instead of `whereIn('agenda_id', <subquery>)`, which
        // forced MySQL to materialise the entire scoped agenda id list.
        $totalPresensi = Attendance::whereHas('agenda', fn ($q) => $q->visibleTo($user))->count();
        $avgPresensi = $totalAgendas > 0 ? round($totalPresensi / $totalAgendas, 1) : 0;

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
            'totalPresensi',
            'avgPresensi',
            'unitStats',
            'memberStats',
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
        ]);

        $attendances = $agenda->attendances()
            ->with('user.unit')
            ->orderBy('signed_at', 'asc')
            ->paginate(10, ['*'], 'page_attendees')
            ->withQueryString();

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

                return redirect()->route('admin.agendas.show', $agenda)
                    ->with('error', 'Terjadi kesalahan saat mengekspor dokumen Berita Acara. Silakan periksa kelengkapan data agenda dan coba kembali.');
            }
        }
    }

    /**
     * Export Official Meeting Minutes & Attendance to Microsoft Word (.doc).
     */
    public function exportWord(Request $request, Agenda $agenda, DocxExportService $docxService): Response|RedirectResponse
    {
        Gate::authorize('view', $agenda);

        $config = $this->extractReportConfig($request, $agenda);

        ActivityLogger::log(
            type: 'EXPORT_WORD',
            description: "Mengekspor Berita Acara & Daftar Hadir Microsoft Word (.doc) untuk agenda: {$agenda->judul_rapat}",
            targetModel: Agenda::class,
            targetId: $agenda->id,
            properties: ['custom_config' => !empty($request->all())]
        );

        try {
            return $docxService->exportBeritaAcara($agenda, $config);
        } catch (\Throwable $e) {
            Log::error('Gagal mengekspor dokumen Microsoft Word (.doc): ' . $e->getMessage(), [
                'agenda_id' => $agenda->id,
                'exception' => $e,
            ]);

            return redirect()->route('admin.agendas.show', $agenda)
                ->with('error', 'Terjadi kesalahan saat mengekspor dokumen Word. Silakan coba kembali atau gunakan format Pratinjau Dokumen.');
        }
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

        // Check if admin opted to save this config as default for this agenda
        if ($request->boolean('save_as_default') && (Auth::user()?->isAdministrator() || Auth::user()?->isAdmin())) {
            $agenda->update(['report_config' => $config]);
        }

        return $config;
    }

    /**
     * Export summary table of meetings to CSV / Excel spreadsheet.
     */
    public function exportSummaryCsv(Request $request): StreamedResponse
    {
        $user = Auth::user();
        $query = Agenda::visibleTo($user)->with('creator:id,name')->withCount('attendances');

        ActivityLogger::log(
            type: 'EXPORT_CSV',
            description: "Mengekspor rekapitulasi data agenda rapat kedinasan ke format CSV / Excel",
            targetModel: Agenda::class
        );

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
            if ($user->isAdministrator()) {
                $query->where(function ($q) use ($unitId) {
                    $q->where('is_all_units', true)
                      ->orWhereHas('units', function ($sub) use ($unitId) {
                          $sub->where('units.id', $unitId);
                      });
                });
            }
        }
        if ($tipe = $request->input('tipe')) {
            $query->where('tipe_rapat', $tipe);
        }

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
                'Status',
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

                    fputcsv($file, [
                        $index,
                        $safeTitle,
                        ucfirst($agenda->jenis_rapat),
                        strtoupper($agenda->tipe_rapat),
                        $safeLocation,
                        $agenda->waktu_mulai->format('d/m/Y H:i'),
                        $agenda->waktu_selesai ? $agenda->waktu_selesai->format('d/m/Y H:i') : '-',
                        ucfirst($agenda->status),
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
