<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\Unit;
use App\Models\User;
use Tests\TestCase;

class ReportAndExportTest extends TestCase
{
    public function test_superadmin_can_view_reports_dashboard(): void
    {
        $superadmin = User::where('role', 'administrator')->first();

        $response = $this->actingAs($superadmin)->get('/admin/reports');

        $response->assertStatus(200);
        $response->assertSee('Rekapitulasi & Laporan Rapat');
        $response->assertSee('Partisipasi Per Unit Kerja');
    }

    public function test_admin_unit_can_view_reports_dashboard(): void
    {
        $adminAkm = User::where('username', 'admin_akademik')->first();

        $response = $this->actingAs($adminAkm)->get('/admin/reports');

        $response->assertStatus(200);
        $response->assertSee('Daftar Rekapitulasi Rapat');
    }

    public function test_reports_filtering_by_status(): void
    {
        $superadmin = User::where('role', 'administrator')->first();

        $response = $this->actingAs($superadmin)->get('/admin/reports?status=completed');

        $response->assertStatus(200);
    }

    public function test_user_can_export_pdf_berita_acara(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        $response = $this->actingAs($superadmin)->get("/admin/reports/{$agenda->id}/export/pdf");

        $response->assertStatus(200);
        if (str_contains($response->headers->get('Content-Type', ''), 'application/pdf')) {
            $this->assertStringStartsWith('%PDF-', $response->getContent());
        } else {
            $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
            $response->assertSee('BERITA ACARA DAN DAFTAR HADIR RAPAT');
            $response->assertSee('LEMBAGA LAYANAN PENDIDIKAN TINGGI (LLDIKTI) WILAYAH X');
        }
    }

    public function test_user_can_export_word_berita_acara(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        $response = $this->actingAs($superadmin)->get("/admin/reports/{$agenda->id}/export/word");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.ms-word; charset=UTF-8');
        $this->assertStringContainsString('.doc', $response->headers->get('Content-Disposition'));
    }

    public function test_user_can_export_summary_csv(): void
    {
        $superadmin = User::where('role', 'administrator')->first();

        $response = $this->actingAs($superadmin)->get('/admin/reports/summary/csv');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('.csv', $response->headers->get('Content-Disposition'));
    }

    public function test_user_can_view_single_report_recap_page(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        $response = $this->actingAs($superadmin)->get("/admin/reports/{$agenda->id}");

        $response->assertStatus(200);
        $response->assertSee('Rekapitulasi Kehadiran & Dokumen Rapat');
        $response->assertSee($agenda->judul_rapat);
        $response->assertSee('Daftar Rekapitulasi');
        $response->assertSee('Unduh PDF (.pdf)');
        $response->assertSee('Unduh Format Word (.doc)');
        $response->assertSee('Kelola Agenda');
        $response->assertSee('Notulensi Rapat');
        $response->assertSee('Dokumentasi Foto');
        $response->assertSee('Surat Edaran');
    }

    public function test_reports_filtering_with_date_and_format_parameters(): void
    {
        $superadmin = User::where('role', 'administrator')->first();

        $response = $this->actingAs($superadmin)->get('/admin/reports?start_date=2026-01-01&end_date=2026-12-31&tipe=offline&status=all');

        $response->assertStatus(200);
        $response->assertSee('Daftar Rekapitulasi Rapat');
    }

    public function test_superadmin_can_filter_reports_by_unit_id(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $unit = Unit::first();

        $response = $this->actingAs($superadmin)->get("/admin/reports?unit_id={$unit->id}");

        $response->assertStatus(200);
        $response->assertSee('Daftar Rekapitulasi Rapat');
    }

