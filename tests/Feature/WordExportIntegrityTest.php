<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\User;
use App\Services\DocxExportService;
use App\Services\SuratEdaranService;
use App\Services\WordExportService;
use Illuminate\Support\Facades\Log;
use Psr\Log\AbstractLogger;
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
        $xml = $this->docxAllXml($this->docxFor($agenda));

        // Kop surat
        $this->assertStringContainsString('LEMBAGA LAYANAN PENDIDIKAN TINGGI WILAYAH X', $xml);

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

        // The letterhead rule follows the official solid 2.25 pt style.
        $html = $this->documentBodyHtml($agenda);
        $this->assertStringContainsString('border-bottom: 2.25pt solid #000000', $html);
        $this->assertStringNotContainsString('double', $html);
    }

    public function test_word_export_keeps_table_columns_and_images_at_their_intended_size(): void
    {
        $agenda = Agenda::first();
        $body = $this->docxFor($agenda);
        $headerXml = $this->docxHeaderXml($body);

        $this->assertGreaterThan(0, $this->docxMediaCount($body), 'Logo harus tertanam di paket.');
        $this->assertStringContainsString(
            'w:w="'.\App\Support\DocumentLayout::KOP_LOGO_COL_TWIPS.'"',
            $headerXml,
            'Kolom logo kop harus 2043 twip sesuai layout 18.25 cm.'
        );
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

    public function test_word_export_seamlessly_falls_back_to_doc_when_docx_fails(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        $mockDocx = $this->createMock(DocxExportService::class);
        $mockDocx->method('exportBeritaAcara')
            ->willThrowException(new \RuntimeException('Simulated DOCX failure'));

        $this->app->instance(DocxExportService::class, $mockDocx);

        $response = $this->actingAs($superadmin)->get("/admin/reports/{$agenda->id}/export/word");
        $response->assertStatus(200);
        $this->assertStringContainsString('ms-word', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Berita_Acara_', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_word_export_error_handling_gracefully_redirects_on_controller_failure(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        $mockDocx = $this->createMock(DocxExportService::class);
        $mockDocx->method('exportBeritaAcara')
            ->willThrowException(new \RuntimeException('Simulated DOCX failure'));

        $mockWord = $this->createMock(WordExportService::class);
        $mockWord->method('exportBeritaAcara')
            ->willThrowException(new \RuntimeException('Simulated Word fallback failure'));

        $this->app->instance(DocxExportService::class, $mockDocx);
        $this->app->instance(WordExportService::class, $mockWord);

        $response = $this->actingAs($superadmin)->get("/admin/reports/{$agenda->id}/export/word");
        $response->assertRedirect(route('admin.reports.show', $agenda));
        $response->assertSessionHas('error');

        $agendaRefererResponse = $this->actingAs($superadmin)
            ->from(route('admin.agendas.show', $agenda))
            ->get("/admin/reports/{$agenda->id}/export/word");
        $agendaRefererResponse->assertRedirect(route('admin.agendas.show', $agenda));
        $agendaRefererResponse->assertSessionHas('error');
    }

    public function test_surat_edaran_word_export_seamlessly_falls_back_to_doc_when_docx_fails(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        $this->partialMock(SuratEdaranService::class, function ($mock): void {
            $mock->shouldReceive('downloadWordResponse')
                ->once()
                ->andThrow(new \RuntimeException('Simulated Surat Dinas DOCX failure'));
        });

        $response = $this->actingAs($superadmin)
            ->get(route('admin.agendas.export-surat-edaran-word', $agenda));

        $response->assertStatus(200);
        $this->assertStringContainsString('ms-word', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('.doc', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_surat_edaran_word_export_error_handling_gracefully_redirects_on_controller_failure(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        $mock = $this->createMock(SuratEdaranService::class);
        $mock->method('downloadWordResponse')
            ->willThrowException(new \RuntimeException('Simulated Surat Dinas Word export failure'));
        $mock->method('downloadLegacyWordResponse')
            ->willThrowException(new \RuntimeException('Simulated Surat Dinas Word fallback failure'));

        $this->app->instance(SuratEdaranService::class, $mock);

        $response = $this->actingAs($superadmin)
            ->from(route('admin.agendas.surat-edaran', $agenda))
            ->get(route('admin.agendas.export-surat-edaran-word', $agenda));

        $response->assertRedirect(route('admin.agendas.surat-edaran', $agenda));
        $response->assertSessionHas('error');
    }

    public function test_word_export_succeeds_even_when_logger_throws_permission_denied(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        $originalLog = Log::getFacadeRoot();
        $failingLogger = new class extends AbstractLogger
        {
            public function log($level, string|\Stringable $message, array $context = []): void
            {
                throw new \UnexpectedValueException('The stream or file "storage/logs/laravel.log" could not be opened in append mode: Permission denied');
            }
        };

        Log::swap($failingLogger);

        try {
            // 1. Happy path must succeed without hitting any file logger (Web & API)
            $webResponse = $this->actingAs($superadmin)->get("/admin/reports/{$agenda->id}/export/word");
            $webResponse->assertStatus(200);
            $this->assertStringContainsString('wordprocessingml.document', (string) $webResponse->headers->get('Content-Type'));

            $apiResponse = $this->actingAs($superadmin, 'sanctum')->get("/api/v1/reports/agendas/{$agenda->id}/export/word");
            $apiResponse->assertStatus(200);
            $this->assertStringContainsString('wordprocessingml.document', (string) $apiResponse->headers->get('Content-Type'));

            // 2. Fallback path must STILL succeed when DocxExportService throws AND logger throws
            $mockDocx = $this->createMock(DocxExportService::class);
            $mockDocx->method('exportBeritaAcara')
                ->willThrowException(new \RuntimeException('Simulated DOCX write failure'));
            $this->app->instance(DocxExportService::class, $mockDocx);

            $webFallback = $this->actingAs($superadmin)->get("/admin/reports/{$agenda->id}/export/word");
            $webFallback->assertStatus(200);
            $this->assertStringContainsString('ms-word', (string) $webFallback->headers->get('Content-Type'));

            $apiFallback = $this->actingAs($superadmin, 'sanctum')->get("/api/v1/reports/agendas/{$agenda->id}/export/word");
            $apiFallback->assertStatus(200);
            $this->assertStringContainsString('ms-word', (string) $apiFallback->headers->get('Content-Type'));
        } finally {
            Log::swap($originalLog);
        }
    }

    public function test_word_export_fallback_handles_null_attendee_user_and_array_query_params_safely(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        // 1. Verify legacy HTML .doc template renders safely even when attendee user, signed_at, or agenda waktu_mulai is null
        $viewData = app(WordExportService::class)->prepareViewData($agenda);
        $nullAttendance = new Attendance([
            'agenda_id' => $agenda->id,
            'status' => 'hadir',
            'signed_at' => null,
        ]);
        $nullAttendance->setRelation('user', null);
        $viewData['attendances'] = collect([[
            'model' => $nullAttendance,
            'sig_base64' => null,
            'selfie_base64' => null,
        ]]);
        $agendaClone = clone $agenda;
        $agendaClone->waktu_mulai = null;
        $agendaClone->waktu_selesai = null;
        $viewData['agenda'] = $agendaClone;

        $renderedHtml = view('exports.word_berita_acara', $viewData)->render();
        $this->assertStringContainsString('Peserta (Anonim)', $renderedHtml);

        // 2. Verify array query parameters (?instansi_induk[]=bad) do not cause TypeError in ReportConfigService
        $mockDocx = $this->createMock(DocxExportService::class);
        $mockDocx->method('exportBeritaAcara')
            ->willThrowException(new \RuntimeException('Simulated DOCX failure'));
        $this->app->instance(DocxExportService::class, $mockDocx);

        $response = $this->actingAs($superadmin)
            ->get("/admin/reports/{$agenda->id}/export/word?instansi_induk[]=bad&signer1_name[]=bad");
        $response->assertStatus(200);
        $this->assertStringContainsString('ms-word', (string) $response->headers->get('Content-Type'));
    }
}
