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

        // 3. Straight vertical alignment wrapper (inline-block text-left)
        $response->assertSee('class="inline-block text-left space-y-0.5"', false);

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

        // 3. Straight vertical alignment wrapper (inline-block text-left)
        $response->assertSee('class="inline-block text-left space-y-0.5"', false);

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

        // 4. Straight vertical alignment wrapper (display: inline-block; text-align: left;)
        $response->assertSee('display: inline-block; text-align: left;', false);
    }

    public function test_document_body_export_signature_block_format(): void
    {
        $wordResponse = $this->actingAs($this->admin)->get(route('admin.reports.export.word', $this->agenda));
        $wordResponse->assertStatus(200);

        $content = $wordResponse->getContent();

        // 1. Signature block has straight vertical alignment
        $this->assertStringContainsString('display: inline-block; text-align: left;', $content);

        // 2. NIP has NO dot in export
        $this->assertStringContainsString('NIP ' . $this->pimpinan->nip, $content);
        $this->assertStringContainsString('NIP ' . $this->notulis->nip, $content);
        $this->assertStringNotContainsString('NIP. ' . $this->pimpinan->nip, $content);
        $this->assertStringNotContainsString('NIP. ' . $this->notulis->nip, $content);

        // 3. Names have NO underline in export
        $this->assertStringNotContainsString('<u>' . $this->pimpinan->name . '</u>', $content);
        $this->assertStringNotContainsString('<u>' . $this->notulis->name . '</u>', $content);
    }
}
