<?php

namespace App\Services;

use App\Models\Agenda;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PdfExportService
{
    public function __construct(
        protected WordExportService $wordExportService
    ) {}

    /**
     * Determine if LibreOffice headless binary and shell execution are available in the current environment.
     */
    public function isLibreOfficeAvailable(): bool
    {
        if (!function_exists('exec')) {
            return false;
        }

        $disabled = explode(',', (string) ini_get('disable_functions'));
        $disabled = array_map('trim', $disabled);
        if (in_array('exec', $disabled, true)) {
            return false;
        }

        $checkCommand = 'which libreoffice 2>/dev/null || which soffice 2>/dev/null || command -v libreoffice 2>/dev/null';
        exec($checkCommand, $output, $returnCode);

        return $returnCode === 0 && !empty($output);
    }

    /**
     * Generate authentic binary PDF document (.pdf) using headless LibreOffice converter.
     * Guaranteed exact A4 portrait layout (210mm x 297mm) and Tata Naskah Dinas margins.
     */
    public function exportBinaryPdf(Agenda $agenda, array $config = []): Response
    {
        @ini_set('memory_limit', '256M');
        @set_time_limit(120);

        if (!$this->isLibreOfficeAvailable()) {
            throw new \RuntimeException('Layanan LibreOffice headless tidak tersedia atau dinonaktifkan pada server ini.');
        }

        $docHtml = $this->wordExportService->generateDocumentContent($agenda, $config);

        $tmpDir = sys_get_temp_dir() . '/siperapat_pdf_' . bin2hex(random_bytes(8));
        if (!mkdir($tmpDir, 0755, true) && !is_dir($tmpDir)) {
            throw new \RuntimeException('Gagal menginisialisasi direktori sementara untuk ekspor PDF.');
        }

        $inputPath = $tmpDir . '/document.doc';
        $outputPath = $tmpDir . '/document.pdf';
        $userProfile = 'file://' . $tmpDir . '/lo_profile';

        try {
            file_put_contents($inputPath, $docHtml);

            // Execute LibreOffice headless converter to PDF
            $command = sprintf(
                'libreoffice -env:UserInstallation=%s --headless --convert-to pdf --outdir %s %s 2>&1',
                escapeshellarg($userProfile),
                escapeshellarg($tmpDir),
                escapeshellarg($inputPath)
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($outputPath)) {
                Log::error('Headless LibreOffice PDF conversion failed', [
                    'return_code' => $returnCode,
                    'output' => $output,
                ]);
                throw new \RuntimeException('Gagal mengonversi dokumen ke format PDF biner.');
            }

            $pdfContent = file_get_contents($outputPath);
            $filename = 'Berita_Acara_Rapat_' . $agenda->slug . '.pdf';

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'max-age=0, must-revalidate',
                'X-Accel-Buffering' => 'no', // Bypass Nginx FastCGI buffer proxy caching
            ]);
        } finally {
            $this->cleanupDirectory($tmpDir);
        }
    }

    /**
     * Generate print-ready inline HTML Berita Acara as browser-printing fallback.
     *
     * Renders the shared Word/PDF template (single source of truth) with
     * Content-Disposition: inline so the browser opens it for Ctrl+P / Save as PDF.
     * Used when LibreOffice headless binary conversion is unavailable.
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

    /**
     * Safely clean up temporary files and directory.
     */
    protected function cleanupDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                @rmdir($item->getRealPath());
            } else {
                @unlink($item->getRealPath());
            }
        }

        @rmdir($dir);
    }
}
