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
     * Generate authentic binary PDF document (.pdf) using headless LibreOffice converter.
     * Guaranteed exact A4 portrait layout (210mm x 297mm) and Tata Naskah Dinas margins.
     */
    public function exportBinaryPdf(Agenda $agenda, array $config = []): Response
    {
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
            ]);
        } finally {
            $this->cleanupDirectory($tmpDir);
        }
    }

    /**
     * Generate printable official Web Preview / Berita Acara document for an Agenda.
     */
    public function exportBeritaAcara(Agenda $agenda, array $config = []): Response
    {
        $data = $this->wordExportService->prepareViewData($agenda, $config);
        $html = view('exports.pdf_berita_acara', $data)->render();

        $filename = 'Berita_Acara_Rapat_' . $agenda->slug . '.html';

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
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
