<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\User;
use App\Services\WordExportService;
use Tests\TestCase;

class WordExportIntegrityTest extends TestCase
{
    public function test_word_export_returns_correct_http_headers_and_filename(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        $response = $this->actingAs($superadmin)->get("/admin/reports/{$agenda->id}/export/word");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.ms-word; charset=UTF-8');
        $this->assertStringContainsString('Berita_Acara_', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.doc', $response->headers->get('Content-Disposition'));
    }

    public function test_word_export_starts_with_utf8_bom(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        $response = $this->actingAs($superadmin)->get("/admin/reports/{$agenda->id}/export/word");

        $content = $response->getContent();
        $this->assertStringStartsWith("\xef\xbb\xbf", $content);
    }

    public function test_word_export_contains_word_document_namespaces_and_page_setup(): void
    {
        $agenda = Agenda::first();
        $service = new WordExportService();
        $response = $service->exportBeritaAcara($agenda);
        $content = $response->getContent();

        $this->assertStringContainsString('xmlns:o="urn:schemas-microsoft-com:office:office"', $content);
        $this->assertStringContainsString('xmlns:w="urn:schemas-microsoft-com:office:word"', $content);
        $this->assertStringContainsString('<w:WordDocument>', $content);
        $this->assertStringContainsString('@page Section1', $content);
        $this->assertStringContainsString('size: 595.3pt 841.9pt;', $content);
        $this->assertStringContainsString('div.Section1', $content);
    }

    public function test_word_export_contains_kop_info_attendance_and_signature_blocks(): void
    {
        $agenda = Agenda::first();
        $service = new WordExportService();
        $response = $service->exportBeritaAcara($agenda);
        $content = $response->getContent();

        // Kop Surat double border
        $this->assertStringContainsString('border-bottom: 2.25pt double #000000', $content);
        $this->assertStringContainsString('LEMBAGA LAYANAN PENDIDIKAN TINGGI (LLDIKTI) WILAYAH X', $content);

        // Document title & table width
        $this->assertStringContainsString('BERITA ACARA DAN DAFTAR HADIR RAPAT', $content);
        $this->assertStringContainsString('table class="attendance-table" width="100%"', $content);

        // Repeated header and un-split rows
        $this->assertStringContainsString('mso-yfti-tblheader: yes;', $content);
        $this->assertStringContainsString('mso-yfti-row: cantSplit;', $content);

        // Signature table
        $this->assertStringContainsString('table class="signature-table" width="100%"', $content);
        $this->assertStringContainsString('Mengetahui,', $content);
        $this->assertStringContainsString('Notulis Rapat', $content);
    }

    public function test_word_export_lines_do_not_overflow_line_buffer(): void
    {
        $agenda = Agenda::first();
        $service = new WordExportService();
        $response = $service->exportBeritaAcara($agenda);
        $content = $response->getContent();

        $lines = explode("\n", $content);
        $maxLineLen = 0;
        foreach ($lines as $line) {
            $len = strlen($line);
            if ($len > $maxLineLen) {
                $maxLineLen = $len;
            }
        }

        // MS Word / LibreOffice HTML parser line buffer limit is 32,768 characters.
        // Our optimized images must ensure every line stays well below this threshold.
        $this->assertLessThan(32000, $maxLineLen, "A line with length {$maxLineLen} exceeds safe parser line buffer threshold!");
    }

    public function test_word_export_converts_cleanly_via_headless_libreoffice(): void
    {
        $agenda = Agenda::first();
        $service = new WordExportService();
        $response = $service->exportBeritaAcara($agenda);

        $tmpDoc = tempnam(sys_get_temp_dir(), 'word_test_') . '.doc';
        file_put_contents($tmpDoc, $response->getContent());

        $tmpOutDir = sys_get_temp_dir();
        $process = exec("libreoffice -env:UserInstallation=file:///tmp/libreoffice_test --headless --convert-to pdf {$tmpDoc} --outdir {$tmpOutDir} 2>&1", $output, $returnCode);

        // Clean up temporary doc
        @unlink($tmpDoc);
        $pdfPath = preg_replace('/\.doc$/', '.pdf', $tmpDoc);
        if (file_exists($pdfPath)) {
            @unlink($pdfPath);
        }

        $outputStr = implode("\n", $output);
        $this->assertSame(0, $returnCode, "LibreOffice conversion failed: {$outputStr}");
        $this->assertStringContainsString('writer_web_pdf_Export', $outputStr, "LibreOffice did not use the HTML/Web document filter: {$outputStr}");
    }
}
