<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\User;
use App\Services\DocxExportService;
use App\Services\PdfExportService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ViewErrorBag;
use Tests\Support\InspectsDocx;
use Tests\TestCase;

/**
 * Setiap nilai yang diketik di formulir harus muncul di lembar pratinjau, di
 * PDF, dan di .docx.
 *
 * Dua cacat di kelas ini sudah pernah terjadi. Yang pertama catatan kaki: ada
 * di formulir, ada di basis data, hilang dari kedua ekspor. Yang kedua override
 * perihal dan tempat: dipakai PDF dan .docx, tapi lembar pratinjau masih
 * menampilkan kolom agenda mentah, sehingga petugas mengetik override,
 * menyimpan, dan tidak pernah melihatnya di layar.
 *
 * Keduanya berantai: form menerima isian lebih dulu, konsekuensinya baru
 * terasa saat dokumen keluar. Uji ini menutup jalurnya di sumber.
 *
 * Dua hal menjaga uji ini tidak berbohong:
 *
 *  - Nilai uji hanya huruf dan angka. `pdftotext -layout` membaca dua kolom
 *    side-by-side baris per baris sehingga teks kolom kanan dan kiri saling
 *    menyisipkan, dan Dompdf boleh memecah baris di tanda hubung. Keduanya
 *    membuat nilai yang sebenarnya tercetak terbaca tidak ada.
 *  - notulensi dan kesimpulan bukan kunci konfigurasi melainkan kolom agenda,
 *    jadi diuji lewat modelnya dan selalu dikembalikan.
 */
class FormValueReachTest extends TestCase
{
    use InspectsDocx;

    protected function tearDown(): void
    {
        $this->docxCleanup();

        parent::tearDown();
    }

    /** @return array<string,string> */
    private function probeValues(): array
    {
        return [
            'custom_agenda_title' => 'ZZPERIHALEEE',
            'custom_location' => 'ZZLOKASIKFF',
            'document_title' => 'ZZJUDULCCC',
            'document_number' => 'ZZNOMORDDD',
            'instansi_induk' => 'ZZINDUKGGG',
            'instansi_pelaksana' => 'ZZPELAKSANAHHH',
            'alamat_kontak' => 'ZZALAMATIII',
            'signer1_role' => 'ZZJABATAN1JJJ',
            'signer1_name' => 'ZZNAMA1KKK',
            'signer1_nip' => '9990001110000001',
            'signer2_role' => 'ZZJABATAN2LLL',
            'signer2_name' => 'ZZNAMA2MMM',
            'signer2_nip' => '9990002220000002',
            'signing_city' => 'ZZKOTANNN',
            'signing_date' => 'ZZTANGGALYYY',
            'footer_note' => 'ZZKAKI000',
        ];
    }

    private function flatten(string $html): string
    {
        $stripped = preg_replace('#<[^>]+>#', ' ', $html) ?? $html;

        return preg_replace(
            '/\s+/u',
            ' ',
            html_entity_decode($stripped, ENT_QUOTES, 'UTF-8')
        ) ?? $stripped;
    }

