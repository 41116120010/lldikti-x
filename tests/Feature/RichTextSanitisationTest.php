<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Locks in the rich-text sanitisation contract for meeting minutes.
 *
 * The minutes are rendered through Blade's raw-output directive, so the model
 * accessor is the only thing standing between a minute-taker and stored XSS in
 * an official document — including a copy that is printed into a PDF. These
 * cases cover the attributes the sanitiser deliberately keeps (links, font
 * sizing) as well as the ones it must drop, because relaxing a filter is
 * exactly when a regression creeps in.
 */
class RichTextSanitisationTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private Agenda $agenda;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::where('role', 'administrator')->first()
            ?? User::create([
                'name' => 'Admin Uji Sanitasi',
                'nip' => '199501012020011234',
                'username' => 'admin_sanitasi',
                'email' => 'admin_sanitasi@lldikti.test',
                'password' => bcrypt('Password123!'),
                'role' => 'administrator',
                'is_active' => true,
            ]);

        $this->agenda = Agenda::create([
            'created_by' => $this->admin->id,
            'judul_rapat' => 'Rapat Uji Sanitasi Notulensi',
            'slug' => 'uji-sanitasi-notulensi',
            'jenis_rapat' => 'Rapat Koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Uji',
            'waktu_mulai' => now(),
            'waktu_selesai' => now()->addHour(),
            'is_all_units' => true,
            'status' => 'ongoing',
        ]);
    }

    public function test_it_drops_script_tags_and_their_contents(): void
    {
        $this->agenda->update([
            'notulensi' => '<p>Catatan resmi.</p><script>alert("xss")</script>',
        ]);

        $this->agenda->refresh();

        $this->assertStringNotContainsString('<script', $this->agenda->formatted_notulensi);
        $this->assertStringNotContainsString('alert("xss")', $this->agenda->formatted_notulensi);
        $this->assertStringContainsString('Catatan resmi.', $this->agenda->formatted_notulensi);
    }

    public function test_it_drops_inline_event_handlers(): void
    {
        $this->agenda->update([
            'notulensi' => '<div onclick="steal()" onmouseover="x()">Isi aman</div>',
        ]);

        $this->agenda->refresh();

        $this->assertStringNotContainsString('onclick', $this->agenda->formatted_notulensi);
        $this->assertStringNotContainsString('onmouseover', $this->agenda->formatted_notulensi);
        $this->assertStringContainsString('Isi aman', $this->agenda->formatted_notulensi);
    }

    public function test_it_rejects_javascript_scheme_links_but_keeps_the_element(): void
    {
        $this->agenda->update([
            'notulensi' => '<p><a href="javascript:alert(1)">Klik</a></p>',
        ]);

        $this->agenda->refresh();
        $rendered = (string) $this->agenda->formatted_notulensi;

        $this->assertStringNotContainsString('javascript:', $rendered);
        $this->assertStringContainsString('Klik', $rendered);
    }

    public function test_it_keeps_ordinary_document_links(): void
    {
        $url = 'https://lldikti3.kemdikbud.go.id/portal/berita-acara-2026.pdf';

        $this->agenda->update([
            'notulensi' => '<p>Dokumen: <a href="' . $url . '">unduh</a></p>',
        ]);

        $this->agenda->refresh();

        $this->assertStringContainsString('href="' . $url . '"', (string) $this->agenda->formatted_notulensi);
    }

    public function test_it_drops_style_attributes_that_enable_spoofing(): void
    {
        $this->agenda->update([
            'notulensi' => '<div style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999">Tertutup</div>',
        ]);

        $this->agenda->refresh();
        $rendered = (string) $this->agenda->formatted_notulensi;

        $this->assertStringNotContainsString('position', $rendered);
        $this->assertStringNotContainsString('z-index', $rendered);
        $this->assertStringContainsString('Tertutup', $rendered);
    }

    public function test_it_keeps_presentational_font_attributes(): void
    {
        $this->agenda->update([
            'notulensi' => '<p><font size="4" color="#c0392b">Penting</font></p>',
        ]);

        $this->agenda->refresh();

        $this->assertStringContainsString('size="4"', (string) $this->agenda->formatted_notulensi);
        $this->assertStringContainsString('#c0392b', (string) $this->agenda->formatted_notulensi);
    }
}
