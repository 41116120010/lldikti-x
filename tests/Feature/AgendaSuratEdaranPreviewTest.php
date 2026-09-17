<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AgendaSuratEdaranPreviewTest extends TestCase
{
    private function getOrCreateSuperadmin(): User
    {
        return User::where('role', 'administrator')->first() ?? User::create([
            'name' => 'Super Administrator',
            'nip' => '197001011990011001',
            'username' => 'superadmin_test',
            'email' => 'superadmin_test@lldikti.test',
            'password' => bcrypt('password'),
            'role' => 'administrator',
            'is_active' => true,
        ]);
    }

    private function getOrCreateStaff(Unit $unit): User
    {
        return User::create([
            'name' => 'Staf Pegawai Test',
            'nip' => '199501012020011002',
            'username' => 'staf_preview_test_' . uniqid(),
            'email' => 'staf_' . uniqid() . '@lldikti.test',
            'password' => bcrypt('password'),
            'role' => 'staff',
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);
    }

    private function getOrCreateUnitAdmin(Unit $unit): User
    {
        return User::create([
            'name' => 'Admin Unit Test',
            'nip' => '198501012015011003',
            'username' => 'admin_preview_test_' . uniqid(),
            'email' => 'admin_unit_' . uniqid() . '@lldikti.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);
    }

    public function test_agenda_model_helper_accessors_detect_pdf_and_image_correctly(): void
    {
        $superadmin = $this->getOrCreateSuperadmin();

        $agendaPdf = Agenda::create([
            'judul_rapat' => 'Rapat Uji PDF Accessor',
            'tanggal_rapat' => now()->toDateString(),
            'waktu_mulai' => now()->addHour(),
            'lokasi_ruang' => 'Ruang 1',
            'status' => 'scheduled',
            'created_by' => $superadmin->id,
            'is_all_units' => true,
            'surat_edaran_path' => 'surat_edaran/sample_invitation.pdf',
        ]);

        $this->assertEquals('pdf', $agendaPdf->surat_edaran_extension);
        $this->assertTrue($agendaPdf->is_surat_edaran_pdf);
        $this->assertFalse($agendaPdf->is_surat_edaran_image);
        $this->assertStringContainsString('surat_edaran/sample_invitation.pdf', $agendaPdf->surat_edaran_url);

        $agendaImg = Agenda::create([
            'judul_rapat' => 'Rapat Uji JPG Accessor',
            'tanggal_rapat' => now()->toDateString(),
            'waktu_mulai' => now()->addHour(),
            'lokasi_ruang' => 'Ruang 2',
            'status' => 'scheduled',
            'created_by' => $superadmin->id,
            'is_all_units' => true,
            'surat_edaran_path' => 'surat_edaran/sample_flyer.PNG',
        ]);

        $this->assertEquals('png', $agendaImg->surat_edaran_extension);
        $this->assertFalse($agendaImg->is_surat_edaran_pdf);
        $this->assertTrue($agendaImg->is_surat_edaran_image);
        $this->assertStringContainsString('surat_edaran/sample_flyer.PNG', $agendaImg->surat_edaran_url);

        $agendaEmpty = Agenda::create([
            'judul_rapat' => 'Rapat Tanpa Berkas',
            'tanggal_rapat' => now()->toDateString(),
            'waktu_mulai' => now()->addHour(),
            'lokasi_ruang' => 'Ruang 3',
            'status' => 'scheduled',
            'created_by' => $superadmin->id,
            'is_all_units' => true,
            'surat_edaran_path' => null,
        ]);

        $this->assertNull($agendaEmpty->surat_edaran_url);
        $this->assertNull($agendaEmpty->surat_edaran_extension);
        $this->assertFalse($agendaEmpty->is_surat_edaran_pdf);
        $this->assertFalse($agendaEmpty->is_surat_edaran_image);
    }

    public function test_admin_agenda_detail_shows_direct_pdf_preview_and_modal_trigger(): void
    {
        $superadmin = $this->getOrCreateSuperadmin();

        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Pleno Koordinasi PDF',
            'tanggal_rapat' => now()->toDateString(),
            'waktu_mulai' => now()->addHour(),
            'lokasi_ruang' => 'Ruang Sidang Utama',
            'status' => 'scheduled',
            'created_by' => $superadmin->id,
            'is_all_units' => true,
            'surat_edaran_path' => 'surat_edaran/undangan_resmi_pleno.pdf',
        ]);

        $response = $this->actingAs($superadmin)->get("/admin/agendas/{$agenda->id}");

        $response->assertStatus(200);
        $response->assertSee('Surat Edaran / Undangan');
        $response->assertSee('PDF');
        $response->assertSee('Pratinjau Layar Penuh');
        $response->assertSee('Tab Baru');
        $response->assertSee('Unduh File');
        $response->assertSee('modal-surat-preview-' . $agenda->id);
        $response->assertSee('h-[90vh]', false);
        $response->assertSee('view=Fit', false);
        $response->assertSee('view=FitH', false);
        $response->assertSee('iframe', false);
        $response->assertSee('undangan_resmi_pleno.pdf');
        
        // Ensure CSP header includes frame-src and object-src
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringContainsString("frame-src 'self' data: blob:", $csp);
        $this->assertStringContainsString("object-src 'self' data: blob:", $csp);
    }

    public function test_admin_agenda_detail_shows_direct_image_preview_and_modal_trigger(): void
    {
        $superadmin = $this->getOrCreateSuperadmin();

        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Sosialisasi Brosur Gambar',
            'tanggal_rapat' => now()->toDateString(),
            'waktu_mulai' => now()->addHour(),
            'lokasi_ruang' => 'Ruang Rapat 2',
            'status' => 'scheduled',
            'created_by' => $superadmin->id,
            'is_all_units' => true,
            'surat_edaran_path' => 'surat_edaran/brosur_kegiatan.webp',
        ]);

        $response = $this->actingAs($superadmin)->get("/admin/agendas/{$agenda->id}");

        $response->assertStatus(200);
        $response->assertSee('Surat Edaran / Undangan');
        $response->assertSee('GAMBAR');
        $response->assertSee('Klik untuk perbesar');
        $response->assertSee('Pratinjau Layar Penuh');
        $response->assertSee('Tab Baru');
        $response->assertSee('Unduh File');
        $response->assertSee('modal-surat-preview-' . $agenda->id);
        $response->assertSee('brosur_kegiatan.webp');
    }

    public function test_admin_agenda_detail_shows_empty_state_when_no_surat_edaran(): void
    {
        $superadmin = $this->getOrCreateSuperadmin();

        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Evaluasi Tanpa Surat',
            'tanggal_rapat' => now()->toDateString(),
            'waktu_mulai' => now()->addHour(),
            'lokasi_ruang' => 'Ruang Rapat 3',
            'status' => 'scheduled',
            'created_by' => $superadmin->id,
            'is_all_units' => true,
            'surat_edaran_path' => null,
        ]);

        $response = $this->actingAs($superadmin)->get("/admin/agendas/{$agenda->id}");

        $response->assertStatus(200);
        $response->assertSee('Surat Edaran / Undangan');
        $response->assertSee('Tidak ada berkas surat edaran atau undangan terlampir.');
        $response->assertDontSee('Layar Penuh');
        $response->assertDontSee('modal-surat-preview-' . $agenda->id);
    }

    public function test_staff_agenda_detail_shows_direct_pdf_preview_and_modal_trigger(): void
    {
        $unit = Unit::create(['nama_unit' => 'Unit Kepegawaian Preview Test', 'kode_unit' => 'UKPT']);
        $superadmin = $this->getOrCreateSuperadmin();
        $staff = $this->getOrCreateStaff($unit);

        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Sosialisasi Staf PDF',
            'tanggal_rapat' => now()->toDateString(),
            'waktu_mulai' => now()->addHour(),
            'lokasi_ruang' => 'Aula Gedung B',
            'status' => 'scheduled',
            'created_by' => $superadmin->id,
            'is_all_units' => false,
            'surat_edaran_path' => 'surat_edaran/undangan_staf.pdf',
        ]);
        $agenda->units()->attach($unit->id);

        $response = $this->actingAs($staff)->get("/agendas/{$agenda->id}");

        $response->assertStatus(200);
        $response->assertSee('Surat Edaran / Undangan');
        $response->assertSee('Pratinjau Layar Penuh');
        $response->assertSee('Tab Baru');
        $response->assertSee('Unduh File');
        $response->assertSee('modal-surat-preview-' . $agenda->id);
        $response->assertSee('undangan_staf.pdf');
    }

    public function test_staff_agenda_detail_shows_direct_image_preview_and_modal_trigger(): void
    {
        $unit = Unit::create(['nama_unit' => 'Unit Riset Preview Test', 'kode_unit' => 'URPT']);
        $superadmin = $this->getOrCreateSuperadmin();
        $staff = $this->getOrCreateStaff($unit);

        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Sosialisasi Riset Gambar',
            'tanggal_rapat' => now()->toDateString(),
            'waktu_mulai' => now()->addHour(),
            'lokasi_ruang' => 'Gedung C',
            'status' => 'scheduled',
            'created_by' => $superadmin->id,
            'is_all_units' => false,
            'surat_edaran_path' => 'surat_edaran/poster_undangan.jpg',
        ]);
        $agenda->units()->attach($unit->id);

        $response = $this->actingAs($staff)->get("/agendas/{$agenda->id}");

        $response->assertStatus(200);
        $response->assertSee('Surat Edaran / Undangan');
        $response->assertSee('GAMBAR');
        $response->assertSee('Klik untuk perbesar');
        $response->assertSee('Pratinjau Layar Penuh');
        $response->assertSee('Tab Baru');
        $response->assertSee('Unduh File');
        $response->assertSee('modal-surat-preview-' . $agenda->id);
        $response->assertSee('poster_undangan.jpg');
    }

    public function test_unit_admin_can_view_direct_surat_preview_for_their_agenda(): void
    {
        $unit = Unit::create(['nama_unit' => 'Unit Tata Usaha Preview Test', 'kode_unit' => 'UTUPT']);
        $unitAdmin = $this->getOrCreateUnitAdmin($unit);

        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Internal Tata Usaha',
            'tanggal_rapat' => now()->toDateString(),
            'waktu_mulai' => now()->addHour(),
            'lokasi_ruang' => 'Ruang TU',
            'status' => 'scheduled',
            'created_by' => $unitAdmin->id,
            'is_all_units' => false,
            'surat_edaran_path' => 'surat_edaran/surat_tu.pdf',
        ]);
        $agenda->units()->attach($unit->id);

        $response = $this->actingAs($unitAdmin)->get("/admin/agendas/{$agenda->id}");

        $response->assertStatus(200);
        $response->assertSee('Surat Edaran / Undangan');
        $response->assertSee('PDF');
        $response->assertSee('modal-surat-preview-' . $agenda->id);
        $response->assertSee('surat_tu.pdf');
    }
}
