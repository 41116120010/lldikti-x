<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Services\DocxExportService;
use App\Services\WordExportService;
use App\Support\DocumentLayout;
use Tests\Support\InspectsDocx;
use Tests\TestCase;

/**
 * Setiap opsi penyesuaian dokumen harus benar-benar berlaku.
 *
 * Kelas cacat yang dilindungi file ini adalah "opsi bisa diisi, pratinjau
 * berubah, lalu diekspor dan isinya hilang". Opsi seperti itu lebih berbahaya
 * daripada opsi yang tidak ada, karena antarmuka ratification Geography
 * mengiyakan sekarang justru mengonfirmasi sesuatu yang tidak akan terjadi.
 *
 * Empat opsi pernah bermasalah: footer_note dan show_footer_note tidak dibaca
 * renderer mana pun, sedangkan show_signer1_signature dan
 * show_signer2_signature hanya dibaca PDF. Officer mencentang atau mengisi,
 * melihat pratinjaunya berubah, lalu exporting dan tidak menemukan bedanya.
 *
 * Test pertama bersifat struktural dan murah. Empat test berikutnya membuktikan
 * secara fungsional opsi yang pernah bocor.
 */
class DocumentOptionEffectivenessTest extends TestCase
{
    use InspectsDocx;

    /**
     * Field formulir yang bukan opsi penyesuaian dokumen, beserta alasannya.
     *
     * @var array<string,string>
     */
    private const NON_OPTIONS = [
        'notulensi' => 'isi notulen, bukan opsi tampilan',
        'kesimpulan' => 'isi kesimpulan, bukan opsi tampilan',
        'has_document_config' => 'penanda bahwa konfigurasi disimpan',
        'reset_custom_logo' => 'aksi, bukan nilai konfigurasi',
        'custom_logo' => 'unggah berkas; nilai konfigurasinya custom_logo_path',
    ];

    protected function tearDown(): void
    {
        $this->docxCleanup();

        parent::tearDown();
    }

    private function pdfHtml(Agenda $agenda, array $config = []): string
    {
        return app(WordExportService::class)->generateDocumentContent($agenda, $config, 'plain');
    }

    /** The exported package itself, for the helpers that need the ZIP. */
    private function docxFor(Agenda $agenda, array $config = []): string
    {
        return app(DocxExportService::class)->exportBeritaAcara($agenda, $config)->getContent();
    }

    private function docxXmlFor(Agenda $agenda, array $config = []): string
    {
        return $this->docxXml($this->docxFor($agenda, $config));
    }

    /**
     * Nama setiap opsi penyesuaian pada formulir pengisian.
     *
     * @return list<string>
     */
    private function optionKeys(): array
    {
        $blade = (string) file_get_contents(
            base_path('resources/views/agendas/notulen.blade.php')
        );

        preg_match_all('/name="([a-z0-9_]+)"/', $blade, $matches);

        $keys = [];
        foreach (array_unique($matches[1]) as $name) {
            if (! isset(self::NON_OPTIONS[$name])) {
                $keys[] = $name;
            }
        }

        sort($keys);

        return $keys;
    }

    public function test_every_option_on_the_form_is_read_by_both_renderers(): void
    {
        $keys = $this->optionKeys();
        $this->assertNotEmpty($keys, 'Formulir pengisian harus punya opsi untuk diperiksa.');

        $pdf = '';
        foreach ([
            'resources/views/exports/partials/document_body.blade.php',
            'resources/views/partials/kop_surat.blade.php',
        ] as $partial) {
            $pdf .= (string) file_get_contents(base_path($partial));
        }

        $docx = (string) file_get_contents(app_path('Services/DocxExportService.php'));

        $pdfMisses = [];
        $docxMisses = [];

        foreach ($keys as $key) {
            $needle = "/\['".preg_quote($key, '/')."'\]/";

            if (! preg_match($needle, $pdf)) {
                $pdfMisses[] = $key;
            }
            if (! preg_match($needle, $docx)) {
                $docxMisses[] = $key;
            }
        }

        $this->assertSame(
            [],
            $pdfMisses,
            'Opsi ada di formulir tapi tidak dibaca template PDF: '.implode(', ', $pdfMisses)
        );

        $this->assertSame(
            [],
            $docxMisses,
            'Opsi ada di formulir tapi tidak dibaca writer DOCX: '.implode(', ', $docxMisses)
        );
    }

