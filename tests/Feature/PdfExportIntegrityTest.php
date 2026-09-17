<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\User;
use App\Services\PdfExportService;
use App\Services\WordExportService;
use Tests\TestCase;

class PdfExportIntegrityTest extends TestCase
{
    public function test_binary_pdf_download_returns_authentic_pdf_file_and_headers(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        $response = $this->actingAs($superadmin)->get("/admin/reports/{$agenda->id}/export/pdf?download=pdf");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf; charset=UTF-8');
        $response->assertHeader('X-Accel-Buffering', 'no');
        $this->assertStringContainsString('Berita_Acara_Rapat_', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.pdf', $response->headers->get('Content-Disposition'));

        $content = $response->getContent();
        $this->assertStringStartsWith('%PDF-', $content);
        $this->assertGreaterThan(1000, strlen($content));
    }

    public function test_a4_sheet_preview_returns_locked_portrait_css_and_sheet_container(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        $response = $this->actingAs($superadmin)->get("/admin/reports/{$agenda->id}/export/pdf");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        $response->assertHeader('X-Accel-Buffering', 'no');

        $content = $response->getContent();
        $this->assertStringContainsString('size: 210mm 297mm portrait', $content);
        $this->assertStringContainsString('size: 210mm 297mm portrait !important;', $content);
        $this->assertStringContainsString('a4-sheet-container', $content);
        $this->assertStringContainsString('a4-sheet-wrapper', $content);
        $this->assertStringContainsString('Unduh PDF (.pdf)', $content);
        $this->assertStringContainsString('Cetak Lembar A4', $content);
        $this->assertStringContainsString('Unduh Word (.doc)', $content);
        $this->assertStringContainsString('SIPERAPAT', $content);
        $this->assertStringContainsString('Pratinjau Dokumen Berita Acara', $content);
        $this->assertStringNotContainsString('A4 Portrait (210 &times; 297 mm)', $content);
        $this->assertStringNotContainsString('status-indicator', $content);
    }

    public function test_pdf_export_service_generates_binary_pdf_directly(): void
    {
        $agenda = Agenda::first();
        $service = new PdfExportService(new WordExportService());

        $response = $service->exportBinaryPdf($agenda);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        $this->assertEquals('no', $response->headers->get('X-Accel-Buffering'));
        $content = $response->getContent();
        $this->assertStringStartsWith('%PDF-', $content);
    }

    public function test_custom_report_config_is_respected_in_binary_pdf_export(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        $response = $this->actingAs($superadmin)->post("/admin/reports/{$agenda->id}/export/pdf?download=pdf", [
            'instansi_induk' => 'KEMENTERIAN RISET TEKNOLOGI DAN PENDIDIKAN TINGGI KHUSUS',
            'custom_agenda_title' => 'RAPAT KOORDINASI KHUSUS TINGKAT TINGGI',
            'show_kop' => '1',
            'show_logo' => '1',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf; charset=UTF-8');
        $content = $response->getContent();
        $this->assertStringStartsWith('%PDF-', $content);
    }

    public function test_custom_report_config_is_respected_in_preview_mode(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        $response = $this->actingAs($superadmin)->post("/admin/reports/{$agenda->id}/export/pdf", [
            'instansi_induk' => 'KEMENTERIAN RISET TEKNOLOGI DAN PENDIDIKAN TINGGI KHUSUS',
            'custom_agenda_title' => 'RAPAT KOORDINASI KHUSUS TINGKAT TINGGI',
            'show_kop' => '1',
            'show_logo' => '1',
        ]);

        $response->assertStatus(200);
        $response->assertSee('KEMENTERIAN RISET TEKNOLOGI DAN PENDIDIKAN TINGGI KHUSUS');
        $response->assertSee('RAPAT KOORDINASI KHUSUS TINGKAT TINGGI');
    }

    public function test_unauthorized_user_cannot_export_agenda_pdf(): void
    {
        $agenda = Agenda::first();

        $response = $this->get("/admin/reports/{$agenda->id}/export/pdf?download=pdf");
        $response->assertRedirect('/login');

        $previewResponse = $this->get("/admin/reports/{$agenda->id}/export/pdf");
        $previewResponse->assertRedirect('/login');
    }

    public function test_is_libreoffice_available_method_returns_boolean(): void
    {
        $service = app(PdfExportService::class);
        $this->assertIsBool($service->isLibreOfficeAvailable());
    }

    public function test_graceful_fallback_when_binary_pdf_export_fails(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        // Mock PdfExportService to simulate failure in binary PDF conversion
        $mockPdfService = $this->createMock(PdfExportService::class);
        $mockPdfService->method('exportBinaryPdf')
            ->willThrowException(new \RuntimeException('Simulated LibreOffice failure'));
        $mockPdfService->method('exportBeritaAcara')
            ->willReturn(response('<html>Mocked Printable A4 View</html>', 200, ['Content-Type' => 'text/html']));

        $this->app->instance(PdfExportService::class, $mockPdfService);

        // 1. GET download request should gracefully fallback to printable view (no 500)
        $getResponse = $this->actingAs($superadmin)->get("/admin/reports/{$agenda->id}/export/pdf?download=pdf");
        $getResponse->assertStatus(200);
        $this->assertStringContainsString('Mocked Printable A4 View', $getResponse->getContent());

        // 2. POST download request should redirect with warning alert (no 500)
        $postResponse = $this->actingAs($superadmin)->post("/admin/reports/{$agenda->id}/export/pdf?download=pdf", [
            'document_title' => 'RAPAT UJI FALLBACK',
        ]);
        $postResponse->assertRedirect(route('admin.agendas.show', $agenda));
        $postResponse->assertSessionHas('warning');
    }
}
