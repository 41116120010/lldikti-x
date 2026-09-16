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
use Illuminate\Support\Facades\Storage;
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
     * Export Official Meeting Minutes & Attendance to PDF (Print View).
     */
    public function exportPdf(Request $request, Agenda $agenda, PdfExportService $pdfService): Response
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

        return $pdfService->exportBeritaAcara($agenda, $config);
    }

    /**
     * Export Official Meeting Minutes & Attendance to Microsoft Word (.doc).
     */
    public function exportWord(Request $request, Agenda $agenda, WordExportService $wordService): Response
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

        return $wordService->exportBeritaAcara($agenda, $config);
    }

    /**
     * Reset report configuration for an agenda to system defaults.
     */
    public function resetReportConfig(Agenda $agenda)
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
     */
    protected function extractReportConfig(Request $request, Agenda $agenda): array
    {
        // If request is standard GET with no query parameters, use resolved config
        if ($request->isMethod('get') && empty($request->query())) {
            return $agenda->resolved_report_config;
        }

        // Process custom logo upload or reset
        $customLogoPath = $agenda->resolved_report_config['custom_logo_path'] ?? null;

        if ($request->boolean('reset_custom_logo')) {
            if ($customLogoPath && Storage::disk('public')->exists($customLogoPath)) {
                Storage::disk('public')->delete($customLogoPath);
            }
            $customLogoPath = null;
        } elseif ($request->hasFile('custom_logo')) {
            $request->validate([
                'custom_logo' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:512'],
            ], [
                'custom_logo.image' => 'Berkas logo harus berupa gambar.',
                'custom_logo.mimes' => 'Format logo harus berupa JPEG, PNG, atau WebP.',
                'custom_logo.max' => 'Ukuran logo maksimal 512 KB.',
            ]);

            if ($customLogoPath && Storage::disk('public')->exists($customLogoPath)) {
                Storage::disk('public')->delete($customLogoPath);
            }

            $customLogoPath = $request->file('custom_logo')->store("agendas/{$agenda->id}/logos", 'public');
        } elseif ($request->filled('custom_logo_path')) {
            $customLogoPath = strip_tags($request->input('custom_logo_path'));
        }

        $config = [
            // Header
            'show_kop' => $request->boolean('show_kop', true),
            'show_logo' => $request->boolean('show_logo', true),
            'custom_logo_path' => $customLogoPath,
            'instansi_induk' => strip_tags($request->input('instansi_induk', 'KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI')),
            'instansi_pelaksana' => strip_tags($request->input('instansi_pelaksana', 'LEMBAGA LAYANAN PENDIDIKAN TINGGI (LLDIKTI) WILAYAH X')),
            'alamat_kontak' => strip_tags($request->input('alamat_kontak', 'Jalan Khatib Sulaiman, Padang, Sumatera Barat • Laman: lldikti10.kemdikbud.go.id')),
            'document_title' => strip_tags($request->input('document_title', 'BERITA ACARA DAN DAFTAR HADIR RAPAT')),
            'show_document_number' => $request->boolean('show_document_number', true),
            'document_number' => strip_tags($request->input('document_number', 'BA-RAPAT/' . ($agenda->waktu_mulai ? $agenda->waktu_mulai->format('Y') : date('Y')) . '/' . str_pad($agenda->id, 4, '0', STR_PAD_LEFT))),

            // Content
            'show_meeting_info' => $request->boolean('show_meeting_info', true),
            'custom_agenda_title' => strip_tags($request->input('custom_agenda_title', $agenda->judul_rapat)),
            'custom_location' => strip_tags($request->input('custom_location', $agenda->lokasi_ruang ?? 'Daring (Online Meeting)')),
            'show_attendees' => $request->boolean('show_attendees', true),
            'show_nip' => $request->boolean('show_nip', true),
            'show_unit' => $request->boolean('show_unit', true),
            'show_attendance_time' => $request->boolean('show_attendance_time', true),
            'show_selfie_photos' => $request->boolean('show_selfie_photos', true),
            'show_attendee_signatures' => $request->boolean('show_attendee_signatures', true),
            'show_notulensi' => $request->boolean('show_notulensi', true),
            'show_kesimpulan' => $request->boolean('show_kesimpulan', true),
            'show_documentation' => $request->boolean('show_documentation', true),

            // Footer
            'signing_city' => strip_tags($request->input('signing_city', 'Padang')),
            'signing_date' => strip_tags($request->input('signing_date', $agenda->waktu_mulai ? $agenda->waktu_mulai->translatedFormat('d F Y') : now()->translatedFormat('d F Y'))),
            'signer1_role' => strip_tags($request->input('signer1_role', 'Pemimpin Rapat')),
            'signer1_name' => strip_tags($request->input('signer1_name') ?: $agenda->nama_pimpinan),
            'signer1_nip' => strip_tags($request->input('signer1_nip') ?: (($agenda->nip_pimpinan && $agenda->nip_pimpinan !== '-') ? $agenda->nip_pimpinan : '-')),
            'show_signer1_signature' => $request->boolean('show_signer1_signature', true),
            'signer2_role' => strip_tags($request->input('signer2_role', 'Notulis Rapat')),
            'signer2_name' => strip_tags($request->input('signer2_name') ?: $agenda->nama_notulis),
            'signer2_nip' => strip_tags($request->input('signer2_nip') ?: (($agenda->nip_notulis && $agenda->nip_notulis !== '-') ? $agenda->nip_notulis : '-')),
            'show_signer2_signature' => $request->boolean('show_signer2_signature', true),
            'show_signer3' => $request->boolean('show_signer3', false),
            'signer3_role' => strip_tags($request->input('signer3_role', 'Kepala Lembaga Layanan Pendidikan Tinggi Wilayah X')),
            'signer3_name' => strip_tags($request->input('signer3_name', '')),
            'signer3_nip' => strip_tags($request->input('signer3_nip', '-')),
            'show_footer_note' => $request->boolean('show_footer_note', true),
            'footer_note' => strip_tags($request->input('footer_note', 'Dokumen ini diterbitkan secara resmi melalui Sistem Informasi Presensi Rapat (SIPERAPAT) LLDIKTI Wilayah X')),
        ];

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
