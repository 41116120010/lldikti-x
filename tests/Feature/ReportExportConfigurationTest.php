<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\User;
use Tests\TestCase;

class ReportExportConfigurationTest extends TestCase
{
    public function test_agenda_has_resolved_report_config_with_sensible_defaults(): void
    {
        $agenda = Agenda::first();
        $config = $agenda->resolved_report_config;

        $this->assertIsArray($config);
        $this->assertArrayHasKey('show_kop', $config);
        $this->assertArrayHasKey('document_title', $config);
        $this->assertArrayHasKey('instansi_induk', $config);
        $this->assertArrayHasKey('instansi_pelaksana', $config);
        $this->assertArrayHasKey('show_notulensi', $config);
        $this->assertArrayHasKey('show_kesimpulan', $config);
        $this->assertArrayHasKey('show_documentation', $config);
        $this->assertArrayHasKey('show_footer_note', $config);
        $this->assertArrayHasKey('show_selfie_photos', $config);

        $this->assertTrue($config['show_kop']);
        $this->assertSame('KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI', $config['instansi_induk']);
        $this->assertSame('LEMBAGA LAYANAN PENDIDIKAN TINGGI (LLDIKTI) WILAYAH X', $config['instansi_pelaksana']);
        $this->assertSame('BERITA ACARA DAN DAFTAR HADIR RAPAT', $config['document_title']);
        $this->assertTrue($config['show_notulensi']);
        $this->assertTrue($config['show_kesimpulan']);
        $this->assertTrue($config['show_documentation']);
        $this->assertTrue($config['show_footer_note']);
        $this->assertTrue($config['show_selfie_photos']);
    }

    public function test_user_can_export_pdf_with_default_configuration(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        $response = $this->actingAs($superadmin)->get("/admin/reports/{$agenda->id}/export/pdf");

        $response->assertStatus(200);
        $response->assertSee('BERITA ACARA DAN DAFTAR HADIR RAPAT');
        $response->assertSee('LEMBAGA LAYANAN PENDIDIKAN TINGGI (LLDIKTI) WILAYAH X');
        $response->assertSee('Mengetahui,');
    }

    public function test_user_can_export_pdf_with_custom_configuration_override(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        $customPayload = [
            'document_title' => 'NOTULEN RESMI SIDANG PLENO',
            'document_number' => '099/PLENO/LLDIKTI10/2026',
            'show_kop' => '0', // Kop disembunyikan
            'instansi_induk' => 'KEMENTERIAN RISET DAN TEKNOLOGI',
            'instansi_pelaksana' => 'LLDIKTI WILAYAH X KHUSUS',
            'alamat_kontak' => 'Jl. Khatib Sulaiman Padang',
            'show_meeting_info' => '1',
            'custom_agenda_title' => 'Sidang Pleno Tahunan 2026',
            'custom_location' => 'Aula Utama Gedung A',
            'show_attendees' => '1',
            'show_nip' => '1',
            'show_unit' => '1',
            'show_attendance_time' => '1',
            'show_attendee_signatures' => '1',
            'show_notulensi' => '0', // Notulensi disembunyikan
            'show_kesimpulan' => '1',
            'show_documentation' => '0',
            'signing_city' => 'Bukittinggi',
            'signing_date' => '15 September 2026',
            'signer1_role' => 'Ketua Dewan Pleno',
            'signer1_name' => 'Dr. Pimpinan Pleno',
            'signer1_nip' => '198001012005011001',
            'show_signer1_signature' => '1',
            'signer2_role' => 'Sekretaris Sidang',
            'signer2_name' => 'Dra. Notulis Sidang',
            'signer2_nip' => '198502022008012002',
            'show_signer2_signature' => '1',
            'show_signer3' => '1', // Penandatangan ketiga aktif
            'signer3_role' => 'Kepala Balai Khusus',
            'signer3_name' => 'Prof. Dr. Penguji Utama',
            'signer3_nip' => '197501012000031001',
            'show_footer_note' => '1',
            'footer_note' => 'Dokumen ini dibuat otomatis dan sah secara hukum kedinasan.',
        ];

        $response = $this->actingAs($superadmin)->post("/admin/reports/{$agenda->id}/export/pdf", $customPayload);

        $response->assertStatus(200);
        $response->assertSee('NOTULEN RESMI SIDANG PLENO');
        $response->assertSee('099/PLENO/LLDIKTI10/2026');
        $response->assertSee('Bukittinggi, 15 September 2026');
        $response->assertSee('Ketua Dewan Pleno');
        $response->assertSee('Sekretaris Sidang');
        $response->assertSee('Kepala Balai Khusus');
        $response->assertSee('Prof. Dr. Penguji Utama');
        $response->assertSee('197501012000031001');
        $response->assertSee('Dokumen ini dibuat otomatis dan sah secara hukum kedinasan.');
        // Kop harus tidak muncul
        $response->assertDontSee('LEMBAGA LAYANAN PENDIDIKAN TINGGI (LLDIKTI) WILAYAH X');
    }

