<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UnifiedDocumentMinutesTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private User $staff;
    private Agenda $agenda;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->admin = User::where('role', 'administrator')->first() ?? User::create([
            'name' => 'Admin Test Unified',
            'username' => 'admin_test_unified_' . uniqid(),
            'email' => 'admin_unified_' . uniqid() . '@lldikti.test',
            'nip' => '198801012010011001',
            'password' => bcrypt('password'),
            'role' => 'administrator',
            'is_active' => true,
        ]);

        $this->staff = User::where('role', 'staff')->first() ?? User::create([
            'name' => 'Pegawai Test Unified',
            'username' => 'staff_test_unified_' . uniqid(),
            'email' => 'staff_unified_' . uniqid() . '@lldikti.test',
            'nip' => '199505052020011002',
            'password' => bcrypt('password'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $unit = $this->admin->unit ?? Unit::first();

        $this->agenda = Agenda::create([
            'unit_id' => $unit?->id,
            'created_by' => $this->admin->id,
            'judul_rapat' => 'Rapat Pleno Uji Single Action Workflow',
            'slug' => 'rapat-pleno-uji-single-action-' . uniqid(),
            'deskripsi' => 'Pengujian penyatuan pengaturan dokumen dan notulensi.',
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

    public function test_workstation_page_renders_document_config_modal_and_controls(): void
    {
        $response = $this->actingAs($this->admin)->get(
            route('admin.agendas.notulen', $this->agenda)
        );

        $response->assertStatus(200);
        $response->assertSee('Penyesuaian Dokumen');
        $response->assertSee('Ekspor');
        $response->assertSee('modal-notulen-document-config');
        $response->assertSee('Batal');
        $response->assertSee('Simpan &amp; Tutup', false);
        $response->assertDontSee('Simpan Notulensi &amp; Pengaturan', false);
    }

    public function test_single_action_submission_saves_minutes_and_persists_document_config(): void
    {
        $payload = [
            'notulensi' => '<h2>Notulensi Rapat Pleno</h2><p>Rapat dimulai tepat waktu dengan kehadiran lengkap.</p>',
            'kesimpulan' => '<p>Seluruh satker wajib menyelesaikan pelaporan sebelum Jumat.</p>',
            'has_document_config' => '1',
            'document_title' => 'BERITA ACARA KHUSUS RAPAT KERJA LLDIKTI',
            'document_number' => 'BA-KHUSUS/2026/001',
            'instansi_induk' => 'KEMENTERIAN SAINS DAN TEKNOLOGI',
            'instansi_pelaksana' => 'LLDIKTI WILAYAH X PADANG',
            'alamat_kontak' => 'Jl. Khatib Sulaiman Padang, Telp 0751-12345',
            'show_kop' => '1',
            'show_logo' => '1',
            'show_document_number' => '1',
            'signing_city' => 'Bukittinggi',
            'signing_date' => '25 September 2026',
            'signer1_role' => 'Pemimpin Rapat Khusus',
            'signer1_name' => 'Prof. Dr. Ir. Budi Raharjo, M.Sc.',
            'signer1_nip' => '197501012000031001',
            'show_signer1_signature' => '1',
            'signer2_role' => 'Notulis Resmi',
            'signer2_name' => 'Siti Nurhaliza, S.Kom.',
            'signer2_nip' => '199203032019032002',
            'show_signer2_signature' => '1',
            'show_signer3' => '1',
            'signer3_role' => 'Sekretaris Utama',
            'signer3_name' => 'Dr. Hendra Gunawan',
            'signer3_nip' => '198004042005011003',
            'footer_note' => 'Dokumen resmi terbitan SIPERAPAT Kementerian Sains.',
        ];

        $response = $this->actingAs($this->admin)->put(
            route('admin.agendas.update-notulen', $this->agenda),
            $payload
        );

        $response->assertRedirect(route('admin.agendas.show', $this->agenda));
        $response->assertSessionHas('success');

        $this->agenda->refresh();

        // 1. Minutes and conclusions updated
        $this->assertStringContainsString('<h2>Notulensi Rapat Pleno</h2>', $this->agenda->notulensi);
        $this->assertStringContainsString('Seluruh satker wajib menyelesaikan pelaporan', $this->agenda->kesimpulan);

        // 2. Report config persisted with exact customized values
        $config = $this->agenda->report_config;
        $this->assertIsArray($config);
        $this->assertSame('BERITA ACARA KHUSUS RAPAT KERJA LLDIKTI', $config['document_title']);
        $this->assertSame('BA-KHUSUS/2026/001', $config['document_number']);
        $this->assertSame('KEMENTERIAN SAINS DAN TEKNOLOGI', $config['instansi_induk']);
        $this->assertSame('LLDIKTI WILAYAH X PADANG', $config['instansi_pelaksana']);
        $this->assertSame('Bukittinggi', $config['signing_city']);
        $this->assertSame('25 September 2026', $config['signing_date']);
        $this->assertSame('Prof. Dr. Ir. Budi Raharjo, M.Sc.', $config['signer1_name']);
        $this->assertSame('197501012000031001', $config['signer1_nip']);
        $this->assertSame('Siti Nurhaliza, S.Kom.', $config['signer2_name']);
        $this->assertTrue($config['show_signer3']);
        $this->assertSame('Dr. Hendra Gunawan', $config['signer3_name']);
        $this->assertSame('Dokumen resmi terbitan SIPERAPAT Kementerian Sains.', $config['footer_note']);
    }

    public function test_persisted_document_config_is_used_by_pdf_and_word_exports(): void
    {
        // Set persisted report_config
        $this->agenda->update([
            'report_config' => [
                'document_title' => 'BERITA ACARA TERPADU NOTULENSI',
                'document_number' => 'BA-TERPADU/2026/777',
                'instansi_induk' => 'KEMENTERIAN RISET DAN PENDIDIKAN TINGGI',
                'signer1_name' => 'Prof. H. Ahmad Dahlan, Ph.D.',
                'signer2_name' => 'Rina Kartika, M.T.',
                'show_kop' => true,
                'show_attendees' => true,
                'show_notulensi' => true,
                'show_kesimpulan' => true,
                'show_documentation' => true,
                'show_footer_note' => true,
            ],
            'notulensi' => '<p>Catatan jalannya sidang terpadu.</p>',
            'kesimpulan' => '<p>Keputusan bulat disetujui bersama.</p>',
        ]);

        // 1. PDF Export reflects persisted config
        $pdfResponse = $this->actingAs($this->admin)->get(
            route('admin.reports.export.pdf', $this->agenda)
        );
        $pdfResponse->assertStatus(200);
        $pdfResponse->assertSee('BERITA ACARA TERPADU NOTULENSI');
        $pdfResponse->assertSee('BA-TERPADU/2026/777');
        $pdfResponse->assertSee('KEMENTERIAN RISET DAN PENDIDIKAN TINGGI');
        $pdfResponse->assertSee('Prof. H. Ahmad Dahlan, Ph.D.');

        // 2. Word Export reflects persisted config
        $wordResponse = $this->actingAs($this->admin)->get(
            route('admin.reports.export.word', $this->agenda)
        );
        $wordResponse->assertStatus(200);
        $this->assertStringContainsString('BERITA ACARA TERPADU NOTULENSI', $wordResponse->getContent());
        $this->assertStringContainsString('BA-TERPADU/2026/777', $wordResponse->getContent());
        $this->assertStringContainsString('Prof. H. Ahmad Dahlan, Ph.D.', $wordResponse->getContent());
    }

    public function test_custom_logo_upload_and_reset_in_single_action(): void
    {
        $fakeLogo = UploadedFile::fake()->image('custom_satker_logo.png', 120, 120);

        // Upload custom logo in updateNotulen
        $response = $this->actingAs($this->admin)->put(
            route('admin.agendas.update-notulen', $this->agenda),
            [
                'notulensi' => '<p>Catatan dengan logo satker kustom.</p>',
                'has_document_config' => '1',
                'custom_logo' => $fakeLogo,
            ]
        );

        $response->assertRedirect(route('admin.agendas.show', $this->agenda));
        $this->agenda->refresh();

        $savedLogoPath = $this->agenda->report_config['custom_logo_path'] ?? null;
        $this->assertNotNull($savedLogoPath);
        Storage::disk('public')->assertExists($savedLogoPath);

        // Reset logo to Tut Wuri Handayani
        $resetResponse = $this->actingAs($this->admin)->put(
            route('admin.agendas.update-notulen', $this->agenda),
            [
                'notulensi' => '<p>Catatan dikembalikan ke logo kementerian.</p>',
                'has_document_config' => '1',
                'reset_custom_logo' => '1',
            ]
        );

        $resetResponse->assertRedirect(route('admin.agendas.show', $this->agenda));
        $this->agenda->refresh();

        $this->assertNull($this->agenda->report_config['custom_logo_path']);
        Storage::disk('public')->assertMissing($savedLogoPath);
    }

    public function test_assigned_notulis_staff_can_access_workstation_and_customize_document(): void
    {
        $this->agenda->update([
            'status' => 'ongoing',
            'notulis_id' => $this->staff->id,
        ]);

        // Assigned staff can access workstation
        $accessResponse = $this->actingAs($this->staff)->get(
            route('admin.agendas.notulen', $this->agenda)
        );
        $accessResponse->assertStatus(200);

        // Assigned staff can save minutes & config
        $saveResponse = $this->actingAs($this->staff)->put(
            route('admin.agendas.update-notulen', $this->agenda),
            [
                'notulensi' => '<p>Catatan dibuat oleh notulis staf bertugas.</p>',
                'kesimpulan' => '<p>Tindak lanjut telah diagendakan.</p>',
                'has_document_config' => '1',
                'document_title' => 'NOTULEN RAPAT KOORDINASI SATKER',
                'signer2_name' => $this->staff->name,
            ]
        );

        $saveResponse->assertRedirect(route('agendas.show', $this->agenda));
        $saveResponse->assertSessionHas('success');

        $this->agenda->refresh();
        $this->assertSame('NOTULEN RAPAT KOORDINASI SATKER', $this->agenda->report_config['document_title']);
        $this->assertSame($this->staff->name, $this->agenda->report_config['signer2_name']);
    }

    public function test_unauthorized_user_cannot_access_or_update_minutes_and_config(): void
    {
        $unauthorizedStaff = User::create([
            'name' => 'Pegawai Asing',
            'username' => 'staff_foreign_' . uniqid(),
            'email' => 'foreign_' . uniqid() . '@lldikti.test',
            'nip' => '199912122022011003',
            'password' => bcrypt('password'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $this->agenda->update([
            'status' => 'ongoing',
            'notulis_id' => $this->staff->id, // Assigned to different staff
        ]);

        $accessResponse = $this->actingAs($unauthorizedStaff)->get(
            route('admin.agendas.notulen', $this->agenda)
        );
        $accessResponse->assertStatus(403);

        $updateResponse = $this->actingAs($unauthorizedStaff)->put(
            route('admin.agendas.update-notulen', $this->agenda),
            [
                'notulensi' => 'Peretasan notulensi',
                'has_document_config' => '1',
            ]
        );
        $updateResponse->assertStatus(403);
    }
}
