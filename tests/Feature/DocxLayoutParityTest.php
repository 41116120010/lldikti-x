<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Services\DocxExportService;
use App\Support\DocumentLayout;
use Tests\Support\InspectsDocx;
use Tests\TestCase;

/**
 * The .docx export has to land in the same places the PDF does.
 *
 * Every assertion here is about an attribute that has to survive the trip into
 * word/document.xml. The failure mode this file exists to stop is specific and
 * it is quiet: PhpWord 0.18 accepts a call, writes no warning, and drops the
 * formatting. The document still opens, still looks like a document, and the
 * assertion that would have caught it is nowhere in sight.
 *
 * The three that cost the most time:
 *
 *  - A table whose width is written as w:type="auto" is sized by Word to its
 *    contents, and w:tblGrid is ignored. The seven column attendance table
 *    collapsed from 17 cm to about 11 cm while every cell width in the XML
 *    still read correctly.
 *  - Two <w:tbl> elements with nothing between them are one table to Word and
 *    to LibreOffice, so the ruled attendance grid was drawn straight across the
 *    borderless signature block below it.
 *  - addText() takes the run style second and the paragraph style third. Handing
 *    it a two element array in the second slot styles nothing at all, and the
 *    attendance header came out as unstyled, unbold, left aligned body text.
 */
class DocxLayoutParityTest extends TestCase
{
    use InspectsDocx;

    protected function tearDown(): void
    {
        $this->docxCleanup();

        parent::tearDown();
    }

    private function docxFor(Agenda $agenda): string
    {
        return app(DocxExportService::class)->exportBeritaAcara($agenda)->getContent();
    }

    public function test_every_table_declares_its_width_in_twips(): void
    {
        $body = $this->docxFor(Agenda::first());
        $expected = DocumentLayout::cmToTwips(DocumentLayout::contentWidthCm());

        $tables = $this->docxTables($body);
        $this->assertNotEmpty($tables, 'Dokumen harus memuat setidaknya satu tabel.');

        foreach ($tables as $index => $table) {
            $this->assertMatchesRegularExpression(
                '#<w:tblW w:w="'.$expected.'" w:type="dxa"/>#',
                $table,
                "Tabel #{$index} harus menyatakan lebarnya dalam twip, bukan auto. "
                .'Type auto membuat Word mengabaikan w:tblGrid dan membungkus kolom pada isi.'
            );
        }
    }

    public function test_no_two_tables_are_adjacent(): void
    {
        $xml = $this->docxXml($this->docxFor(Agenda::first()));

        // Two w:tbl siblings with nothing between them are read as ONE table, so
        // the first table's grid and borders silently apply to the second's rows.
        $this->assertSame(
            0,
            preg_match_all('#</w:tbl>\s*<w:tbl>#', $xml),
            'Tidak boleh ada dua tabel bersebelahan tanpa paragraf pemisah di antaranya.'
        );
    }

    public function test_only_the_ruled_tables_carry_a_border(): void
    {
        $body = $this->docxFor(Agenda::first());
        $xml = $this->docxXml($body);

        $ruled = 0;
        $borderless = 0;

        foreach ($this->docxTables($body) as $table) {
            if (str_contains($table, '<w:tblBorders>')) {
                $ruled++;
            } else {
                $borderless++;
            }
        }

        // The two prose boxes and the attendance table, nothing else. The kop,
        // the meeting detail table, the signature block and the annex are open.
        $this->assertSame(3, $ruled, 'Hanya dua kotak notulensi dan tabel kehadiran yang bergaris.');
        $this->assertGreaterThanOrEqual(4, $borderless, 'Kop, detail, tanda tangan dan lampiran harus tanpa garis.');
        $this->assertStringNotContainsString('w:sz="0"', $xml, 'Garis berukuran nol tetap digambar LibreOffice.');
    }

    public function test_signature_block_is_three_rows_wide_not_nine_rows_deep(): void
    {
        $body = $this->docxFor(Agenda::first());
        $table = $this->docxTableContaining($body, 'Mengetahui,');
        $rows = $this->docxRows($table);
        $signers = count($this->docxGridColumns($table));

        $this->assertGreaterThanOrEqual(2, $signers);
        $this->assertCount(
            3,
            $rows,
            'Blok tanda tangan harus tiga baris: label, tanda tangan, nama. '
            .'Satu baris per penanda tangan menyusun para penanda secara vertikal.'
        );

        foreach ($rows as $index => $row) {
            $this->assertCount(
                $signers,
                $row,
                "Baris #{$index} blok tanda tangan harus punya satu sel per penanda tangan."
            );
        }
    }