    public function test_user_can_export_word_with_custom_configuration_override(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        $customPayload = [
            'document_title' => 'LAPORAN WORD REKAPITULASI AGENDA',
            'document_number' => 'WORD/2026/001',
            'show_kop' => '1',
            'instansi_induk' => 'KEMENDIKBUD RISTEK',
            'instansi_pelaksana' => 'LLDIKTI WILAYAH X',
            'alamat_kontak' => 'Padang, Sumatera Barat',
            'show_meeting_info' => '1',
            'show_attendees' => '1',
            'show_nip' => '1',
            'show_unit' => '1',
            'show_attendance_time' => '1',
            'show_attendee_signatures' => '1',
            'show_notulensi' => '1',
            'show_kesimpulan' => '1',
            'show_documentation' => '1',
            'signing_city' => 'Kota Padang',
            'signing_date' => '20 September 2026',
            'signer1_role' => 'Inspektur Wilayah',
            'signer1_name' => 'Drs. Pemeriksa Utama',
            'signer1_nip' => '197001011995031001',
            'show_signer1_signature' => '1',
            'signer2_role' => 'Notulis Utama',
            'signer2_name' => 'Fulan, S.Kom.',
            'signer2_nip' => '199001012015011002',
            'show_signer2_signature' => '1',
            'show_signer3' => '0',
            'show_footer_note' => '1',
            'footer_note' => 'Format Word Resmi Kemendikbud',
        ];

        $response = $this->actingAs($superadmin)->post("/admin/reports/{$agenda->id}/export/word", $customPayload);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.ms-word; charset=UTF-8');
        $this->assertStringContainsString('.doc', $response->headers->get('Content-Disposition'));
        $response->assertSee('LAPORAN WORD REKAPITULASI AGENDA');
        $response->assertSee('WORD/2026/001');
        $response->assertSee('Kota Padang, 20 September 2026');
        $response->assertSee('Inspektur Wilayah');
    }

    public function test_user_can_save_configuration_as_default_for_agenda(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        $customPayload = [
            'save_as_default' => '1',
            'document_title' => 'BERITA ACARA TERSIMPAN PERMANEN',
            'document_number' => 'PERM/001/2026',
            'show_kop' => '1',
            'instansi_induk' => 'KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI',
            'instansi_pelaksana' => 'LEMBAGA LAYANAN PENDIDIKAN TINGGI (LLDIKTI) WILAYAH X',
            'alamat_kontak' => 'Jl. Khatib Sulaiman, Padang',
            'show_meeting_info' => '1',
            'show_attendees' => '1',
            'show_nip' => '1',
            'show_unit' => '1',
            'show_attendance_time' => '1',
            'show_attendee_signatures' => '1',
            'show_notulensi' => '1',
            'show_kesimpulan' => '1',
            'show_documentation' => '1',
            'signing_city' => 'Payakumbuh',
            'signing_date' => '25 September 2026',
            'signer1_role' => 'Ketua Sidang Khusus',
            'signer1_name' => 'Dr. H. Pemimpin',
            'signer1_nip' => '196805051992031002',
            'show_signer1_signature' => '1',
            'signer2_role' => 'Sekretaris Sidang Khusus',
            'signer2_name' => 'Fulanah, M.Pd.',
            'signer2_nip' => '198203032006042001',
            'show_signer2_signature' => '1',
            'show_signer3' => '0',
            'show_footer_note' => '1',
            'footer_note' => 'Konfigurasi tersimpan pada agenda.',
        ];

        $response = $this->actingAs($superadmin)->post("/admin/reports/{$agenda->id}/export/pdf", $customPayload);
        $response->assertStatus(200);

        // Refresh model from DB
        $agenda->refresh();

        $this->assertNotNull($agenda->report_config);
        $this->assertSame('BERITA ACARA TERSIMPAN PERMANEN', $agenda->report_config['document_title']);
        $this->assertSame('PERM/001/2026', $agenda->report_config['document_number']);
        $this->assertSame('Payakumbuh', $agenda->report_config['signing_city']);

        // Now verify standard GET export uses this saved config!
        $getResponse = $this->actingAs($superadmin)->get("/admin/reports/{$agenda->id}/export/pdf");
        $getResponse->assertStatus(200);
        $getResponse->assertSee('BERITA ACARA TERSIMPAN PERMANEN');
        $getResponse->assertSee('PERM/001/2026');
        $getResponse->assertSee('Payakumbuh');
    }

    public function test_user_can_reset_configuration_to_defaults(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        // Ensure it has some config
        $agenda->update([
            'report_config' => [
                'document_title' => 'CUSTOM TITLE YANG AKAN DIRESET',
            ],
        ]);
        $this->assertNotNull($agenda->fresh()->report_config);

        $response = $this->actingAs($superadmin)->post("/admin/reports/{$agenda->id}/report-config/reset");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $agenda->refresh();
        $this->assertNull($agenda->report_config);

        // Check resolved config has gone back to default title
        $this->assertSame('BERITA ACARA DAN DAFTAR HADIR RAPAT', $agenda->resolved_report_config['document_title']);
    }
}
