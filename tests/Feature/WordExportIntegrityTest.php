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
        $response->assertHeader('X-Accel-Buffering', 'no');
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

    public function test_word_export_resilience_when_attendance_media_files_are_missing_or_corrupted(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        // Create a unique user and an attendance record with nonexistent media paths
        $testUser = User::create([
            'nip' => '199999999999999999',
            'name' => 'Pengguna Pengujian Resiliensi',
            'username' => 'test_resilience_' . uniqid(),
            'email' => 'test_resilience_' . uniqid() . '@lldikti.test',
            'password' => bcrypt('password'),
            'role' => 'staff',
            'unit_id' => $agenda->unit_id,
            'is_active' => true,
        ]);

        $brokenAttendance = Attendance::create([
            'agenda_id' => $agenda->id,
            'user_id' => $testUser->id,
            'signed_at' => now(),
            'selfie_path' => 'nonexistent/path/to/corrupted_selfie.jpg',
            'signature_path' => 'nonexistent/path/to/corrupted_signature.png',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Test',
        ]);

        $service = new WordExportService();
        $response = $service->exportBeritaAcara($agenda);

        $this->assertSame(200, $response->getStatusCode());
        $content = $response->getContent();
        $this->assertStringContainsString($testUser->name, $content);
        $this->assertStringContainsString('Tanpa Foto', $content);

        $brokenAttendance->delete();
        $testUser->delete();
    }

    public function test_word_export_error_handling_gracefully_redirects_on_controller_failure(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        // Mock WordExportService to simulate unexpected failure
        $mockWordService = $this->createMock(WordExportService::class);
        $mockWordService->method('exportBeritaAcara')
            ->willThrowException(new \RuntimeException('Simulated Word export disk failure'));

        $this->app->instance(WordExportService::class, $mockWordService);

        $response = $this->actingAs($superadmin)->get("/admin/reports/{$agenda->id}/export/word");
        $response->assertRedirect(route('admin.agendas.show', $agenda));
        $response->assertSessionHas('error');
    }
}
