<?php

namespace App\Services;

use App\Models\Agenda;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Produces the exported Berita Acara as a downloadable PDF.
 *
 * The document is rendered by Dompdf from the same template the Word export
 * uses, so both formats stay in step: one partial, one set of numbers.
 *
 * Dompdf replaced a headless LibreOffice pipeline that had three problems the
 * measurements made plain:
 *
 *  - LibreOffice discards a named @page block without warning, so page margins
 *    were locked to its own defaults and the tables overflowed the right
 *    margin. Changing the declared margin to 5 cm produced a byte-identical
 *    PDF, which is how that was proven. Dompdf reads a plain @page, so the
 *    margin in config/export.php is the margin on the page.
 *  - The same document converted roughly six times faster and used about a
 *    twelfth of the memory (0.6 s and 40 MB against 1.8 s and a 512 MB
 *    ceiling).
 *  - The LibreOffice fallback had become the least reliable part of the
 *    system: at 12 pt it entered a layout loop and ran past its own timeout,
 *    and because it ran as a child process it left a hang that stalled the
 *    whole suite. Removing it removes the only component that can hang.
 *
 * The trade is that there is no second engine to fall back to. That is
 * deliberate: the fallback was a net liability, and a pure-PHP renderer has no
 * external process that can block.
 */
class PdfExportService
{
    public function __construct(
        protected WordExportService $wordExportService,
        protected ?DompdfRenderer $renderer = null,
    ) {}

    /**
     * Resolved lazily so the service can still be built with a plain
     * `new PdfExportService()` from tests and console code.
     */
    protected function dompdf(): DompdfRenderer
    {
        return $this->renderer ??= app(DompdfRenderer::class);
    }

    /**
     * Generate the binary PDF document for an agenda.
     *
     * @param  array<string,mixed>  $config  Report-config overrides for this single export.
     */
    public function exportBinaryPdf(Agenda $agenda, array $config = []): Response
    {
        if (! $this->dompdf()->isAvailable()) {
            throw new \RuntimeException('Pustaka Dompdf tidak tersedia pada server ini.');
        }

        // 'plain' emits a bare @page, the only form Dompdf reads. The named
        // form exists for Word and is left untouched.
        $html = $this->wordExportService->generateDocumentContent($agenda, $config, 'plain');

        try {
            $pdf = $this->dompdf()->render($html);
        } catch (Throwable $e) {
            Log::error('PDF export failed', [
                'agenda_id' => $agenda->id,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Gagal membuat dokumen PDF. Silakan coba lagi.', previous: $e);
        }

        return $this->pdfResponse($pdf, $agenda);
    }

    /**
     * Build the download response for a finished PDF.
     */
    private function pdfResponse(string $pdf, Agenda $agenda): Response
    {
        $filename = 'Berita_Acara_Rapat_' . $agenda->slug . '.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0, must-revalidate',
            'X-Accel-Buffering' => 'no', // Bypass Nginx FastCGI buffer proxy caching
        ]);
    }

    /**
     * Generate print-ready inline HTML Berita Acara as browser-printing fallback.
     *
     * Renders the shared Word/PDF template (single source of truth) with
     * Content-Disposition: inline so the browser opens it for Ctrl+P / Save as
     * PDF. Used when the PDF renderer is unavailable.
     *
     * @param  array<string,mixed>  $config  Report-config overrides for this single export.
     */
    public function exportBeritaAcara(Agenda $agenda, array $config = []): Response
    {
        $data = $this->wordExportService->prepareViewData($agenda, $config);
        $html = view('exports.word_berita_acara', $data)->render();

        $filename = 'Berita_Acara_Rapat_' . $agenda->slug . '.html';

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
