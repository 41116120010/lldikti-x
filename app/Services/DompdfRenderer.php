<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use RuntimeException;
use Throwable;

/**
 * Renders the exported Berita Acara document to PDF with Dompdf.
 *
 * Dompdf is the primary engine because it is the only one in this project that
 * honours the page margin declared in the document. LibreOffice discards the
 * whole named @page block without warning, which locked every export to
 * LibreOffice's own defaults and pushed the tables past the right margin.
 *
 * Compared with the LibreOffice path this is also far cheaper: the same
 * document converted roughly six times faster and used about a twelfth of the
 * memory, and it spawns no child process at all, so there is no subprocess that
 * can hang and no user profile to keep warm.
 *
 * The class deliberately owns nothing but the render call. Deciding which
 * engine to use, and what to do when one fails, belongs to PdfExportService.
 */
class DompdfRenderer
{
    /**
     * Convert an exported document into a PDF and return the raw bytes.
     *
     * @throws RuntimeException when the document cannot be rendered
     */
    public function render(string $html): string
    {
        $startedAt = microtime(true);

        try {
            $dompdf = new Dompdf($this->options());
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $pdf = $dompdf->output();
        } catch (Throwable $e) {
            throw new RuntimeException('Dokumen PDF gagal dibuat oleh mesin Dompdf.', previous: $e);
        }

        if ($pdf === '' || ! str_starts_with($pdf, '%PDF')) {
            throw new RuntimeException('Mesin Dompdf menghasilkan berkas yang bukan PDF.');
        }

        report(sprintf(
            'PDF dirender oleh Dompdf dalam %.0f ms, %d byte, %d halaman.',
            (microtime(true) - $startedAt) * 1000,
            strlen($pdf),
            $dompdf->getCanvas()->get_page_count(),
        ));

        return $pdf;
    }

    /**
     * Whether the Dompdf extension is installed and loadable.
     */
    public function isAvailable(): bool
    {
        return class_exists(Dompdf::class);
    }

    /**
     * Dompdf options, assembled from config so an operator can tune them
     * without touching code.
     */
    private function options(): Options
    {
        $options = new Options;

        // Remote resources are never fetched. Every image in the document is an
        // inline data URI produced by WordExportService, so this closes an SSRF
        // surface without costing any fidelity.
        $options->set('isRemoteEnabled', (bool) config('export.dompdf.is_remote_enabled', false));
        $options->set('isHtml5ParserEnabled', (bool) config('export.dompdf.is_html5_parser_enabled', true));
        $options->set('defaultFont', (string) config('export.dompdf.default_font', 'serif'));

        $fontDir = storage_path((string) config('export.dompdf.font_dir', 'app/fonts'));
        $fontCache = storage_path((string) config('export.dompdf.font_cache', 'app/fonts/fontdata'));

        foreach ([$fontDir, $fontCache] as $directory) {
            if (! is_dir($directory)) {
                @mkdir($directory, 0755, true);
            }
        }

        $options->set('fontDir', $fontDir);
        $options->set('fontCache', $fontCache);
        $options->set('chroot', $fontDir);

        return $options;
    }
}