    public function test_every_typed_value_reaches_the_sheet_the_pdf_and_the_docx(): void
    {
        $agenda = Agenda::where('status', 'ongoing')->first() ?? Agenda::orderBy('id')->first();
        $this->assertNotNull($agenda, 'Fixture agenda tidak tersedia.');

        $admin = User::where('role', 'administrator')->first() ?? User::orderBy('id')->first();
        $this->actingAs($admin);

        $values = $this->probeValues();
        $notulensi = 'ZZNOTULENSIZZ';
        $kesimpulan = 'ZZKESIMPULANZZ';

        $config = array_merge($agenda->report_config, $values);

        // Catatan kaki mati secara bawaan, jadi harus dinyalakan bersama
        // nilainya. Tanpa ini, probe akan menyimpulkan nilai yang diketik tidak
        // sampai ke dokumen, padahal bloknya memang sengaja mati.
        $config['show_footer_note'] = true;

        // Lembar memuat blok @error. View yang dirender langsung di dalam
        // test tidak melewati middleware, jadi tas galat harus dibagikan.
        view()->share('errors', new ViewErrorBag);

        // Halaman pengisian reading attendee lewat query, jadi sheet-nya
        // dirender terpisah dari isi PDF dan DOCX.
        $attendances = $agenda->attendances()->with('user.unit')->orderBy('signed_at', 'asc')->get();
        $documentations = $agenda->documentations()->latest('id')->paginate(6, ['*'], 'page_docs');

        DB::beginTransaction();

        try {
            $agenda->notulensi = '<p>'.$notulensi.'</p>';
            $agenda->kesimpulan = '<p>'.$kesimpulan.'</p>';
            $agenda->save();

            $sheetHtml = view('agendas.notulen', [
                'agenda' => $agenda->loadMissing(['creator.unit', 'pimpinan.unit', 'notulis.unit', 'units']),
                'documentations' => $documentations,
                'config' => $config,
                'attendances' => $attendances,
            ])->render();

            $sheetStart = strpos($sheetHtml, '<div class="office-paper-sheet paper-a4"');
            $this->assertNotFalse($sheetStart, 'Wadah lembar tidak ditemukan pada halaman pengisian.');

            $pdfBytes = app(PdfExportService::class)->exportBinaryPdf($agenda, $config)->getContent();
            $pdfPath = tempnam(sys_get_temp_dir(), 'reach_pdf_');
            file_put_contents($pdfPath, $pdfBytes);
            $pdfText = (string) shell_exec('pdftotext -layout '.escapeshellarg($pdfPath).' - 2>/dev/null');
            @unlink($pdfPath);

            $docxXml = $this->docxXml(
                app(DocxExportService::class)->exportBeritaAcara($agenda, $config)->getContent()
            );

            $targets = [
                'lembar' => $this->flatten(substr($sheetHtml, (int) $sheetStart)),
                'PDF' => $this->flatten($pdfText),
                'DOCX' => $this->flatten($docxXml),
            ];

            $values['notulensi'] = $notulensi;
            $values['kesimpulan'] = $kesimpulan;

            $misses = [];

            foreach ($values as $field => $value) {
                foreach ($targets as $where => $haystack) {
                    if (! str_contains($haystack, $value)) {
                        $misses[] = $field.' tidak muncul di '.$where;
                    }
                }
            }
        } finally {
            DB::rollBack();
        }

        $this->assertSame(
            [],
            $misses,
            "Nilai yang diketik di formulir tidak sampai ke dokumen:\n  ".implode("\n  ", $misses)
        );
    }

    public function test_the_sheet_reads_the_configured_overrides_for_the_meeting_details(): void
    {
        $agenda = Agenda::where('status', 'ongoing')->first() ?? Agenda::orderBy('id')->first();
        $this->actingAs(User::where('role', 'administrator')->first() ?? User::orderBy('id')->first());

        $blade = (string) file_get_contents(base_path('resources/views/agendas/notulen.blade.php'));
        $sheet = substr($blade, (int) strpos($blade, '<div class="office-paper-sheet paper-a4"'));

        // Pemeriksaan sumber, supaya gagal di titik yang sama dengan sumber
        // kebocorannya: baris "Perihal / Agenda" pada lembar.
        $this->assertMatchesRegularExpression(
            "/\['custom_agenda_title'\] \?\? \\\$agenda->judul_rapat/",
            $sheet,
            'Baris Perihal pada lembar harus memakai override custom_agenda_title, seperti pada ekspor.'
        );
        $this->assertMatchesRegularExpression(
            "/\['custom_location'\] \?\? \(\\\$agenda->lokasi_ruang/",
            $sheet,
            'Baris Format & Tempat pada lembar harus memakai override custom_location, seperti pada ekspor.'
        );
    }
}
