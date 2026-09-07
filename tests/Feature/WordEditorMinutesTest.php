<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WordEditorMinutesTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private User $staff;
    private Agenda $agenda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', 'administrator')->first() ?? User::create([
            'name' => 'Admin Test Editor',
            'username' => 'admin_test_editor_' . uniqid(),
            'email' => 'admin_editor_' . uniqid() . '@lldikti.test',
            'nip' => '198801012010011001',
            'password' => bcrypt('password'),
            'role' => 'administrator',
            'is_active' => true,
        ]);

        $this->staff = User::where('role', 'staff')->first() ?? User::create([
            'name' => 'Pegawai Test Editor',
            'username' => 'staff_test_editor_' . uniqid(),
            'email' => 'staff_editor_' . uniqid() . '@lldikti.test',
            'nip' => '199505052020011002',
            'password' => bcrypt('password'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $unit = $this->admin->unit ?? Unit::first();

        $this->agenda = Agenda::create([
            'unit_id' => $unit?->id,
            'created_by' => $this->admin->id,
            'judul_rapat' => 'Rapat Koordinasi Uji Editor Word',
            'slug' => 'rapat-koordinasi-uji-editor-word-' . uniqid(),
            'deskripsi' => 'Pengujian toolbar editor Word pada notulensi rapat.',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => now()->setTime(9, 0),
            'waktu_selesai' => now()->setTime(11, 0),
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Sidang Utama',
            'status' => 'completed',
            'notulensi' => null,
            'kesimpulan' => null,
        ]);
    }

    public function test_accessors_support_both_legacy_plain_text_and_rich_html(): void
    {
        // 1. Empty/null handling
        $this->assertNull($this->agenda->formatted_notulensi);
        $this->assertNull($this->agenda->formatted_kesimpulan);

        // 2. Legacy plain text conversion with line breaks
        $this->agenda->notulensi = "Poin pertama\nPoin kedua\nPoin ketiga";
        $this->assertStringContainsString("<br />", $this->agenda->formatted_notulensi);
        $this->assertStringContainsString("Poin pertama", $this->agenda->formatted_notulensi);

        // 3. Rich HTML text preserved
        $richHtml = "<h2>Agenda Pokok</h2><ul><li>Diskusi Anggaran</li><li>Timeline Pelaksanaan</li></ul>";
        $this->agenda->notulensi = $richHtml;
        $this->assertSame($richHtml, $this->agenda->formatted_notulensi);
    }

    public function test_admin_can_update_minutes_with_rich_text_formatting(): void
    {
        $richNotulensi = "<h2>Jalannya Rapat</h2><p>Pimpinan membuka rapat dengan <b>tegas</b> dan <i>lugas</i>.</p><ul><li>Poin A: Realisasi anggaran 90%</li><li>Poin B: Evaluasi server</li></ul>";
        $richKesimpulan = "<blockquote>Arahan Pimpinan: Percepat integrasi data minggu ini.</blockquote><ol><li>RTL 1: Penyiapan API</li></ol>";

        $response = $this->actingAs($this->admin)->put(
            route('admin.agendas.update-notulen', $this->agenda),
            [
                'notulensi' => $richNotulensi,
                'kesimpulan' => $richKesimpulan,
            ]
        );

        $response->assertRedirect(route('admin.agendas.show', $this->agenda));
        $response->assertSessionHas('success');

        $this->agenda->refresh();
        $this->assertStringContainsString('<h2>Jalannya Rapat</h2>', $this->agenda->notulensi);
        $this->assertStringContainsString('<b>tegas</b>', $this->agenda->notulensi);
        $this->assertStringContainsString('<blockquote>Arahan Pimpinan:', $this->agenda->kesimpulan);
    }

    public function test_xss_scripts_and_malicious_handlers_are_stripped(): void
    {
        $maliciousNotulensi = '<p>Catatan penting <script>alert("XSS-ATTACK");</script> dan aman.</p><img src="x" onerror="alert(1)">';
        $maliciousKesimpulan = '<a href="javascript:alert(\'hack\')">Klik Disini</a><iframe src="https://evil.com"></iframe><b>Kesimpulan Resmi</b>';

        $response = $this->actingAs($this->admin)->put(
            route('admin.agendas.update-notulen', $this->agenda),
            [
                'notulensi' => $maliciousNotulensi,
                'kesimpulan' => $maliciousKesimpulan,
            ]
        );

        $response->assertRedirect();

        $this->agenda->refresh();

        // Must NOT contain any dangerous vectors
        $this->assertStringNotContainsString('<script>', $this->agenda->notulensi);
        $this->assertStringNotContainsString('alert("XSS-ATTACK")', $this->agenda->notulensi);
        $this->assertStringNotContainsString('onerror=', $this->agenda->notulensi);
        $this->assertStringNotContainsString('<iframe>', $this->agenda->kesimpulan);
        $this->assertStringNotContainsString('javascript:', $this->agenda->kesimpulan);

        // Safe tags must be preserved
        $this->assertStringContainsString('Catatan penting', $this->agenda->notulensi);
        $this->assertStringContainsString('<b>Kesimpulan Resmi</b>', $this->agenda->kesimpulan);
    }

    public function test_agenda_detail_page_renders_rich_formatted_minutes_with_clean_view(): void
    {
        $this->agenda->update([
            'notulensi' => '<h3>Poin Strategis</h3><ul><li>Peningkatan Akreditasi</li></ul>',
            'kesimpulan' => '<blockquote>Patuhi target waktu.</blockquote>',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.agendas.show', $this->agenda));

        $response->assertOk();
        $response->assertSee('<h3>Poin Strategis</h3>', false);
        $response->assertSee('Peningkatan Akreditasi');
        $response->assertSee('<blockquote>Patuhi target waktu.</blockquote>', false);
        $response->assertSee('Edit Notulensi');

        // Verify that the detail page is kept pristine, clean, and without embedded editor ribbon
        $response->assertDontSee('word-editor-ribbon');
        $response->assertDontSee('word-btn');
    }

    public function test_dedicated_notulen_page_renders_word_ribbon_editor(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.agendas.notulen', $this->agenda));

        $response->assertOk();
        $response->assertSee('word-editor-ribbon');
        $response->assertSee('word-btn');
        $response->assertSee('Kembali ke Detail Agenda');
        $response->assertSee('Simpan Notulensi');
        $response->assertSee('0 Kata');
        $response->assertSee('0 Karakter');

        // Verify that the title bar header and save status badge have been removed
        $response->assertDontSee('PENGOLAH KATA RESMI');
        $response->assertDontSee('Standar Tata Naskah Dinas');
        $response->assertDontSee('Siap Disimpan');
    }

    public function test_dedicated_notulen_page_contains_photo_preview_dropzone_and_grid(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.agendas.notulen', $this->agenda));

        $response->assertOk();
        $response->assertSee('photo-uploader-container');
        $response->assertSee('photo-dropzone');
        $response->assertSee('photo-counter-badge');
        $response->assertSee('photo-preview-grid');
        $response->assertSee('name="photos[]"', false);
    }

    public function test_pdf_and_word_exports_render_formatted_minutes(): void
    {
        $this->agenda->update([
            'notulensi' => '<p>Poin pembahasan <b>sangat penting</b>.</p>',
            'kesimpulan' => '<ol><li>Tindak lanjut nomor 1</li></ol>',
        ]);

        // Printable PDF Export
        $pdfResponse = $this->actingAs($this->admin)->get(route('admin.reports.export.pdf', $this->agenda));
        $pdfResponse->assertOk();
        $this->assertStringContainsString('<b>sangat penting</b>', $pdfResponse->getContent());
        $this->assertStringContainsString('<ol><li>Tindak lanjut nomor 1</li></ol>', $pdfResponse->getContent());

        // Word Export
        $wordResponse = $this->actingAs($this->admin)->get(route('admin.reports.export.word', $this->agenda));
        $wordResponse->assertOk();
        $this->assertStringContainsString('<b>sangat penting</b>', $wordResponse->getContent());
        $this->assertStringContainsString('<ol><li>Tindak lanjut nomor 1</li></ol>', $wordResponse->getContent());
    }
}
