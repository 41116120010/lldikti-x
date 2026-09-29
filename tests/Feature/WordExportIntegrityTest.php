<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\User;
use App\Services\DocxExportService;
use App\Services\WordExportService;
use Tests\Support\InspectsDocx;
use Tests\TestCase;

/**
 * The Word export is a real .docx package, not HTML wearing a .doc filename.
 *
 * These tests therefore assert on the OpenXML inside the archive - a ZIP whose
 * text lives in word/document.xml and whose images live under word/media -
 * rather than on the response body, which is binary.
 */
class WordExportIntegrityTest extends TestCase
{
    use InspectsDocx;

    private function docxFor(Agenda $agenda): string
    {
        return app(DocxExportService::class)->exportBeritaAcara($agenda)->getContent();
    }

    public function test_word_export_returns_correct_http_headers_and_filename(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        $response = $this->actingAs($superadmin)->get("/admin/reports/{$agenda->id}/export/word");

        $response->assertStatus(200);
        $response->assertHeader(
            'Content-Type',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );
        $response->assertHeader('X-Accel-Buffering', 'no');
        $this->assertStringContainsString('Berita_Acara_', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.docx', $response->headers->get('Content-Disposition'));
    }

    public function test_word_export_is_a_zip_archive_and_not_a_renamed_html_page(): void
    {
        $agenda = Agenda::first();
        $body = $this->docxFor($agenda);

        // A .docx is a ZIP container. The old export was an HTML page, which
        // started with "<!DOCTYPE"; that must not come back.
        $this->assertStringStartsWith('PK', $body);
        $this->assertStringNotContainsString('<!DOCTYPE', $body);

        $xml = $this->docxXml($body);
        $this->assertNotFalse(simplexml_load_string($xml), 'word/document.xml harus XML yang valid.');
    }

    public function test_word_export_carries_an_a4_page_setup_with_the_configured_margins(): void
    {
        $agenda = Agenda::first();
        $xml = $this->docxXml($this->docxFor($agenda));

        // A4 in twentieths of a point: 21.0 cm and 29.7 cm.
        $this->assertStringContainsString('w:w="11906"', $xml, 'Lebar halaman harus A4 (11906 twip).');
        $this->assertStringContainsString('w:h="16838"', $xml, 'Tinggi halaman harus A4 (16838 twip).');

        // Margins come from config: left 2 cm, right 2 cm, top/bottom 1.5 cm.
        $twips = fn (string $key): int => (int) round(
            \App\Support\DocumentLayout::cm((string) config($key)) * 566.929
        );

        $this->assertStringContainsString(
            'w:left="'.$twips('export.page.left').'"',
            $xml,
            'Margin kiri harus mengikuti config export.page.left.'
        );
        $this->assertStringContainsString(
            'w:right="'.$twips('export.page.right').'"',
            $xml,
            'Margin kanan harus mengikuti config export.page.right.'
        );
    }

    public function test_word_export_contains_kop_info_attendance_and_signature_blocks(): void
    {
        $agenda = Agenda::first();
        $xml = $this->docxXml($this->docxFor($agenda));

        // Kop surat
        $this->assertStringContainsString('LEMBAGA LAYANAN PENDIDIKAN TINGGI (LLDIKTI) WILAYAH X', $xml);

        // Document title
        $this->assertStringContainsString('BERITA ACARA DAN DAFTAR HADIR RAPAT', $xml);

        // Attendance and signature blocks
        $this->assertStringContainsString('DAFTAR KEHADIRAN PESERTA', $xml);
        $this->assertStringContainsString('Tanda Tangan', $xml);
        $this->assertStringContainsString('Mengetahui,', $xml);
        $this->assertStringContainsString('Notulis Rapat', $xml);

        // Rows refuse to split and the attendance header repeats on each page.
        $this->assertStringContainsString('cantSplit', $xml);
        $this->assertStringContainsString('tblHeader', $xml);

        // The letterhead rule follows the official solid 1 pt style. The shared
        // body is the single source of truth for both renderers, so the rule is
        // asserted there rather than on the package.
        $html = $this->documentBodyHtml($agenda);
        $this->assertStringContainsString('border-bottom: 1pt solid #000000', $html);
        $this->assertStringNotContainsString('2.25pt double', $html);
    }

    public function test_word_export_keeps_table_columns_and_images_at_their_intended_size(): void
    {
        $agenda = Agenda::first();
        $body = $this->docxFor($agenda);
        $xml = $this->docxXml($body);

        // Column widths are written in twips. The 17 cm printable width must
        // survive, otherwise the tables collapse.
        $this->assertGreaterThan(0, $this->docxMediaCount($body), 'Logo harus tertanam di paket.');
        // The letterhead's logo column is 19% of the 17 cm printable width,
        // which is 1831 twips. If this drifts the whole table collapses.
        $this->assertStringContainsString('w:w="1831"', $xml, 'Kolom logo kop harus 1831 twip (3,23 cm).');
    }

    public function test_word_export_survives_missing_attendance_media(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        $testUser = User::create([
            'nip' => '199999999999999999',
            'name' => 'Pengguna Pengujian Resiliensi',
            'username' => 'test_resilience_'.uniqid(),
            'email' => 'test_resilience_'.uniqid().'@lldikti.test',
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

        $xml = $this->docxXml($this->docxFor($agenda));

        $this->assertStringContainsString($testUser->name, $xml);

        $brokenAttendance->delete();
        $testUser->delete();
    }

    public function test_shared_document_body_renders_cleanly_into_a_pdf(): void
    {
        $agenda = Agenda::first();

        // The same body the .docx is built from must also be renderable by the
        // PDF engine, otherwise the two formats drift apart.
        $html = app(WordExportService::class)->generateDocumentContent($agenda, [], 'plain');

        $pdf = app(\App\Services\DompdfRenderer::class)->render($html);

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf), 'PDF hasil render terlalu kecil untuk berisi dokumen.');
    }

    public function test_word_export_error_handling_gracefully_redirects_on_controller_failure(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        $mock = $this->createMock(DocxExportService::class);
        $mock->method('exportBeritaAcara')
            ->willThrowException(new \RuntimeException('Simulated Word export failure'));

        $this->app->instance(DocxExportService::class, $mock);

        $response = $this->actingAs($superadmin)->get("/admin/reports/{$agenda->id}/export/word");
        $response->assertRedirect(route('admin.agendas.show', $agenda));
        $response->assertSessionHas('error');
    }
}
