<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\User;
use App\Services\WordExportService;
use App\Support\DocumentLayout;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

/**
 * Halaman pengisian dan hasil ekspor harus menggambarkan dokumen yang sama.
 *
 * Pratinjau pada halaman pengisian adalah salinan markup yang terpisah dari
 * template ekspor. Salinan itu dulu bebas bergerak sendiri, dan bergeraklah:
 * urutan seksi terbalik, tipografiseparuh ukuran, tiga belas toggle tidak
 *Effects, dan blok tanda tangan tidak pernah bisa punya kolom ketiga. Petugas
 * mengisi dokumen, melihat bentuk yang berbeda dari yang akan diekspor, lalu
 * exporting kecepetan.
 *
 * Test di sini memeriksa dua hal yang berbeda. Yang pertama struktural dan
 * murahan: setiap opsi penyesuaian harus dibaca di dalam area lembar, bukan
 * hanya di dalam formulir. Yang kedua geometri: urutan seksi dan angka tipografi
 * harus diambil dari DocumentLayout, sumber yang sama dengan writer .docx.
 */
class NotulenPreviewParityTest extends TestCase
{
    private const SHEET_OPEN = '<div class="office-paper-sheet paper-a4"';

    private const BLADE = 'resources/views/agendas/notulen.blade.php';

    private function bladeSource(): string
    {
        return (string) file_get_contents(base_path(self::BLADE));
    }

    /**
     * Potongan sumber blade yang berada di dalam lembar cetakan, bukan di dalam
     * formulir pengaturan.
     *
     * Pemotongan dilakukan pada posisi dua tanda pembuka dan penutup lembar,
     * karena nama field pada formulir juga memuat kunci-kunci yang sama dan
     * akan membuat pemeriksaan struktural ini selalu hijau.
     */
    private function sheetSource(): string
    {
        $src = $this->bladeSource();
        $start = strpos($src, self::SHEET_OPEN);

        $this->assertNotFalse($start, 'Tidak menemukan wadah lembar pada halaman pengisian.');

        // Lembar ditutup oleh div terakhir sebelum blok formulir. Ambil sampai
        // kemunculan pertama "Lampiran Foto" pada area pengaturan, yang selalu
        // berada setelah lembar.
        $end = strpos($src, 'Lampiran Foto', $start);

        if ($end === false) {
            $end = strlen($src);
        }

        $sheet = substr($src, $start, $end - $start);

        // Kop surat hidup di partial sendiri dan dirender di dalam lembar, jadi
        // sumbernya ikut dihitung. Tanpa ini, show_kop akan selalu terlihat
        // seperti toggle yang mati.
        $sheet .= (string) file_get_contents(base_path('resources/views/partials/kop_surat.blade.php'));

        return $sheet;
    }

    /**
     * @return list<string>
     */
    private function toggleKeys(): array
    {
        preg_match_all('/name="(show_[a-z0-9_]+)"/', $this->bladeSource(), $m);

        $keys = array_values(array_unique($m[1]));
        sort($keys);

        return $keys;
    }

    public function test_every_toggle_is_honoured_inside_the_sheet(): void
    {
        $sheet = $this->sheetSource();
        $misses = [];

        foreach ($this->toggleKeys() as $key) {
            if (! preg_match("/\\\$config\['".preg_quote($key, '/')."'\]/", $sheet)) {
                $misses[] = $key;
            }
        }

        $this->assertSame(
            [],
            $misses,
            'Toggle ini hanya jadi tanda centang di formulir, tanpa efek pada lembar: '
            .implode(', ', $misses)
        );
    }

    public function test_the_sheet_uses_the_shared_layout_constants(): void
    {
        $sheet = $this->sheetSource();

        $expected = [
            'DOC_TITLE_GAP_TOP_PT',
            'DOC_TITLE_GAP_BOTTOM_PT',
            'DOCUMENT_TITLE_SIZE_PT',
            'INFO_SIZE_PT',
            'INFO_CELL_PAD_PT',
            'DETAIL_LABEL_COLUMN',
            'DETAIL_COLON_COLUMN',
            'DETAIL_VALUE_COLUMN',
            'SECTION_SIZE_PT',
            'SUBHEADING_SIZE_PT',
            'SECTION_GAP_TOP_PT',
            'SECTION_GAP_BOTTOM_PT',
            'BLOCK_GAP_BOTTOM_PT',
            'PROSE_PAD_TOP_PT',
            'PROSE_PAD_SIDE_PT',
            'TTD_GAP_PT',
            'TTD_ROW_HEIGHT_PT',
        ];

        $misses = array_values(array_filter(
            $expected,
            static fn (string $c): bool => ! str_contains($sheet, $c)
        ));

        $this->assertSame(
            [],
            $misses,
            'Lembar harus membaca angka tata letak dari DocumentLayout: '.implode(', ', $misses)
        );
    }

