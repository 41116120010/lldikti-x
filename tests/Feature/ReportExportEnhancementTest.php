<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportExportEnhancementTest extends TestCase
{
    public function test_export_pdf_includes_default_tut_wuri_handayani_logo(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        $response = $this->actingAs($admin)->get("/admin/reports/{$agenda->id}/export/pdf");

        $response->assertStatus(200);
        $response->assertSee('alt="Logo Instansi"', false);
        $response->assertSee('data:image/png;base64,', false);
    }

    public function test_export_pdf_can_hide_logo_when_disabled(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        $payload = [
            'show_kop' => '1',
            'show_logo' => '0',
        ];

        $response = $this->actingAs($admin)->post("/admin/reports/{$agenda->id}/export/pdf", $payload);

        $response->assertStatus(200);
        $response->assertDontSee('alt="Logo Instansi"', false);
    }

    public function test_export_word_includes_default_logo_and_supports_custom_logo(): void
    {
        Storage::fake('public');
        $admin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        // 1. Default logo in Word export
        $response = $this->actingAs($admin)->get("/admin/reports/{$agenda->id}/export/word");
        $response->assertStatus(200);
        $response->assertSee('alt="Logo Instansi"', false);

        // 2. Upload custom logo
        $fakeLogo = UploadedFile::fake()->image('custom_logo.png', 200, 200);
        $postResponse = $this->actingAs($admin)->post("/admin/reports/{$agenda->id}/export/word", [
            'show_kop' => '1',
            'show_logo' => '1',
            'custom_logo' => $fakeLogo,
            'save_as_default' => '1',
        ]);
        $postResponse->assertStatus(200);

        $agenda->refresh();
        $this->assertNotNull($agenda->report_config['custom_logo_path']);
        Storage::disk('public')->assertExists($agenda->report_config['custom_logo_path']);

        // 3. Reset custom logo
        $resetResponse = $this->actingAs($admin)->post("/admin/reports/{$agenda->id}/export/word", [
            'reset_custom_logo' => '1',
            'save_as_default' => '1',
        ]);
        $resetResponse->assertStatus(200);

        $agenda->refresh();
        $this->assertNull($agenda->report_config['custom_logo_path']);
    }

    public function test_export_can_toggle_participant_selfie_photos(): void
    {
        Storage::fake('public');
        $admin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        $participantUser = User::create([
            'name' => 'Peserta Uji Presensi',
            'nip' => '199505052020011005',
            'username' => 'peserta_uji',
            'email' => 'peserta_uji@lldikti.test',
            'password' => bcrypt('password'),
            'role' => 'staff',
            'unit_id' => $agenda->unit_id ?? 1,
            'is_active' => true,
        ]);

        // Create a dummy attendance with selfie photo
        $dummyImage = UploadedFile::fake()->image('selfie.jpg', 300, 400);
        $selfiePath = $dummyImage->store('attendances/selfies', 'public');
        $dummySig = UploadedFile::fake()->image('signature.png', 150, 50);
        $sigPath = $dummySig->store('attendances/signatures', 'public');

        $attendance = Attendance::create([
            'agenda_id' => $agenda->id,
            'user_id' => $participantUser->id,
            'signed_at' => now(),
            'selfie_path' => $selfiePath,
            'signature_path' => $sigPath,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Test Device',
        ]);

        // 1. Export without selfie photos
        $responseWithoutSelfie = $this->actingAs($admin)->post("/admin/reports/{$agenda->id}/export/pdf", [
            'show_attendees' => '1',
            'show_selfie_photos' => '0',
        ]);
        $responseWithoutSelfie->assertStatus(200);
        $responseWithoutSelfie->assertDontSee('Foto Kehadiran');

        // 2. Export with selfie photos
        $responseWithSelfie = $this->actingAs($admin)->post("/admin/reports/{$agenda->id}/export/pdf", [
            'show_attendees' => '1',
            'show_selfie_photos' => '1',
        ]);
        $responseWithSelfie->assertStatus(200);
        $responseWithSelfie->assertSee('Foto Kehadiran');
        $responseWithSelfie->assertSee('alt="Selfie"', false);

        // Word export with selfie
        $wordWithSelfie = $this->actingAs($admin)->post("/admin/reports/{$agenda->id}/export/word", [
            'show_attendees' => '1',
            'show_selfie_photos' => '1',
        ]);
        $wordWithSelfie->assertStatus(200);
        $wordWithSelfie->assertSee('Foto Kehadiran');

        $attendance->delete();
        $participantUser->delete();
    }

    public function test_assigning_roles_dynamically_updates_report_config_signers(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $unit = Unit::first() ?? Unit::create(['nama_unit' => 'Unit Uji', 'kode_unit' => 'UU']);

        $pimpinanUser = User::create([
            'name' => 'Dr. H. Rinaldi M.Kom',
            'nip' => '198101012005011005',
            'username' => 'rinaldi_test',
            'email' => 'rinaldi_test@lldikti.test',
            'password' => bcrypt('password'),
            'role' => 'staff',
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);

        $notulisUser = User::create([
            'name' => 'Siti Aminah S.Kom',
            'nip' => '199203032015012003',
            'username' => 'siti_test',
            'email' => 'siti_test@lldikti.test',
            'password' => bcrypt('password'),
            'role' => 'staff',
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);

        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Sinkronisasi Peran & Penandatangan Dokumen',
            'tanggal_rapat' => now()->toDateString(),
            'waktu_mulai' => now()->addHour(),
            'waktu_selesai' => now()->addHours(3),
            'lokasi_ruang' => 'Ruang Sidang 1',
            'status' => 'scheduled',
            'unit_id' => $unit->id,
            'created_by' => $admin->id,
            'report_config' => [
                'document_title' => 'BERITA ACARA SINKRONISASI',
                'signer1_role' => 'Ketua Rapat',
                'signer1_name' => 'Nama Lama',
                'signer1_nip' => '000000000000000000',
                'signer2_role' => 'Pencatat',
                'signer2_name' => 'Notulis Lama',
                'signer2_nip' => '111111111111111111',
            ],
        ]);

        // Assign roles via AgendaController::updateRoles
        $response = $this->actingAs($admin)->patch("/admin/agendas/{$agenda->id}/roles", [
            'pimpinan_id' => $pimpinanUser->id,
            'notulis_id' => $notulisUser->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $agenda->refresh();

        // Check that agenda report_config and resolved_report_config now reflect the new roles
        $this->assertEquals($pimpinanUser->name, $agenda->report_config['signer1_name']);
        $this->assertEquals($pimpinanUser->nip, $agenda->report_config['signer1_nip']);
        $this->assertEquals($notulisUser->name, $agenda->report_config['signer2_name']);
        $this->assertEquals($notulisUser->nip, $agenda->report_config['signer2_nip']);

        $resolved = $agenda->resolved_report_config;
        $this->assertEquals($pimpinanUser->name, $resolved['signer1_name']);
        $this->assertEquals($pimpinanUser->nip, $resolved['signer1_nip']);
        $this->assertEquals($notulisUser->name, $resolved['signer2_name']);
        $this->assertEquals($notulisUser->nip, $resolved['signer2_nip']);

        // Check that PDF export reflects the newly assigned signers
        $pdfResponse = $this->actingAs($admin)->get("/admin/reports/{$agenda->id}/export/pdf");
        $pdfResponse->assertStatus(200);
        $pdfResponse->assertSee($pimpinanUser->name);
        $pdfResponse->assertSee($pimpinanUser->nip);
        $pdfResponse->assertSee($notulisUser->name);
        $pdfResponse->assertSee($notulisUser->nip);

        $agenda->delete();
        $pimpinanUser->delete();
        $notulisUser->delete();
    }

    public function test_export_defaults_to_showing_selfie_photos_and_uses_normal_font_style_for_kop_and_clean_borders(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $unit = Unit::first() ?? Unit::create(['nama_unit' => 'Unit Uji 39', 'kode_unit' => 'UU39']);

        // Create an agenda with no attendances to verify empty table colspan and default options
        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Uji Poin 39',
            'tanggal_rapat' => now()->toDateString(),
            'waktu_mulai' => now()->addHour(),
            'waktu_selesai' => now()->addHours(2),
            'lokasi_ruang' => 'Ruang Akreditasi',
            'status' => 'scheduled',
            'unit_id' => $unit->id,
            'created_by' => $admin->id,
        ]);

        // 1. PDF export by default (GET)
        $pdfResponse = $this->actingAs($admin)->get("/admin/reports/{$agenda->id}/export/pdf");
        $pdfResponse->assertStatus(200);
        $pdfContent = $pdfResponse->getContent();

        // Must include Foto Kehadiran by default
        $this->assertStringContainsString('Foto Kehadiran', $pdfContent);
        // Kop surat paragraph must have normal font-style
        $this->assertStringContainsString('.header-kop p {', $pdfContent);
        $this->assertStringContainsString('font-style: normal;', $pdfContent);
        // Table must have proper border styling
        $this->assertStringContainsString('border: 1px solid #000;', $pdfContent);
        // Colspan on empty state with all default columns (No, Nama, NIP, Unit, Waktu, Foto, TTD) = 7
        $this->assertStringContainsString('colspan="7"', $pdfContent);

        // 2. Word export by default (GET)
        $wordResponse = $this->actingAs($admin)->get("/admin/reports/{$agenda->id}/export/word");
        $wordResponse->assertStatus(200);
        $wordContent = $wordResponse->getContent();

        // Must include Foto Kehadiran by default
        $this->assertStringContainsString('Foto Kehadiran', $wordContent);
        // Kop surat paragraph in Word must have normal font-style
        $this->assertStringContainsString('font-style: normal;', $wordContent);
        // Table must have border="1" attribute for MS Word border rendering
        $this->assertStringContainsString('border="1" cellspacing="0" cellpadding="0"', $wordContent);
        // Empty state in Word must have colspan="7"
        $this->assertStringContainsString('colspan="7"', $wordContent);

        // 3. When show_selfie_photos is explicitly unchecked (value '0')
        $pdfWithoutSelfie = $this->actingAs($admin)->post("/admin/reports/{$agenda->id}/export/pdf", [
            'show_selfie_photos' => '0',
            'show_attendees' => '1',
            'show_nip' => '1',
            'show_unit' => '1',
            'show_attendance_time' => '1',
            'show_attendee_signatures' => '1',
        ]);
        $pdfWithoutSelfie->assertStatus(200);
        $pdfNoSelfieContent = $pdfWithoutSelfie->getContent();
        $this->assertStringNotContainsString('Foto Kehadiran', $pdfNoSelfieContent);
        // Colspan without photo column = 6
        $this->assertStringContainsString('colspan="6"', $pdfNoSelfieContent);

        $agenda->delete();
    }
}

