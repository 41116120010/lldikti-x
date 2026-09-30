<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\AgendaDocumentation;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentationPreviewParityTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $staff;
    protected Unit $unit;
    protected Agenda $agenda;
    protected AgendaDocumentation $doc;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->unit = Unit::first() ?? Unit::create([
            'nama_unit' => 'Bagian Umum LLDIKTI',
            'kode_unit' => 'BU-01',
            'is_active' => true,
        ]);

        $this->admin = User::where('role', 'administrator')->first()
            ?? User::factory()->create([
                'username' => 'admin_' . uniqid(),
                'role' => 'administrator',
                'nip' => '197001011995011001',
                'unit_id' => $this->unit->id,
                'is_active' => true,
            ]);

        $this->staff = User::factory()->create([
            'username' => 'staff_' . uniqid(),
            'role' => 'staff',
            'nip' => '199001012015011001',
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);

        $this->agenda = Agenda::create([
            'created_by' => $this->admin->id,
            'judul_rapat' => 'Rapat Evaluasi Dokumentasi Resmi',
            'slug' => 'rapat-evaluasi-dokumentasi-resmi-' . uniqid(),
            'jenis_rapat' => 'internal',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Sidang Utama LLDIKTI Wilayah X',
            'is_all_units' => true,
            'waktu_mulai' => now()->subHours(2),
            'waktu_selesai' => now()->addHours(2),
            'status' => 'ongoing',
            'nama_pimpinan' => 'Dr. Pimpinan Rapat',
            'nip_pimpinan' => '197001011995011001',
            'nama_notulis' => 'Notulis Resmi',
            'nip_notulis' => '198501012010012001',
        ]);

        $this->agenda->units()->sync([$this->unit->id]);

        $file = UploadedFile::fake()->image('dokumentasi_kegiatan.jpg', 800, 600);
        $path = $file->store('documentations/' . $this->agenda->id, 'public');

        $this->doc = AgendaDocumentation::create([
            'agenda_id' => $this->agenda->id,
            'file_path' => $path,
            'caption' => 'Sesi Pembukaan dan Arahan Pimpinan',
            'sort_order' => 1,
        ]);
    }

    public function test_reports_show_renders_documentation_preview_modal_and_triggers(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.show', $this->agenda));

        $response->assertOk();
        $response->assertSee('id="documentation-preview-modal"', false);
        $response->assertSee('data-doc-modal-trigger', false);
        $response->assertSee('data-doc-gallery="report-docs"', false);
        $response->assertSee('Sesi Pembukaan dan Arahan Pimpinan', false);
        $response->assertSee('Pratinjau', false);
    }

    public function test_agenda_show_renders_documentation_preview_modal_and_triggers(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.agendas.show', $this->agenda));

        $response->assertOk();
        $response->assertSee('id="documentation-preview-modal"', false);
        $response->assertSee('data-doc-modal-trigger', false);
        $response->assertSee('data-doc-gallery="agenda-docs"', false);
        $response->assertSee('Sesi Pembukaan dan Arahan Pimpinan', false);
        $response->assertSee('Pratinjau', false);
    }

    public function test_staff_show_renders_documentation_preview_modal_and_interactive_grid(): void
    {
        $response = $this->actingAs($this->staff)
            ->get(route('agendas.show', $this->agenda));

        $response->assertOk();
        $response->assertSee('id="documentation-preview-modal"', false);
        $response->assertSee('data-doc-modal-trigger', false);
        $response->assertSee('data-doc-gallery="staff-agenda-docs"', false);
        $response->assertSee('Lihat Foto', false);
        $response->assertSee('Sesi Pembukaan dan Arahan Pimpinan', false);
    }

    public function test_notulen_workstation_renders_documentation_preview_modal_and_triggers(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.agendas.notulen', $this->agenda));

        $response->assertOk();
        $response->assertSee('id="documentation-preview-modal"', false);
        $response->assertSee('data-doc-modal-trigger', false);
        $response->assertSee('data-doc-gallery="modal-stored-docs"', false);
        $response->assertSee('data-doc-gallery="notulen-sheet-docs"', false);
        $response->assertSee(route('admin.agendas.delete-documentation', [$this->agenda, $this->doc]), false);
    }

    public function test_edit_agenda_renders_documentation_preview_modal_and_gallery_preview(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.agendas.edit', $this->agenda));

        $response->assertOk();
        $response->assertSee('id="documentation-preview-modal"', false);
        $response->assertSee('data-doc-modal-trigger', false);
        $response->assertSee('data-doc-gallery="edit-agenda-docs"', false);
        $response->assertSee('Lihat Galeri', false);
    }

    public function test_authorized_user_can_delete_documentation_photo(): void
    {
        $this->assertDatabaseHas('agenda_documentations', [
            'id' => $this->doc->id,
            'agenda_id' => $this->agenda->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.agendas.delete-documentation', [$this->agenda, $this->doc]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('agenda_documentations', [
            'id' => $this->doc->id,
        ]);
    }

    public function test_unauthorized_user_cannot_delete_documentation_photo(): void
    {
        $response = $this->actingAs($this->staff)
            ->delete(route('admin.agendas.delete-documentation', [$this->agenda, $this->doc]));

        $response->assertForbidden();

        $this->assertDatabaseHas('agenda_documentations', [
            'id' => $this->doc->id,
        ]);
    }

    public function test_documentation_preview_escapes_xss_payloads(): void
    {
        $xssDoc = AgendaDocumentation::create([
            'agenda_id' => $this->agenda->id,
            'file_path' => 'documentations/xss.jpg',
            'caption' => '<script>alert("xss-caption")</script>',
            'sort_order' => 2,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.agendas.show', $this->agenda));

        $response->assertOk();
        // Should not render unescaped raw script tag
        $response->assertDontSee('<script>alert("xss-caption")</script>', false);
        $response->assertSee(e('<script>alert("xss-caption")</script>'), false);
    }

    public function test_views_render_gracefully_when_no_documentations_exist(): void
    {
        $emptyAgenda = Agenda::create([
            'created_by' => $this->admin->id,
            'judul_rapat' => 'Rapat Tanpa Dokumentasi',
            'slug' => 'rapat-tanpa-dokumentasi-' . uniqid(),
            'jenis_rapat' => 'internal',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Rapat 2',
            'is_all_units' => true,
            'waktu_mulai' => now()->subHours(1),
            'waktu_selesai' => now()->addHours(1),
            'status' => 'ongoing',
        ]);
        $emptyAgenda->units()->sync([$this->unit->id]);

        $res1 = $this->actingAs($this->admin)->get(route('admin.agendas.show', $emptyAgenda));
        $res1->assertOk();
        $res1->assertSee('Belum ada foto dokumentasi yang diunggah untuk agenda ini.', false);

        $res2 = $this->actingAs($this->staff)->get(route('agendas.show', $emptyAgenda));
        $res2->assertOk();
        $res2->assertSee('Belum ada foto dokumentasi yang diunggah untuk agenda ini.', false);

        $res3 = $this->actingAs($this->admin)->get(route('admin.reports.show', $emptyAgenda));
        $res3->assertOk();
        $res3->assertSee('Belum ada foto dokumentasi yang diunggah untuk agenda ini.', false);
    }
}