    public function test_the_sheet_carries_no_hardcoded_typography(): void
    {
        $sheet = $this->sheetSource();

        // Angka yang dulu ditulis tangan di lembar dan membuat pratinjau
        // berbeda dari dokumen cetak. Yang tersisa di sini hanya ukuran
        // gambar dan sesekali pergeseran kecil yang memang miliknya sendiri.
        $forbidden = [
            'font-size: 11.5pt',   // judul dokumen, ekspor 14 pt
            'font-size: 9.5pt; font-weight: bold; margin:', // judul seksi
            'font-size: 8.5pt; font-family',   // subjudul
            'font-size: 8pt;',     // sel NIP, ekspor 9 pt
        ];

        $found = array_values(array_filter(
            $forbidden,
            static fn (string $needle): bool => str_contains($sheet, $needle)
        ));

        $this->assertSame(
            [],
            $found,
            'Ukuran font masih ditulis tangan di lembar: '.implode(' | ', $found)
        );
    }

    public function test_the_sheet_and_the_export_agree_on_section_order(): void
    {
        $agenda = Agenda::first();
        $sheet = $this->renderSheet($agenda);
        $export = app(WordExportService::class)->generateDocumentContent($agenda, [], 'plain');

        $this->assertSame(
            $this->sectionOrder($export),
            $this->sectionOrder($sheet),
            'Urutan seksi pada lembar harus sama dengan urutan pada dokumen ekspor.'
        );
    }

    /**
     * Nama seksi berurutan seperti yang muncul di sumber.
     *
     * @return list<string>
     */
    private function sectionOrder(string $html): array
    {
        preg_match_all(
            '/\b(I{1,3})\. (NOTULENSI|DAFTAR KEHADIRAN|LAMPIRAN FOTO)\b/',
            $html,
            $matches,
            PREG_OFFSET_CAPTURE | PREG_SET_ORDER
        );

        $found = [];
        foreach ($matches as $match) {
            $found[] = [$match[0][1], $match[1][0].'. '.$match[2][0]];
        }

        usort($found, static fn (array $a, array $b): int => $a[0] <=> $b[0]);

        return array_column($found, 1);
    }

    public function test_the_sheet_supports_a_third_signer(): void
    {
        $agenda = Agenda::first();
        $config = $agenda->report_config;

        $two = $this->renderSheet($agenda, $config);
        $three = $this->renderSheet($agenda, ['show_signer3' => true, 'signer3_role' => 'Kepala LLDIKTI']);

        $this->assertStringNotContainsString('Menyetujui,', $two);
        $this->assertStringContainsString('Menyetujui,', $three, 'Kolom ketiga harus muncul saat show_signer3 menyala.');
        $this->assertStringContainsString('Kepala LLDIKTI', $three);
    }

    public function test_the_sheet_supports_switching_off_the_block_signatures(): void
    {
        $agenda = Agenda::first();

        $on = $this->signatureBlock($this->renderSheet($agenda, [
            'show_signer1_signature' => true,
            'show_signer2_signature' => true,
        ]));
        $off = $this->signatureBlock($this->renderSheet($agenda, [
            'show_signer1_signature' => false,
            'show_signer2_signature' => false,
        ]));

        $this->assertStringContainsString('alt="TTD"', $on);
        $this->assertStringNotContainsString('alt="TTD"', $off, 'Tanda tangan blok harus hilang dari lembar.');

        // Nama, jabatan, dan NIP tetap ada: yang dimatikan hanya mark-nya.
        $this->assertStringContainsString('Mengetahui,', $off);
        $this->assertStringContainsString('Notulis Rapat', $off);
    }