    public function test_signature_text_sits_in_the_same_narrow_block_as_the_pdf(): void
    {
        $body = $this->docxFor(Agenda::first());
        $table = $this->docxTableContaining($body, 'Mengetahui,');

        $columns = $this->docxGridColumns($table);
        $inner = DocumentLayout::TTD_INNER_WIDTH_TWO_COLUMN;
        $expected = (int) round($columns[0] * (100 - (float) rtrim($inner, '%')) / 200);

        // The PDF centres a 68 % block inside each column; Word needs the same
        // offset reserved on BOTH sides or the line wraps wider than the block.
        $this->assertStringContainsString(
            '<w:ind w:left="'.$expected.'" w:right="'.$expected.'"/>',
            $table,
            'Blok tanda tangan harus menjorok simetris agar texts mulai dan berhenti di posisi yang sama dengan PDF.'
        );
    }

    public function test_attendance_header_cells_are_actually_styled(): void
    {
        $body = $this->docxFor(Agenda::first());
        $table = $this->docxTableContaining($body, 'Nama Lengkap');
        $header = $this->docxRows($table)[0];

        $this->assertNotEmpty($header);

        foreach ($header as $index => $cell) {
            $this->assertStringContainsString(
                'Times New Roman',
                $cell,
                "Sel header #{$index} harus memakai Times New Roman pada 9 pt."
            );
            $this->assertMatchesRegularExpression(
                '#<w:b w:val="1"/>#',
                $cell,
                "Sel header #{$index} harus tebal."
            );
        }

        // "No" and "Waktu" are centred, the wide name and NIP columns are not.
        $this->assertStringContainsString('<w:jc w:val="center"/>', $header[0]);
        $this->assertStringNotContainsString('<w:jc w:val="center"/>', $header[1]);
    }

    public function test_letterhead_rule_is_a_merged_cell_border(): void
    {
        $body = $this->docxFor(Agenda::first());
        $kop = $this->docxTableContaining($body, 'KEMENTERIAN PENDIDIKAN');
        $eighths = DocumentLayout::ptToBorderEighths(DocumentLayout::RULE_WEIGHT_PT);

        // The closing rule under the letterhead is a merged second row, not a
        // table border: a table border would also box the logo and the text.
        $this->assertStringNotContainsString('<w:tblBorders>', $kop);
        $this->assertStringContainsString('<w:gridSpan w:val="2"/>', $kop);
        $this->assertStringContainsString('<w:bottom w:val="single" w:sz="'.$eighths.'"', $kop);
    }

    public function test_images_are_centred_except_the_block_signatures(): void
    {
        $body = $this->docxFor(Agenda::first());
        $tables = $this->docxTables($body);
        $centred = 0;
        $left = 0;

        foreach ($tables as $table) {
            foreach ($this->docxRows($table) as $row) {
                foreach ($row as $cell) {
                    if (! str_contains($cell, '<w:pict>')) {
                        continue;
                    }

                    if (str_contains($cell, '<w:jc w:val="center"/>')) {
                        $centred++;
                    } else {
                        $left++;
                    }
                }
            }
        }

        // The kop logo, three selfies, three attendance signatures, two block
        // signatures and the annex photo are centred. The two signatures inside
        // the block follow its left aligned inner box, exactly as in the PDF.
        $this->assertSame(2, $left, 'Hanya dua tanda tangan blok yang boleh rata kiri.');
        $this->assertGreaterThanOrEqual(8, $centred, 'Gambar sisanya harus terpusat di dalam selnya.');
    }

    public function test_annex_frame_colour_matches_the_pdf(): void
    {
        $body = $this->docxFor(Agenda::first());
        $tables = $this->docxTables($body);

        // The annex is the last table in the document.
        $annex = end($tables);
        $this->assertNotFalse($annex);

        $this->assertStringContainsString(
            '<w:tcBorders>',
            $annex,
            'Setiap foto lampiran harus berbingkai, seperti di PDF.'
        );
        $this->assertStringContainsString(
            'w:color="'.DocumentLayout::PHOTO_FRAME_COLOR.'"',
            $annex,
            'Warna bingkai lampiran harus sama dengan PDF.'
        );
        $this->assertStringNotContainsString('<w:tblBorders>', $annex, 'Lampiran tidak punya garis pemisah kolom.');
    }
}
