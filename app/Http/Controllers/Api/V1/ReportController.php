<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\FiltersByDateRange;
use App\Http\Resources\AgendaResource;
use App\Models\Agenda;
use App\Models\Attendance;
use App\Services\ActivityLogger;
use App\Services\DocxExportService;
use App\Services\PdfExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    use FiltersByDateRange;

    /**
     * Get aggregate statistics and summaries for reporting.
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Agenda::visibleTo($user);

        $this->applyDateRange(
            $query,
            'waktu_mulai',
            $request->query('start_date'),
            $request->query('end_date'),
        );
        if ($request->filled('tipe_rapat')) {
            $query->where('tipe_rapat', $request->query('tipe_rapat'));
        }

        $agendaCounts = (clone $query)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN status = 'ongoing' THEN 1 ELSE 0 END) AS ongoing")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed")
            ->first();

        $totalAttendances = Attendance::whereHas('agenda', function ($q) use ($user) {
            $q->visibleTo($user);
        })->count();

        $totalAgendas = (int) ($agendaCounts->total ?? 0);
        $avgAttendances = $totalAgendas > 0 ? round($totalAttendances / $totalAgendas, 1) : 0;

        return response()->json([
            'success' => true,
            'message' => 'Ringkasan laporan berhasil diambil.',
            'data' => [
                'total_agendas' => $totalAgendas,
                'completed_agendas' => (int) ($agendaCounts->completed ?? 0),
                'ongoing_agendas' => (int) ($agendaCounts->ongoing ?? 0),
                'total_attendances' => $totalAttendances,
                'avg_attendances_per_agenda' => $avgAttendances,
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
                'export_links' => [
                    'pdf' => url("/api/v1/reports/agendas/{$agenda->id}/export/pdf"),
                    'word' => url("/api/v1/reports/agendas/{$agenda->id}/export/word"),
                ],
            ],
        ]);
    }

    /**
     * Download or stream protected PDF meeting document.
     */
    public function exportPdf(Request $request, Agenda $agenda, PdfExportService $pdfService): SymfonyResponse
    {
        Gate::authorize('view', $agenda);

        ActivityLogger::log(
            type: 'EXPORT_PDF_API',
            description: "Mengekspor Berita Acara & Daftar Hadir PDF via API Mobile untuk agenda: {$agenda->judul_rapat}",
            targetModel: Agenda::class,
            targetId: $agenda->id
        );

        $config = $agenda->resolved_report_config;

        try {
            return $pdfService->exportBinaryPdf($agenda, $config);
        } catch (\Throwable) {
            return $pdfService->exportBeritaAcara($agenda, $config);
        }
    }

    /**
     * Download or stream protected Microsoft Word (.doc) meeting document.
     */
    public function exportWord(Request $request, Agenda $agenda, DocxExportService $docxService): SymfonyResponse
    {
        Gate::authorize('view', $agenda);

        ActivityLogger::log(
            type: 'EXPORT_WORD_API',
            description: "Mengekspor Berita Acara & Daftar Hadir Word via API Mobile untuk agenda: {$agenda->judul_rapat}",
            targetModel: Agenda::class,
            targetId: $agenda->id
        );

        $config = $agenda->resolved_report_config;

        return $docxService->exportBeritaAcara($agenda, $config);
    }

    /**
     * Export summary table of meetings to CSV spreadsheet for mobile clients.
     */
    public function exportSummaryCsv(Request $request): StreamedResponse
    {
        $user = $request->user();
        $query = Agenda::visibleTo($user)->with('creator:id,name')->withCount('attendances');

        ActivityLogger::log(
            type: 'EXPORT_CSV_API',
            description: "Mengekspor rekapitulasi data agenda rapat ke format CSV via API Mobile",
            targetModel: Agenda::class
        );

        $this->applyDateRange(
            $query,
            'waktu_mulai',
            $request->query('start_date'),
            $request->query('end_date'),
        );

        if ($status = $request->query('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }
        if ($unitId = $request->query('unit_id')) {
            if ($user->isAdministrator()) {
                $query->where(function ($q) use ($unitId) {
                    $q->where('is_all_units', true)
                      ->orWhereHas('units', function ($sub) use ($unitId) {
                          $sub->where('units.id', $unitId);
                      });
                });
            }
        }
        if ($tipe = $request->query('tipe')) {
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

            $query->orderBy('waktu_mulai', 'desc')->chunk(500, function ($agendas) use ($file, &$index) {
                foreach ($agendas as $agenda) {
                    $index++;

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