    /**
     * Potongan markup blok tanda tangan saja.
     *
     * Sel tanda tangan pada tabel kehadiran memakai alt="TTD" juga, tetapi
     * dikendalikan oleh show_attendee_signatures yang lain tuju, jadi seluruh
     * lembar tidak bisa dijadikan dasar assertion ini.
     */
    private function signatureBlock(string $html): string
    {
        $start = strpos($html, 'class="signature-block"');
        $this->assertNotFalse($start, 'Blok tanda tangan tidak ditemukan pada lembar.');

        $end = strpos($html, 'document-footer-note', $start);

        return $end === false
            ? substr($html, $start)
            : substr($html, $start, $end - $start);
    }

    /**
     * Merender lembar saja, tanpa chassis halaman.
     *
     * @param  array<string,mixed>  $override
     */
    private function renderSheet(Agenda $agenda, array $override = []): string
    {
        $admin = User::where('role', 'administrator')->first() ?? User::orderBy('id')->first();
        $this->actingAs($admin);

        $config = array_merge($agenda->report_config, $override);

        // Lembar memuat blok @error. View yang dirender langsung di dalam test
        // tidak melewati middleware, jadi tas galat harus dibagikan manual.
        view()->share('errors', new ViewErrorBag);

        $html = view('agendas.notulen', [
            'agenda' => $agenda->loadMissing(['creator.unit', 'pimpinan.unit', 'notulis.unit', 'units']),
            'documentations' => $agenda->documentations()->latest('id')->paginate(6, ['*'], 'page_docs'),
            'config' => $config,
            'attendances' => $agenda->attendances()->with('user.unit')->orderBy('signed_at', 'asc')->get(),
        ])->render();

        $start = strpos($html, self::SHEET_OPEN);
        $this->assertNotFalse($start, 'Lembar tidak ditemukan pada hasil render.');

        return substr($html, $start);
    }

    /**
     * Nilai yang harus sama di kedua sisi, diperiksa sekali agar kelas
     * pengujian ini tidak diam-diam kehilangan sebagian cakupannya.
     */
    public function test_the_shared_constants_still_carry_the_approved_values(): void
    {
        // Sengaja assertEquals, bukan assertSame: yang dijaga di sini adalah
        // nilainya, bukan apakah konstanta itu ditulis 36 atau 36.0.
        $expected = [
            'DOCUMENT_TITLE_SIZE_PT' => 14.0,
            'SECTION_SIZE_PT' => 12.0,
            'SUBHEADING_SIZE_PT' => 12.0,
            'INFO_SIZE_PT' => 12.0,
            'BODY_SIZE_PT' => 12.0,
            'BODY_LINE_PT' => 18.0,
            'ATTENDANCE_SIZE_PT' => 9.0,
            'ATTENDANCE_LINE_PT' => 11.0,
            'INFO_CELL_PAD_PT' => 1.5,
            'BORDER_PT' => 1.0,
            'TTD_GAP_PT' => 18.0,
            'TTD_ROW_HEIGHT_PT' => 36.0,
            'DOC_TITLE_GAP_TOP_PT' => 12.0,
            'DOC_TITLE_GAP_BOTTOM_PT' => 6.0,
            'SECTION_GAP_TOP_PT' => 6.0,
            'SECTION_GAP_BOTTOM_PT' => 3.0,
            'SUBHEADING_GAP_BOTTOM_PT' => 1.5,
            'BLOCK_GAP_BOTTOM_PT' => 6.0,
            'PROSE_PAD_TOP_PT' => 3.0,
            'PROSE_PAD_SIDE_PT' => 5.0,
        ];

        $drifted = [];

        foreach ($expected as $name => $value) {
            $constant = constant(DocumentLayout::class.'::'.$name);

            if ($constant != $value) {
                $drifted[] = $name.' = '.var_export($constant, true).' (harusnya '.$value.')';
            }
        }

        $this->assertSame([], $drifted, 'Nilai tata letak yang sudah disetujui berubah: '.implode('; ', $drifted));

        $this->assertSame('24%', DocumentLayout::DETAIL_LABEL_COLUMN);
        $this->assertSame('2%', DocumentLayout::DETAIL_COLON_COLUMN);
        $this->assertSame('74%', DocumentLayout::DETAIL_VALUE_COLUMN);
        $this->assertSame(['4%', '15%', '12%', '9%', '15%', '15%'], DocumentLayout::attendanceColumns());
    }
}
