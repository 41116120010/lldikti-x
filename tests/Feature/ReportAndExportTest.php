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
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        $response->assertSee('BERITA ACARA DAN DAFTAR HADIR RAPAT');
        $response->assertSee('LEMBAGA LAYANAN PENDIDIKAN TINGGI (LLDIKTI) WILAYAH X');
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
    }

    public function test_reports_filtering_with_date_and_format_parameters(): void
    {
        $superadmin = User::where('role', 'administrator')->first();

        $response = $this->actingAs($superadmin)->get('/admin/reports?start_date=2026-01-01&end_date=2026-12-31&tipe=offline&status=all');

        $response->assertStatus(200);
        $response->assertSee('Daftar Rekapitulasi Rapat');
    }

    public function test_users_table_pagination_links_render(): void
    {
        $superadmin = User::where('role', 'administrator')->first();

        $response = $this->actingAs($superadmin)->get('/admin/users');

        $response->assertStatus(200);
        $response->assertSee('Menampilkan');
        $response->assertSee('data');
    }
}