    public function test_footer_note_reaches_the_pdf(): void
    {
        $agenda = Agenda::first();
        $note = 'CATATAN UJI KAKI DOKUMEN';

        $on = $this->pdfHtml($agenda, ['show_footer_note' => true, 'footer_note' => $note]);
        $off = $this->pdfHtml($agenda, ['show_footer_note' => false, 'footer_note' => $note]);

        $this->assertStringContainsString($note, $on);
        $this->assertStringNotContainsString($note, $off);
    }

    public function test_footer_note_reaches_the_docx(): void
    {
        $agenda = Agenda::first();
        $note = 'CATATAN UJI KAKI DOKUMEN';

        $on = $this->docxXmlFor($agenda, ['show_footer_note' => true, 'footer_note' => $note]);
        $off = $this->docxXmlFor($agenda, ['show_footer_note' => false, 'footer_note' => $note]);

        $this->assertStringContainsString($note, $on);
        $this->assertStringNotContainsString($note, $off);

        // Kolom kosong tidak boleh menyisakan garis pemisah tanpa teks.
        $blank = $this->docxXmlFor($agenda, ['show_footer_note' => true, 'footer_note' => '   ']);
        $this->assertStringNotContainsString(
            DocumentLayout::FOOTER_NOTE_RULE_COLOR,
            $blank,
            'Blok kaki harus dilewati seluruhnya bila catatannya kosong.'
        );
    }

    public function test_neither_format_prints_the_system_boilerplate_or_a_print_stamp(): void
    {
        $agenda = Agenda::first();

        $pdf = $this->pdfHtml($agenda, ['show_footer_note' => true, 'footer_note' => 'CATATAN PETUGAS']);
        $docx = $this->docxXmlFor($agenda, ['show_footer_note' => true, 'footer_note' => 'CATATAN PETUGAS']);

        // Dua kalimat ini pernah tercetak tanpa diminta, dan keduanya dihapus:
        // kalimat resmi dari sistem dan stempel waktu cetak. Yang dicetak
        // hanyalah apa yang benar-benar ditulis petugas.
        foreach (['Dicetak pada', 'Dokumen ini diterbitkan secara resmi'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $pdf, "PDF tidak boleh memuat: {$forbidden}");
            $this->assertStringNotContainsString($forbidden, $docx, "DOCX tidak boleh memuat: {$forbidden}");
        }

        $this->assertStringContainsString('CATATAN PETUGAS', $pdf);
        $this->assertStringContainsString('CATATAN PETUGAS', $docx);
    }

    public function test_block_signature_toggles_remove_only_the_marks_from_the_docx(): void
    {
        $agenda = Agenda::first();

        $with = $this->docxTableContaining(
            $this->docxFor($agenda, [
                'show_signer1_signature' => true,
                'show_signer2_signature' => true,
            ]),
            'Mengetahui,'
        );

        $without = $this->docxTableContaining(
            $this->docxFor($agenda, [
                'show_signer1_signature' => false,
                'show_signer2_signature' => false,
            ]),
            'Mengetahui,'
        );

        $this->assertGreaterThan(
            0,
            substr_count($with, '<w:pict>'),
            'Kedua tanda tangan blok harus tertanam saat toggle menyala.'
        );
        $this->assertSame(
            0,
            substr_count($without, '<w:pict>'),
            'Tidak boleh ada gambar tanda tangan saat kedua toggle mati.'
        );

        // The block itself is untouched: same three rows, same names, same NIPs.
        $this->assertCount(3, $this->docxRows($without));
        $this->assertStringContainsString('Ahmad Syukron', $without);
        $this->assertStringContainsString('Dewi Lestari', $without);
    }

    public function test_the_two_formats_print_the_same_number_of_images(): void
    {
        $agenda = Agenda::first();

        // Every image the document carries: the letterhead logo, the attendance
        // selfies and marks, the two block marks and the annex photo. Counting
        // them on both sides is what makes this a parity test rather than a
        // test of one renderer against a hard coded number, which is how the
        // DOCX went on printing marks the officer had switched off.
        $docxImages = fn (string $xml): int => substr_count($xml, '<w:pict>');
        $pdfImages = static function (string $html): int {
            preg_match_all('#<img\b#', $html, $m);

            return count($m[0]);
        };

        foreach ([true, false] as $value) {
            $config = [
                'show_signer1_signature' => $value,
                'show_signer2_signature' => $value,
            ];

            $this->assertSame(
                $pdfImages($this->pdfHtml($agenda, $config)),
                $docxImages($this->docxXmlFor($agenda, $config)),
                "PDF dan DOCX harus mencetak jumlah gambar yang sama (toggle = {$value})."
            );
        }
    }
}