    public function test_csv_export_sanitizes_formula_injection_characters(): void
    {
        $superadmin = User::where('role', 'administrator')->first();

        // Create an agenda with a potential formula injection title
        $agenda = Agenda::create([
            'created_by' => $superadmin->id,
            'judul_rapat' => '=cmd|"/C calc"!A0',
            'slug' => 'test-formula-injection-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang 1',
            'waktu_mulai' => now()->addDay(),
            'waktu_selesai' => now()->addDay()->addHours(2),
            'is_all_units' => true,
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($superadmin)->get('/admin/reports/summary/csv');

        $response->assertStatus(200);
        $content = $response->streamedContent();

        // Must prepend single quote to prevent spreadsheet execution
        $this->assertStringContainsString("'=cmd", $content);
    }

    public function test_report_recap_renders_unified_surat_edaran_preview_for_pdf(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::create([
            'created_by' => $superadmin->id,
            'judul_rapat' => 'Rapat Evaluasi PDF Preview',
            'slug' => 'test-pdf-preview-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Rapat Utama',
            'waktu_mulai' => now()->addDay(),
            'waktu_selesai' => now()->addDay()->addHours(2),
            'is_all_units' => true,
            'status' => 'scheduled',
            'surat_edaran_path' => 'surat_edaran/sample_invitation.pdf',
        ]);

        $response = $this->actingAs($superadmin)->get("/admin/reports/{$agenda->id}");

        $response->assertStatus(200);
        $response->assertSee('Surat Edaran / Undangan');
        $response->assertSee('PDF');
        $response->assertSee('Pratinjau Layar Penuh');
        $response->assertSee('Tab Baru');
        $response->assertSee('Unduh File');
        $response->assertSee("modal-surat-preview-{$agenda->id}");
        $response->assertSee('sample_invitation.pdf#toolbar=0');
    }

    public function test_report_recap_renders_unified_surat_edaran_preview_for_image(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::create([
            'created_by' => $superadmin->id,
            'judul_rapat' => 'Rapat Evaluasi Image Preview',
            'slug' => 'test-image-preview-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Rapat Utama',
            'waktu_mulai' => now()->addDay(),
            'waktu_selesai' => now()->addDay()->addHours(2),
            'is_all_units' => true,
            'status' => 'scheduled',
            'surat_edaran_path' => 'surat_edaran/sample_flyer.jpg',
        ]);

        $response = $this->actingAs($superadmin)->get("/admin/reports/{$agenda->id}");

        $response->assertStatus(200);
        $response->assertSee('Surat Edaran / Undangan');
        $response->assertSee('GAMBAR');
        $response->assertSee('Klik untuk perbesar');
        $response->assertSee('Pratinjau Layar Penuh');
        $response->assertSee("modal-surat-preview-{$agenda->id}");
        $response->assertSee('sample_flyer.jpg');
    }

    public function test_report_recap_renders_unified_surat_edaran_empty_state(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::create([
            'created_by' => $superadmin->id,
            'judul_rapat' => 'Rapat Tanpa Surat Edaran',
            'slug' => 'test-empty-surat-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Rapat Utama',
            'waktu_mulai' => now()->addDay(),
            'waktu_selesai' => now()->addDay()->addHours(2),
            'is_all_units' => true,
            'status' => 'scheduled',
            'surat_edaran_path' => null,
        ]);

        $response = $this->actingAs($superadmin)->get("/admin/reports/{$agenda->id}");

        $response->assertStatus(200);
        $response->assertSee('Surat Edaran / Undangan');
        $response->assertSee('Tidak ada berkas surat edaran atau undangan terlampir.');
    }

    public function test_surat_edaran_preview_parity_between_agenda_show_and_report_show(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::create([
            'created_by' => $superadmin->id,
            'judul_rapat' => 'Rapat Parity Test',
            'slug' => 'test-parity-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Rapat Utama',
            'waktu_mulai' => now()->addDay(),
            'waktu_selesai' => now()->addDay()->addHours(2),
            'is_all_units' => true,
            'status' => 'scheduled',
            'surat_edaran_path' => 'surat_edaran/sample_parity.pdf',
        ]);

        $agendaShow = $this->actingAs($superadmin)->get("/admin/agendas/{$agenda->id}");
        $reportShow = $this->actingAs($superadmin)->get("/admin/reports/{$agenda->id}");

        $agendaShow->assertStatus(200);
        $reportShow->assertStatus(200);

        // Both pages must render the identical unified preview component
        $expectedFragments = [
            'Surat Edaran / Undangan',
            'PDF',
            'Pratinjau Layar Penuh',
            'Tab Baru',
            'Unduh File',
            "modal-surat-preview-{$agenda->id}",
            'sample_parity.pdf#toolbar=0',
        ];

        foreach ($expectedFragments as $fragment) {
            $agendaShow->assertSee($fragment);
            $reportShow->assertSee($fragment);
        }
    }
}

