<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\Unit;
use App\Services\ActivityLogger;
use App\Services\PdfExportService;
use App\Services\WordExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

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

        // Filter: Unit (Superadmin only or Admin's unit)
        if ($unitId = $request->input('unit_id')) {
            $query->where(function ($q) use ($unitId) {
                $q->where('is_all_units', true)
                  ->orWhereHas('units', function ($sub) use ($unitId) {
                      $sub->where('units.id', $unitId);
                  });
            });
        }

        // Filter: Format
        if ($tipe = $request->input('tipe')) {
            $query->where('tipe_rapat', $tipe);
        }

        $agendas = $query->orderBy('waktu_mulai', 'desc')->paginate(10)->withQueryString();

        // Aggregate Statistics (from filtered or overall scoped set)
        $allScopedAgendas = Agenda::visibleTo($user)->with('attendances')->get();
        $totalAgendas = $allScopedAgendas->count();
        $completedAgendas = $allScopedAgendas->where('status', 'completed')->count();
        $ongoingAgendas = $allScopedAgendas->where('status', 'ongoing')->count();
        $totalPresensi = $allScopedAgendas->sum(fn ($a) => $a->attendances->count());
        $avgPresensi = $totalAgendas > 0 ? round($totalPresensi / $totalAgendas, 1) : 0;

        // Unit breakdown statistics
        $units = Unit::active()->withCount('users')->get();
        $unitStats = $units->map(function ($unit) {
            $unitAttendancesCount = Attendance::whereHas('user', function ($q) use ($unit) {
                $q->where('unit_id', $unit->id);
            })->count();

            return [
                'unit' => $unit,
                'attendances_count' => $unitAttendancesCount,
                'total_users' => $unit->users_count,
            ];
        });

        return view('reports.index', compact(
            'agendas',
            'units',
            'totalAgendas',
            'completedAgendas',
            'ongoingAgendas',
            'totalPresensi',
            'avgPresensi',
            'unitStats',
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
            'attendances.user.unit',
            'documentations',
        ]);

        return view('reports.show', compact('agenda'));
    }

    /**
     * Export Official Meeting Minutes & Attendance to PDF (Print View).
     */
    public function exportPdf(Agenda $agenda, PdfExportService $pdfService): Response
    {
        Gate::authorize('view', $agenda);

        ActivityLogger::log(
            'export_pdf',
            "Mengekspor Berita Acara & Daftar Hadir PDF untuk agenda: {$agenda->judul_rapat}",
            Agenda::class,
            $agenda->id
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
            'export_word',
            "Mengekspor Berita Acara & Daftar Hadir Microsoft Word (.doc) untuk agenda: {$agenda->judul_rapat}",
            Agenda::class,
            $agenda->id
        );

        return $wordService->exportBeritaAcara($agenda);
    }

    /**
     * Export summary table of meetings to CSV / Excel spreadsheet.
     */
    public function exportSummaryCsv(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user = Auth::user();
        $query = Agenda::visibleTo($user)->with(['creator', 'attendances']);

        ActivityLogger::log(
            'export_csv',
            "Mengekspor rekapitulasi data agenda rapat kedinasan ke format CSV / Excel",
            Agenda::class
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

        $agendas = $query->orderBy('waktu_mulai', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="Rekapitulasi_Agenda_LLDIKTI_' . date('Ymd_His') . '.csv"',
        ];

        $callback = function () use ($agendas) {
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

            foreach ($agendas as $index => $a) {
                fputcsv($file, [
                    $index + 1,
                    $a->judul_rapat,
                    ucfirst($a->jenis_rapat),
                    strtoupper($a->tipe_rapat),
                    $a->lokasi_ruang ?? 'Daring / Online',
                    $a->waktu_mulai->format('d/m/Y H:i'),
                    $a->waktu_selesai->format('d/m/Y H:i'),
                    ucfirst($a->status),
                    $a->creator->name,
                    $a->attendances->count(),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
