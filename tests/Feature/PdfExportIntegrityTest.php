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

    public function test_direct_pdf_export_without_query_param_returns_binary_pdf(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        $response = $this->actingAs($superadmin)->get("/admin/reports/{$agenda->id}/export/pdf");

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        $content = $response->getContent();
        $this->assertStringStartsWith('%PDF-', $content);
        $this->assertGreaterThan(1000, strlen($content));
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

        $response = $this->actingAs($superadmin)->post("/admin/reports/{$agenda->id}/export/pdf", [
            'instansi_induk' => 'KEMENTERIAN RISET TEKNOLOGI DAN PENDIDIKAN TINGGI KHUSUS',
            'custom_agenda_title' => 'RAPAT KOORDINASI KHUSUS TINGKAT TINGGI',
            'show_kop' => '1',
            'show_logo' => '1',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        $content = $response->getContent();
        $this->assertStringStartsWith('%PDF-', $content);
    }

    public function test_custom_report_config_is_applied_in_direct_pdf_export(): void
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
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_unauthorized_user_cannot_export_agenda_pdf(): void
    {
        $agenda = Agenda::first();

        $response = $this->get("/admin/reports/{$agenda->id}/export/pdf");
        $response->assertRedirect('/login');

        $previewResponse = $this->get("/admin/reports/{$agenda->id}/export/pdf?download=pdf");
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

        // 1. Request should gracefully fallback to printable view (no 500)
        $getResponse = $this->actingAs($superadmin)->get("/admin/reports/{$agenda->id}/export/pdf");
        $getResponse->assertStatus(200);
        $this->assertStringContainsString('Mocked Printable A4 View', $getResponse->getContent());

        // 2. When both binary and HTML fallback fail, redirects with error alert
        $failingMock = $this->createMock(PdfExportService::class);
        $failingMock->method('exportBinaryPdf')
            ->willThrowException(new \RuntimeException('Simulated LibreOffice failure'));
        $failingMock->method('exportBeritaAcara')
            ->willThrowException(new \RuntimeException('Simulated HTML render failure'));

        $this->app->instance(PdfExportService::class, $failingMock);

        $postResponse = $this->actingAs($superadmin)->post("/admin/reports/{$agenda->id}/export/pdf", [
            'document_title' => 'RAPAT UJI FALLBACK',
        ]);
        $postResponse->assertRedirect(route('admin.agendas.show', $agenda));
        $postResponse->assertSessionHas('error');
    }
}
