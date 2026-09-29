<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SignatureColumnStandardizationTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private User $pimpinan;
    private User $notulis;
    private Agenda $agenda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', 'administrator')->first() ?? User::create([
            'name' => 'Admin Signature Test',
            'username' => 'admin_sig_test_' . uniqid(),
            'email' => 'admin_sig_' . uniqid() . '@lldikti.test',
            'nip' => '198001012005011001',
            'password' => bcrypt('password'),
            'role' => 'administrator',
            'is_active' => true,
        ]);

        $this->pimpinan = User::create([
            'name' => 'Dr. H. Hendra Suherman, S.T., M.T.',
            'username' => 'pimpinan_' . uniqid(),
            'email' => 'pimpinan_' . uniqid() . '@lldikti.test',
            'nip' => '197405151999031002',
            'password' => bcrypt('password'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $this->notulis = User::create([
            'name' => 'Rina Kartika, S.Kom., M.Cs.',
            'username' => 'notulis_' . uniqid(),
            'email' => 'notulis_' . uniqid() . '@lldikti.test',
            'nip' => '199208182018012003',
            'password' => bcrypt('password'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $unit = $this->admin->unit ?? Unit::first();

        $this->agenda = Agenda::create([
            'unit_id' => $unit?->id,
            'created_by' => $this->admin->id,
            'pimpinan_id' => $this->pimpinan->id,
            'notulis_id' => $this->notulis->id,
            'judul_rapat' => 'Rapat Uji Standardisasi Kolom Tanda Tangan',
            'slug' => 'rapat-uji-ttd-' . uniqid(),
            'deskripsi' => 'Pengujian format sejajar lurus, NIP tanpa titik, dan nama tanpa garis bawah.',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => now()->setTime(9, 0),
            'waktu_selesai' => now()->setTime(11, 0),
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Rapat LLDIKTI',
            'status' => 'completed',
            'is_all_units' => true,
            'notulensi' => '<p>Catatan pembahasan rapat dinas.</p>',
            'kesimpulan' => '<p>Keputusan bulat disetujui bersama.</p>',
        ]);
    }

    public function test_agendas_show_signature_block_format(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.agendas.show', $this->agenda));

        $response->assertStatus(200);

        // 1. Leader signature block
        $response->assertSee($this->pimpinan->name);
        $response->assertSee('NIP ' . $this->pimpinan->nip);
        $response->assertDontSee('NIP. ' . $this->pimpinan->nip);

        // 2. Notulis signature block
        $response->assertSee($this->notulis->name);
        $response->assertSee('NIP ' . $this->notulis->nip);
        $response->assertDontSee('NIP. ' . $this->notulis->nip);

        // 3. Straight vertical alignment wrapper (inline-block text-left) within centered table
        $response->assertSee('class="inline-block text-left space-y-0.5"', false);
        $response->assertSee('align="center"', false);
        $response->assertSee('display: inline-table', false);

        // 4. Name is NOT underlined
        $response->assertDontSee('<u>' . $this->pimpinan->name . '</u>', false);
        $response->assertDontSee('<u>' . $this->notulis->name . '</u>', false);
    }

    public function test_reports_show_signature_block_format(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.reports.show', $this->agenda));

        $response->assertStatus(200);

        // 1. Leader signature block
        $response->assertSee($this->pimpinan->name);
        $response->assertSee('NIP ' . $this->pimpinan->nip);
        $response->assertDontSee('NIP. ' . $this->pimpinan->nip);

        // 2. Notulis signature block
        $response->assertSee($this->notulis->name);
        $response->assertSee('NIP ' . $this->notulis->nip);
        $response->assertDontSee('NIP. ' . $this->notulis->nip);

        // 3. Straight vertical alignment wrapper (inline-block text-left) within centered table
        $response->assertSee('class="inline-block text-left space-y-0.5"', false);
        $response->assertSee('align="center"', false);
        $response->assertSee('display: inline-table', false);

        // 4. Name is NOT underlined
        $response->assertDontSee('<u>' . $this->pimpinan->name . '</u>', false);
        $response->assertDontSee('<u>' . $this->notulis->name . '</u>', false);
    }

    public function test_notulen_workstation_signature_block_format(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.agendas.notulen', $this->agenda));

        $response->assertStatus(200);

        // 1. Element IDs exist for live preview JS
        $response->assertSee('id="sheet-signer1-name"', false);
        $response->assertSee('id="sheet-signer2-name"', false);
        $response->assertSee('id="sheet-signer1-nip"', false);
        $response->assertSee('id="sheet-signer2-nip"', false);

        // 2. Name is NOT underlined (no <u> tag)
        $response->assertDontSee('<u id="sheet-signer1-name">', false);
        $response->assertDontSee('<u id="sheet-signer2-name">', false);

        // 3. NIP label has NO dot
        $response->assertSee('NIP <span id="sheet-signer1-nip">', false);
        $response->assertSee('NIP <span id="sheet-signer2-nip">', false);
        $response->assertDontSee('NIP. <span id="sheet-signer1-nip">', false);
        $response->assertDontSee('NIP. <span id="sheet-signer2-nip">', false);

        // 4. Straight vertical alignment with centered table structure and fixed 50% width
        $response->assertSee('align="center"', false);
        $response->assertSee('margin: 0 auto', false);
        $response->assertSee('text-align: left;', false);
        $response->assertSee('table-layout: fixed', false);
        $response->assertSee('width="50%"', false);
        $response->assertSee('margin-top: 18pt;', false);

        // 5. Poin 66: Verify that Mengetahui, role, name, and NIP are in a unified nested table (sharing exact vertical alignment)
        $content = $response->getContent();
        $this->assertMatchesRegularExpression('/<table[^>]*align="center"[^>]*>[\s\S]*?Mengetahui,[\s\S]*?sheet-signer1-role[\s\S]*?sheet-signer1-name[\s\S]*?sheet-signer1-nip[\s\S]*?<\/table>/', $content);
        $this->assertMatchesRegularExpression('/<table[^>]*align="center"[^>]*>[\s\S]*?sheet-signing-city[\s\S]*?sheet-signer2-role[\s\S]*?sheet-signer2-name[\s\S]*?sheet-signer2-nip[\s\S]*?<\/table>/', $content);
    }

    public function test_document_body_export_signature_block_format(): void
    {
        $wordResponse = $this->actingAs($this->admin)->get(route('admin.reports.export.word', $this->agenda));
        $wordResponse->assertStatus(200);

        $content = $wordResponse->getContent();

        // 1. Signature block has centred wrappers whose width follows the column.
        // The width used to be a hard 175pt / 135pt, which is what made the
        // blocks drift out of their columns whenever the page margin changed.
        $this->assertStringContainsString('display: inline-block;', $content);
        $this->assertStringContainsString('width: 68%', $content);
        $this->assertStringNotContainsString('width: 175pt', $content);
        $this->assertStringNotContainsString('width: 135pt', $content);
        $this->assertStringContainsString('text-align: left;', $content);

        // 2. Balanced 50% columns with table-layout fixed
        $this->assertStringContainsString('table-layout: fixed', $content);
        $this->assertStringContainsString('width="50%"', $content);

        // 3. NIP has NO dot in export
        $this->assertStringContainsString('NIP ' . $this->pimpinan->nip, $content);
        $this->assertStringContainsString('NIP ' . $this->notulis->nip, $content);
        $this->assertStringNotContainsString('NIP. ' . $this->pimpinan->nip, $content);
        $this->assertStringNotContainsString('NIP. ' . $this->notulis->nip, $content);

        // 4. Names have NO underline in export
        $this->assertStringNotContainsString('<u>' . $this->pimpinan->name . '</u>', $content);
        $this->assertStringNotContainsString('<u>' . $this->notulis->name . '</u>', $content);

        // 5. Poin 66: In Word/PDF export, Mengetahui, role, and name/NIP are in symmetrical aligned wrappers
        $this->assertStringContainsString('Mengetahui,', $content);
        $this->assertStringContainsString('Pemimpin Rapat', $content);
        $this->assertStringContainsString($this->pimpinan->name, $content);
        $this->assertStringContainsString('Notulis Rapat', $content);
        $this->assertStringContainsString($this->notulis->name, $content);
    }

    public function test_export_document_layout_and_spacing_synchronization(): void
    {
        $wordResponse = $this->actingAs($this->admin)->get(route('admin.reports.export.word', $this->agenda));
        $wordResponse->assertStatus(200);

        $content = $wordResponse->getContent();

        // 1. Kop surat memakai skala tipografi resmi instansi, bukan nilai lama
        //    (10pt / 11.5pt / 8pt dengan line-height 1.25 dan letter-spacing).
        //    Rincian lengkap dipin di KopSuratStandardTest.
        $this->assertStringContainsString('font-size: 16pt; line-height: 19pt;', $content);
        $this->assertStringContainsString('font-size: 14pt; line-height: 17pt;', $content);
        $this->assertStringContainsString('font-size: 12pt; line-height: 14pt;', $content);
        $this->assertStringContainsString('border-bottom: 1pt solid #000000', $content);
        $this->assertStringNotContainsString('letter-spacing', $content);

        // 2. Bab 1 (Daftar Hadir) spacing & font
        $this->assertStringContainsString('I. DAFTAR KEHADIRAN PESERTA', $content);
        $this->assertStringContainsString('font-size: 12pt; font-weight: bold; margin: 6pt 0 3pt 0', $content);

        // 3. Bab 2 (Notulensi & Kesimpulan) spacing & font
        $this->assertStringContainsString('II. NOTULENSI &amp; KESIMPULAN RAPAT', $content);
        $this->assertStringContainsString('margin-top: 6pt', $content);

        // 4. Signature block placed right after Bab 2 with dignified 18pt margin
        $this->assertStringContainsString('class="signature-block" style="margin-top: 18pt;', $content);

        // 5. Sequence: Bab 1 -> Bab 2 -> Signature Block
        $posBab1 = strpos($content, 'I. DAFTAR KEHADIRAN PESERTA');
        $posBab2 = strpos($content, 'II. NOTULENSI &amp; KESIMPULAN RAPAT');
        $posSig = strpos($content, 'class="signature-block"');

        $this->assertNotFalse($posBab1);
        $this->assertNotFalse($posBab2);
        $this->assertNotFalse($posSig);
        $this->assertTrue($posBab1 < $posBab2);
        $this->assertTrue($posBab2 < $posSig);
    }
}
