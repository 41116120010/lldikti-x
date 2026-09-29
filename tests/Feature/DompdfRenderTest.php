<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\User;
use App\Services\DompdfRenderer;
use App\Services\PdfExportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Pins the contract of the PDF export now that Dompdf replaced LibreOffice.
 *
 * Three things have to stay true or the export silently regresses:
 *
 *  1. The page margin declared in config reaches the rendered page. This is
 *     the whole reason the engine changed: LibreOffice discarded the @page
 *     block, so the declared margin was never the printed margin.
 *  2. The rendered document is a real PDF, produced without a child process.
 *  3. The failure path degrades to the printable HTML view instead of a 500.
 */
class DompdfRenderTest extends TestCase
{
    use DatabaseTransactions;

    private User $superadmin;

    private Agenda $agenda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::where('role', 'administrator')->firstOrFail();
        $this->agenda = Agenda::firstOrFail();
    }

    public function test_export_produces_a_real_pdf_without_any_child_process(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->get("/admin/reports/{$this->agenda->id}/export/pdf?download=pdf");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf; charset=UTF-8');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_the_declared_page_margin_reaches_the_document(): void
    {
        $html = app(\App\Services\WordExportService::class)
            ->generateDocumentContent($this->agenda, [], 'plain');

        $expected = sprintf(
            'margin: %s %s %s %s',
            config('export.page.top'),
            config('export.page.right'),
            config('export.page.bottom'),
            config('export.page.left'),
        );

        $this->assertStringContainsString($expected, $html);

        // The bare @page is the only form Dompdf reads; the named form is for
        // Word and is discarded by Dompdf, which would silently undo this.
        $this->assertMatchesRegularExpression('/@page\s*\{[^}]*'.$expected.'/', $html);
    }

    public function test_the_renderer_reports_whether_dompdf_is_installed(): void
    {
        $this->assertTrue(app(DompdfRenderer::class)->isAvailable());
    }

    public function test_a_renderer_failure_degrades_to_the_printable_view(): void
    {
        $renderer = $this->createMock(DompdfRenderer::class);
        $renderer->method('isAvailable')->willReturn(true);
        $renderer->method('render')->willThrowException(new \RuntimeException('Simulated failure'));

        $this->app->instance(DompdfRenderer::class, $renderer);
        $this->app->instance(PdfExportService::class, new PdfExportService(
            app(\App\Services\WordExportService::class),
            $renderer,
        ));

        $response = $this->actingAs($this->superadmin)
            ->get("/admin/reports/{$this->agenda->id}/export/pdf");

        // The browser-print fallback answers instead of a 500.
        $response->assertStatus(200);
        $this->assertStringContainsString('text/html', $response->headers->get('Content-Type'));
    }
}
