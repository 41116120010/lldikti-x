<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Pins the official letterhead standard for the Berita Acara export.
 *
 * The letterhead used to exist twice, as near-identical markup in the on-screen
 * workstation and in the export partial, with the typography copied between the
 * two by hand. Copies drift, and the drift here was invisible: both sides still
 * rendered a plausible-looking document, so nobody noticed that the printed
 * page and the preview had come apart.
 *
 * Two guarantees are asserted here:
 *
 *  1. The typographic scale follows the government letterhead convention
 *     (opening line largest and bold, hierarchy descending, absolute line
 *     heights so Word and LibreOffice agree on the baselines).
 *  2. The workstation and the export emit byte-identical letterhead markup, so
 *     the two cannot drift apart again. This is the assertion that actually
 *     prevents a regression; the first one only documents the current values.
 */
class KopSuratStandardTest extends TestCase
{
    use DatabaseTransactions;

    private User $superadmin;

    private Agenda $agenda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::where('role', 'administrator')->firstOrFail();
        $this->agenda = Agenda::firstOrFail();
    }

    public function test_letterhead_follows_the_official_typographic_scale(): void
    {
        $html = $this->exportHtml();

        // Typography follows the official LLDIKTI letterhead: the ministry line is
        // the largest but is NOT bold, and the implementing agency is the one
        // that is bold. The previous design had this inverted.
        $this->assertStringContainsString('font-size: 16pt; line-height: 19pt;', $html);
        $this->assertStringContainsString('font-size: 14pt; line-height: 17pt;', $html);
        $this->assertStringContainsString('font-size: 12pt; line-height: 14pt;', $html);

        $this->assertMatchesRegularExpression(
            '/id="sheet-instansi-induk"[^>]*font-size: 16pt[^>]*font-weight: normal/',
            $html,
            'Nama Kementerian harus 16 pt dan tidak ditebalkan.'
        );
        $this->assertMatchesRegularExpression(
            '/id="sheet-instansi-pelaksana"[^>]*font-size: 14pt[^>]*font-weight: bold/',
            $html,
            'Nama lembaga pelaksana harus 14 pt dan ditebalkan.'
        );

        // Absolute line heights, so both renderers compute the same baselines.
        $this->assertStringContainsString('mso-line-height-rule: exactly', $html);

        // A solid rule, not the double rule used for academic papers.
        $this->assertStringContainsString('border-bottom: 1pt solid #000000', $html);
        $this->assertStringNotContainsString('2.25pt double', $html);

        // Letter spacing is not part of the convention and is unreliable in the
        // Word import path.
        $this->assertStringNotContainsString('letter-spacing', $html);
    }

    public function test_every_font_in_the_document_is_times_new_roman(): void
    {
        $html = $this->exportHtml();

        // Courier New was used for the document number and a generic monospace
        // for the NIP column. Both were confirmed to reach the PDF as real
        // embedded fonts (CourierNewPSMT and DejaVuSansMono), so the document
        // was not Times New Roman throughout.
        $this->assertStringNotContainsString('Courier', $html);
        $this->assertStringNotContainsString('monospace', $html);
        $this->assertStringContainsString("font-family: 'Times New Roman', Times, serif", $html);
    }

    public function test_the_page_margin_is_declared_and_measurable_on_the_page(): void
    {
        $html = $this->exportHtml();

        // Measured against the official LLDIKTI letterhead: left 1.91 cm and
        // right 1.41 cm, carried over to A4 almost unchanged. Top and bottom are
        // deliberately NOT 0.81 cm and 0.47 cm as in the reference, because
        // those fall inside the unprintable band of many office printers.
        $this->assertStringContainsString('@page', $html);
        $this->assertStringContainsString('margin: 1.5cm 2cm 1.5cm 2cm', $html);

        // The bare @page is what Dompdf reads; the named one is Word's. The
        // margin has to be declared in both, so the test accepts either form.
        $this->assertMatchesRegularExpression(
            '/@page( Section1)?\s*\{[^}]*margin: 1\.5cm 2cm 1\.5cm 2cm/',
            $html
        );
    }

    public function test_letterhead_avoids_css_the_office_renderers_ignore(): void
    {
        $kop = $this->extractKop($this->exportHtml());

        // calc() is not evaluated by the Word or LibreOffice HTML importers, and
        // app.css forces .office-paper-sheet table to width:100% !important,
        // which discards a <colgroup> in the workstation anyway. Fixed layout with
        // widths on the cells themselves is honoured by all three renderers.
        $this->assertStringNotContainsString('calc(', $kop);
        $this->assertStringNotContainsString('<colgroup', $kop);
        $this->assertStringContainsString('table-layout: fixed', $kop);

        // object-fit is ignored by the Word importer, so a logo would be stretched
        // rather than fitted. The template passes real pixel dimensions instead.
        $this->assertStringNotContainsString('object-fit', $kop);
    }

    public function test_the_detail_table_declares_its_widths_on_the_cells_not_in_a_colgroup(): void
    {
        $html = $this->exportHtml();

        // Dompdf ignores <colgroup>, so widths declared there silently do
        // nothing and the three columns come out equal. That pushed the colon
        // to the middle of the page and squeezed the values into the right
        // third, which is what "isi terlalu mepet ke kanan" looked like.
        // The widths therefore live on the <td> themselves.
        $this->assertStringNotContainsString('<colgroup>', $html);

        $this->assertSame(5, preg_match_all('/<td[^>]*width: 24%;/', $html), 'Kolom label harus 24%.');
        $this->assertSame(5, preg_match_all('/<td[^>]*width: 2%;/', $html), 'Kolom titik dua harus 2%.');
        $this->assertSame(5, preg_match_all('/<td[^>]*width: 74%;/', $html), 'Kolom isi harus 74%.');
    }

    public function test_workstation_and_export_render_an_identical_letterhead(): void
    {
        $export = $this->extractKop($this->exportHtml());

        $workstationResponse = $this->actingAs($this->superadmin)
            ->get(route('admin.agendas.notulen', $this->agenda));
        $workstationResponse->assertStatus(200);
        $workstation = $this->extractKop($workstationResponse->getContent());

        $this->assertSame(
            $export,
            $workstation,
            'Kop surat pada workstation dan ekspor harus identik. Jika berbeda, '
            .'pratinjau layar tidak akan sama dengan dokumen yang dicetak.'
        );
    }

    /**
     * The rendered word/PDF export document.
     */
    private function exportHtml(): string
    {
        $response = $this->actingAs($this->superadmin)
            ->get(route('admin.reports.export.word', $this->agenda));
        $response->assertStatus(200);

        return $response->getContent();
    }

    /**
     * The letterhead block on its own, with the logo element removed.
     *
     * The logo legitimately differs: the export inlines a base64 data URI of a
     * resized copy, while the workstation points at the original file. Stripping
     * the element leaves exactly the structure and typography that must match.
     */
    private function extractKop(string $html): string
    {
        $start = strpos($html, 'id="sheet-header-kop"');
        $this->assertNotFalse($start, 'Kop surat tidak ditemukan pada keluaran.');

        $end = strpos($html, '<!-- Judul Dokumen', $start);
        $this->assertNotFalse($end, 'Penanda akhir kop surat tidak ditemukan.');

        $kop = substr($html, $start, $end - $start);
        $kop = preg_replace('/<img\b[^>]*>/i', '', $kop);

        // The two call sites are indented differently; collapse whitespace so only
        // the markup itself is compared.
        return trim((string) preg_replace('/\s+/', ' ', $kop));
    }
}
