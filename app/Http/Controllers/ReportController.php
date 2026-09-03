<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\Unit;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\PdfExportService;
use App\Services\WordExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Display the Executive Reporting & Meeting Recap Dashboard.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // Base scoped agenda query
        $query = Agenda::visibleTo($user)->with(['creator.unit', 'units', 'attendances']);

        // Filter: Date Range
        if ($startDate = $request->input('start_date')) {
            $query->whereDate('waktu_mulai', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->whereDate('waktu_mulai', '<=', $endDate);
        }

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

        // Direct database aggregate calculations (O(1) memory footprint)
        $baseScopedQuery = Agenda::visibleTo($user);
        $totalAgendas = (clone $baseScopedQuery)->count();
        $completedAgendas = (clone $baseScopedQuery)->where('status', 'completed')->count();
        $ongoingAgendas = (clone $baseScopedQuery)->where('status', 'ongoing')->count();
        $totalPresensi = Attendance::whereIn('agenda_id', (clone $baseScopedQuery)->select('id'))->count();
        $avgPresensi = $totalAgendas > 0 ? round($totalPresensi / $totalAgendas, 1) : 0;

        // Eliminate N+1 query: Fetch unit attendance stats with a single group-by aggregation
        $attendanceCountsByUnit = Attendance::query()
            ->join('users', 'attendances.user_id', '=', 'users.id')
            ->whereNotNull('users.unit_id')
            ->selectRaw('users.unit_id, count(*) as total')
            ->groupBy('users.unit_id')
            ->pluck('total', 'users.unit_id');

        $units = $user->isAdministrator() ? Unit::active()->orderBy('nama_unit')->get() : collect();
        $unitStats = null;
        $memberStats = null;

        if ($user->isAdministrator()) {
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
            // Paginate unit member participation stats (5 per page) for Admin Unit
            $memberAttendanceCounts = Attendance::query()
                ->whereHas('user', function ($q) use ($user) {
                    $q->where('unit_id', $user->unit_id);
                })
                ->selectRaw('user_id, count(*) as total')
                ->groupBy('user_id')
                ->pluck('total', 'user_id');

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
     * Export Official Meeting Minutes & Attendance to PDF (Print View).
     */
    public function exportPdf(Agenda $agenda, PdfExportService $pdfService): Response
    {
        Gate::authorize('view', $agenda);

        ActivityLogger::log(
            type: 'EXPORT_PDF',
            description: "Mengekspor Berita Acara & Daftar Hadir PDF untuk agenda: {$agenda->judul_rapat}",
            targetModel: Agenda::class,
            targetId: $agenda->id
        );

        return $pdfService->exportBeritaAcara($agenda);
    }

    /**
     * Export Official Meeting Minutes & Attendance to Microsoft Word (.doc).
     */
    public function exportWord(Agenda $agenda, WordExportService $wordService): Response
    {
        Gate::authorize('view', $agenda);

        ActivityLogger::log(
            type: 'EXPORT_WORD',
            description: "Mengekspor Berita Acara & Daftar Hadir Microsoft Word (.doc) untuk agenda: {$agenda->judul_rapat}",
            targetModel: Agenda::class,
            targetId: $agenda->id
        );

        return $wordService->exportBeritaAcara($agenda);
    }

    /**
     * Export summary table of meetings to CSV / Excel spreadsheet.
     */
    public function exportSummaryCsv(Request $request): StreamedResponse
    {
        $user = Auth::user();
        $query = Agenda::visibleTo($user)->with('creator')->withCount('attendances');

        ActivityLogger::log(
            type: 'EXPORT_CSV',
            description: "Mengekspor rekapitulasi data agenda rapat kedinasan ke format CSV / Excel",
            targetModel: Agenda::class
        );

        if ($startDate = $request->input('start_date')) {
            $query->whereDate('waktu_mulai', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->whereDate('waktu_mulai', '<=', $endDate);
        }
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
            // Use cursor() to stream records efficiently without memory bloat
            foreach ($query->orderBy('waktu_mulai', 'desc')->cursor() as $agenda) {
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
