<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\AgendaDocumentation;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AgendaManagementTest extends TestCase
{
    public function test_admin_can_view_agendas_index_but_staff_is_forbidden(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $staff = User::where('role', 'staff')->first();

        $this->actingAs($superadmin)->get('/admin/agendas')->assertStatus(200)->assertSee('Agenda Rapat Kedinasan');
        $this->actingAs($staff)->get('/admin/agendas')->assertStatus(403);
    }

    public function test_administrator_can_create_universal_agenda_with_circular_file(): void
    {
        Storage::fake('public');
        $superadmin = User::where('role', 'administrator')->first();
        $file = UploadedFile::fake()->create('surat_undangan.pdf', 1024, 'application/pdf');

        $uniqueTitle = 'Rapat Pleno Koordinasi ' . uniqid();

        $response = $this->actingAs($superadmin)->post('/admin/agendas', [
            'judul_rapat' => $uniqueTitle,
            'jenis_rapat' => 'pleno',
            'tipe_rapat' => 'hybrid',
            'lokasi_ruang' => 'Auditorium Utama Lantai 3',
            'link_meeting' => 'https://zoom.us/j/987654321',
            'waktu_mulai' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'waktu_selesai' => now()->addDays(2)->addHours(3)->format('Y-m-d H:i:s'),
            'is_all_units' => true,
            'surat_edaran' => $file,
            'status' => 'scheduled',
        ]);

        $this->assertDatabaseHas('agendas', [
            'judul_rapat' => $uniqueTitle,
            'is_all_units' => true,
            'tipe_rapat' => 'hybrid',
        ]);

        $agenda = Agenda::where('judul_rapat', $uniqueTitle)->first();
        $this->assertNotNull($agenda->surat_edaran_path);
        Storage::disk('public')->assertExists($agenda->surat_edaran_path);

        $response->assertRedirect("/admin/agendas/{$agenda->id}");

        $this->assertDatabaseHas('activity_logs', [
            'activity_type' => 'CREATE_AGENDA',
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_admin_unit_can_create_scoped_agenda(): void
    {
        $adminAkm = User::where('username', 'admin_akademik')->first();
        $unitAkm = Unit::where('kode_unit', 'POKJA-AKM')->first();
        $unitKlb = Unit::where('kode_unit', 'POKJA-KLB')->first();

        $uniqueTitle = 'Rapat Koordinasi Bersama ' . uniqid();

        $response = $this->actingAs($adminAkm)->post('/admin/agendas', [
            'judul_rapat' => $uniqueTitle,
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Rapat Pokja AKM',
            'waktu_mulai' => now()->addDays(4)->setHour(9)->setMinute(0)->format('Y-m-d H:i:s'),
            'waktu_selesai' => now()->addDays(4)->setHour(11)->setMinute(0)->format('Y-m-d H:i:s'),
            'is_all_units' => false,
            'unit_ids' => [$unitAkm->id, $unitKlb->id],
            'status' => 'scheduled',
        ]);

        $agenda = Agenda::where('judul_rapat', $uniqueTitle)->first();
        $this->assertNotNull($agenda);
        $this->assertCount(2, $agenda->units);
    }

    public function test_staff_cannot_create_agenda(): void
    {
        $staff = User::where('role', 'staff')->first();

        $response = $this->actingAs($staff)->post('/admin/agendas', [
            'judul_rapat' => 'Rapat Ilegal',
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang 1',
            'waktu_mulai' => now()->addDay()->format('Y-m-d H:i:s'),
            'waktu_selesai' => now()->addDay()->addHours(2)->format('Y-m-d H:i:s'),
            'is_all_units' => true,
        ]);

        $response->assertStatus(403);
    }

    public function test_agenda_status_transition_works(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        $response = $this->actingAs($superadmin)->patch("/admin/agendas/{$agenda->id}/status", [
            'status' => 'ongoing',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('agendas', [
            'id' => $agenda->id,
            'status' => 'ongoing',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'activity_type' => 'UPDATE_AGENDA_STATUS',
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_user_can_update_notulensi_and_upload_documentation_photos(): void
    {
        Storage::fake('public');
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        $photo1 = UploadedFile::fake()->create('foto_suasana_1.jpg', 500, 'image/jpeg');
        $photo2 = UploadedFile::fake()->create('foto_suasana_2.webp', 500, 'image/webp');

        $response = $this->actingAs($superadmin)->put("/admin/agendas/{$agenda->id}/notulen", [
            'notulensi' => "1. Pembukaan oleh Kepala Lembaga.\n2. Pembahasan evaluasi pelaporan semester.",
            'kesimpulan' => "Seluruh PTS wajib merampungkan sinkronisasi data selambatnya akhir bulan ini.",
            'photos' => [$photo1, $photo2],
            'captions' => ['Suasana Pembukaan Rapat', 'Pemaparan Materi Pokja'],
        ]);

        $response->assertRedirect("/admin/agendas/{$agenda->id}");

        $this->assertDatabaseHas('agendas', [
            'id' => $agenda->id,
            'notulensi' => "1. Pembukaan oleh Kepala Lembaga.\n2. Pembahasan evaluasi pelaporan semester.",
        ]);

        $this->assertDatabaseHas('agenda_documentations', [
            'agenda_id' => $agenda->id,
            'caption' => 'Suasana Pembukaan Rapat',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'activity_type' => 'UPDATE_AGENDA_MINUTES',
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_user_can_delete_photo_documentation(): void
    {
        Storage::fake('public');
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();
        $doc = AgendaDocumentation::where('agenda_id', $agenda->id)->first();

        if (!$doc) {
            $doc = AgendaDocumentation::create([
                'agenda_id' => $agenda->id,
                'file_path' => 'documentations/sample.jpg',
                'caption' => 'Sample Doc',
            ]);
        }

        $response = $this->actingAs($superadmin)->delete("/admin/agendas/{$agenda->id}/documentations/{$doc->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('agenda_documentations', ['id' => $doc->id]);
    }

    public function test_admin_unit_can_access_create_agenda_page(): void
    {
        $adminAkm = User::where('username', 'admin_akademik')->first();

        $response = $this->actingAs($adminAkm)->get(route('admin.agendas.create'));

        $response->assertStatus(200);
        $response->assertSee('Buat Agenda Rapat Baru');
        $response->assertSee($adminAkm->unit->nama_unit);
    }

    public function test_agenda_creation_auto_calculates_waktu_selesai_if_omitted(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $uniqueTitle = 'Rapat Auto Selesai ' . uniqid();

        $startTime = now()->addDays(3)->startOfHour();

        $response = $this->actingAs($superadmin)->post('/admin/agendas', [
            'judul_rapat' => $uniqueTitle,
            'jenis_rapat' => 'evaluasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Rapat Lantai 1',
            'waktu_mulai' => $startTime->format('Y-m-d H:i:s'),
            'waktu_selesai' => '', // omitted
            'is_all_units' => true,
        ]);

        $agenda = Agenda::where('judul_rapat', $uniqueTitle)->first();
        $this->assertNotNull($agenda);
        $this->assertNull($agenda->waktu_selesai);
        $this->assertStringContainsString('WIB s.d. Selesai', $agenda->rentang_waktu);

        // Verify view rendering is null-safe
        $showResponse = $this->actingAs($superadmin)->get("/admin/agendas/{$agenda->id}");
        $showResponse->assertStatus(200);
        $showResponse->assertSee('WIB s.d. Selesai');

        $agenda->delete();
    }

    public function test_agenda_creation_normalizes_meeting_link(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $uniqueTitle = 'Rapat Daring Normalisasi ' . uniqid();

        $response = $this->actingAs($superadmin)->post('/admin/agendas', [
            'judul_rapat' => $uniqueTitle,
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'online',
            'link_meeting' => 'meet.google.com/abc-defg-hij', // without https://
            'waktu_mulai' => now()->addDay()->format('Y-m-d H:i:s'),
            'is_all_units' => true,
        ]);

        $agenda = Agenda::where('judul_rapat', $uniqueTitle)->first();
        $this->assertNotNull($agenda);
        $this->assertEquals('https://meet.google.com/abc-defg-hij', $agenda->link_meeting);
    }

    public function test_staff_can_view_agenda_details_via_non_admin_route_and_cannot_view_attendees(): void
    {
        $staff = User::where('role', 'staff')->first();
        $agenda = Agenda::where('is_all_units', true)->first();

        // Check dashboard displays non-admin link for staff
        $dashboardResponse = $this->actingAs($staff)->get('/dashboard');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee("agendas/{$agenda->id}");
        $dashboardResponse->assertDontSee("admin/agendas/{$agenda->id}");

        // Check staff is forbidden from admin agenda detail route
        $adminRouteResponse = $this->actingAs($staff)->get("/admin/agendas/{$agenda->id}");
        $adminRouteResponse->assertStatus(403);

        // Check staff can access the dedicated non-admin agenda detail page
        $detailResponse = $this->actingAs($staff)->get("/agendas/{$agenda->id}");
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee($agenda->judul_rapat);
        $detailResponse->assertSee('Notulensi');
        $detailResponse->assertSee('Kesimpulan Rapat');
        $detailResponse->assertSee('Dokumentasi Foto Rapat');
        $detailResponse->assertSee('Kembali ke Dashboard');

        // Verify staff CANNOT see other participants' attendance list
        $detailResponse->assertDontSee('Peserta Hadir');
        $detailResponse->assertSee('Status Presensi Anda');
    }

    public function test_staff_cannot_bypass_to_view_draft_agenda(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $staff = User::where('role', 'staff')->first();

        $draftAgenda = Agenda::create([
            'created_by' => $superadmin->id,
            'judul_rapat' => 'Konsep Agenda Rahasia Internal ' . uniqid(),
            'slug' => 'konsep-agenda-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'status' => 'draft',
            'waktu_mulai' => now()->addDays(5),
            'waktu_selesai' => now()->addDays(5)->addHours(2),
            'is_all_units' => true,
        ]);

        $response = $this->actingAs($staff)->get("/agendas/{$draftAgenda->id}");
        $response->assertStatus(403);
    }

    public function test_staff_cannot_bypass_to_view_other_unit_restricted_agenda(): void
    {
        $unitKlb = \App\Models\Unit::where('kode_unit', 'POKJA-KLB')->first();
        $staffAkm = User::where('username', 'staff_rizky')->first(); // staff in POKJA-AKM
        $superadmin = User::where('role', 'administrator')->first();

        $agendaKlb = Agenda::create([
            'created_by' => $superadmin->id,
            'judul_rapat' => 'Rapat Terbatas KLB Khusus ' . uniqid(),
            'slug' => 'rapat-klb-khusus-' . uniqid(),
            'jenis_rapat' => 'terbatas',
            'tipe_rapat' => 'offline',
            'status' => 'scheduled',
            'waktu_mulai' => now()->addDays(2),
            'waktu_selesai' => now()->addDays(2)->addHours(2),
            'is_all_units' => false,
        ]);
        $agendaKlb->units()->attach($unitKlb->id);

        $response = $this->actingAs($staffAkm)->get("/agendas/{$agendaKlb->id}");
        $response->assertStatus(403);
    }

    public function test_staff_sees_attendance_button_on_ongoing_agenda_detail(): void
    {
        $staff = User::where('role', 'staff')->first();
        $agenda = Agenda::where('is_all_units', true)->first();
        $agenda->update(['status' => 'ongoing']);

        // Remove any prior attendance for this staff so we test the check-in button
        $agenda->attendances()->where('user_id', $staff->id)->delete();

        $response = $this->actingAs($staff)->get("/agendas/{$agenda->id}");
        $response->assertStatus(200);
        $response->assertSee('Isi Presensi Sekarang');
    }

    public function test_admin_agenda_detail_renders_clean_management_actions_without_glitch(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::where('is_all_units', true)->first();
        $agenda->update(['status' => 'ongoing']);

        $response = $this->actingAs($superadmin)->get("/admin/agendas/{$agenda->id}");
        $response->assertStatus(200);

        // Assert clean management buttons are present in hero
        $response->assertSee('Daftar Agenda');
        $response->assertSee('Edit Agenda');
        $response->assertSee('Ekspor PDF');
        $response->assertSee('Ekspor Word');
        $response->assertSee('Tutup Rapat &amp; Selesaikan', false);

        // Assert attendance list is visible to admin
        $response->assertSee('Peserta Hadir');

        // Assert shortcut links on panels
        $response->assertSee('Edit Notulensi');
        $response->assertSee('Unggah Foto');
    }

    public function test_admin_can_create_and_update_agenda_with_custom_meeting_type(): void
    {
        $superadmin = User::where('role', 'administrator')->first();

        // 1. Create agenda with custom free-text meeting type
        $customTitle = 'Sosialisasi BKD LLDIKTI ' . uniqid();
        $customType = 'Sosialisasi & Workshop BKD 2026';

        $createResponse = $this->actingAs($superadmin)->post('/admin/agendas', [
            'judul_rapat' => $customTitle,
            'jenis_rapat' => $customType,
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Aula Gedung A Lantai 3',
            'waktu_mulai' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'waktu_selesai' => now()->addDays(2)->addHours(4)->format('Y-m-d H:i:s'),
            'is_all_units' => true,
        ]);

        $agenda = Agenda::where('judul_rapat', $customTitle)->first();
        $this->assertNotNull($agenda);
        $createResponse->assertRedirect(route('admin.agendas.show', $agenda));
        $this->assertEquals($customType, $agenda->jenis_rapat);

        // 2. View create form and assert input text and datalist exist
        $createFormRes = $this->actingAs($superadmin)->get('/admin/agendas/create');
        $createFormRes->assertStatus(200);
        $createFormRes->assertSee('id="jenis_rapat"', false);
        $createFormRes->assertSee('list="jenis_rapat_suggestions"', false);
        $createFormRes->assertSee('<datalist id="jenis_rapat_suggestions">', false);

        // 3. Update agenda with another custom free-text meeting type
        $updatedType = 'Bimbingan Teknis Akreditasi Mandiri';
        $updateResponse = $this->actingAs($superadmin)->put("/admin/agendas/{$agenda->id}", [
            'judul_rapat' => $customTitle,
            'jenis_rapat' => $updatedType,
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Sidang Utama',
            'waktu_mulai' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'waktu_selesai' => now()->addDays(2)->addHours(4)->format('Y-m-d H:i:s'),
            'is_all_units' => true,
        ]);

        $updateResponse->assertRedirect(route('admin.agendas.show', $agenda));
        $agenda->refresh();
        $this->assertEquals($updatedType, $agenda->jenis_rapat);

        // 4. Detail page displays the custom meeting type
        $detailResponse = $this->actingAs($superadmin)->get("/admin/agendas/{$agenda->id}");
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee($updatedType);
    }

    public function test_administrator_can_filter_agendas_by_unit(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $unitKlb = Unit::where('kode_unit', 'POKJA-KLB')->first();
        $unitTu = Unit::where('kode_unit', 'BAG-TU')->first();

        $agendaKlb = Agenda::create([
            'created_by' => $superadmin->id,
            'judul_rapat' => 'Rapat Internal KLB Filter Test ' . uniqid(),
            'slug' => 'rapat-klb-filter-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang KLB',
            'waktu_mulai' => now()->addDays(3),
            'waktu_selesai' => now()->addDays(3)->addHours(2),
            'is_all_units' => false,
            'status' => 'scheduled',
        ]);
        $agendaKlb->units()->sync([$unitKlb->id]);

        $agendaTu = Agenda::create([
            'created_by' => $superadmin->id,
            'judul_rapat' => 'Rapat Internal TU Filter Test ' . uniqid(),
            'slug' => 'rapat-tu-filter-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang TU',
            'waktu_mulai' => now()->addDays(4),
            'waktu_selesai' => now()->addDays(4)->addHours(2),
            'is_all_units' => false,
            'status' => 'scheduled',
        ]);
        $agendaTu->units()->sync([$unitTu->id]);

        // 1. Agenda index shows unit filter dropdown for Administrator
        $indexResponse = $this->actingAs($superadmin)->get('/admin/agendas');
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('name="unit_id"', false);
        $indexResponse->assertSee('Semua Sasaran Unit Kerja');

        // 2. Filter by unit KLB
        $filterKlbResponse = $this->actingAs($superadmin)->get("/admin/agendas?unit_id={$unitKlb->id}");
        $filterKlbResponse->assertStatus(200);
        $filterKlbResponse->assertSee($agendaKlb->judul_rapat);
        $filterKlbResponse->assertDontSee($agendaTu->judul_rapat);

        // 3. Filter by unit TU
        $filterTuResponse = $this->actingAs($superadmin)->get("/admin/agendas?unit_id={$unitTu->id}");
        $filterTuResponse->assertStatus(200);
        $filterTuResponse->assertSee($agendaTu->judul_rapat);
        $filterTuResponse->assertDontSee($agendaKlb->judul_rapat);
    }

    public function test_agenda_cannot_be_deleted_if_it_has_attendances(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $staff = User::where('role', 'staff')->first();

        $agenda = Agenda::create([
            'created_by' => $superadmin->id,
            'judul_rapat' => 'Rapat dengan Presensi ' . uniqid(),
            'slug' => 'rapat-presensi-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang 1',
            'waktu_mulai' => now()->subHours(2),
            'waktu_selesai' => now()->subHour(),
            'is_all_units' => true,
            'status' => 'completed',
        ]);

        // Add an attendance record
        \App\Models\Attendance::create([
            'agenda_id' => $agenda->id,
            'user_id' => $staff->id,
            'signed_at' => now()->subHours(2),
            'selfie_path' => 'selfies/test.jpg',
            'signature_path' => 'signatures/test.png',
        ]);

        // Attempt deletion
        $response = $this->actingAs($superadmin)->delete("/admin/agendas/{$agenda->id}");
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('agendas', ['id' => $agenda->id]);

        // Clean up
        $agenda->attendances()->delete();
        $agenda->delete();
    }

    public function test_cannot_manage_minutes_for_cancelled_agenda(): void
    {
        $superadmin = User::where('role', 'administrator')->first();

        $agenda = Agenda::create([
            'created_by' => $superadmin->id,
            'judul_rapat' => 'Rapat Dibatalkan ' . uniqid(),
            'slug' => 'rapat-batal-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang 1',
            'waktu_mulai' => now()->addDays(1),
            'waktu_selesai' => now()->addDays(1)->addHours(2),
            'is_all_units' => true,
            'status' => 'cancelled',
        ]);

        // Accessing minutes editor on cancelled agenda should be forbidden (403)
        $this->actingAs($superadmin)->get("/admin/agendas/{$agenda->id}/notulen")->assertStatus(403);

        // Submitting minutes update on cancelled agenda should be forbidden (403)
        $this->actingAs($superadmin)->put("/admin/agendas/{$agenda->id}/notulen", [
            'notulensi' => 'Catatan rapat dibatalkan',
        ])->assertStatus(403);

        $agenda->delete();
    }

    public function test_agendas_index_displays_draft_tab_after_all_tab(): void
    {
        $superadmin = User::where('role', 'administrator')->first();

        // Create a draft agenda
        $draftAgenda = Agenda::create([
            'created_by' => $superadmin->id,
            'judul_rapat' => 'Draf Konsep Rapat ' . uniqid(),
            'slug' => 'draf-konsep-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Draf',
            'waktu_mulai' => now()->addDays(3),
            'waktu_selesai' => now()->addDays(3)->addHours(2),
            'is_all_units' => true,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($superadmin)->get('/admin/agendas');
        $response->assertStatus(200);

        $content = $response->getContent();
        $posSemua = strpos($content, 'Semua (');
        $posDraft = strpos($content, 'Draf / Konsep (');
        $posBerlangsung = strpos($content, 'Sedang Berlangsung (');

        $this->assertNotFalse($posSemua, 'Tab Semua should be visible');
        $this->assertNotFalse($posDraft, 'Tab Draf / Konsep should be visible');
        $this->assertNotFalse($posBerlangsung, 'Tab Sedang Berlangsung should be visible');
        $this->assertTrue($posSemua < $posDraft, 'Tab Draf / Konsep must be placed after tab Semua');
        $this->assertTrue($posDraft < $posBerlangsung, 'Tab Draf / Konsep must be placed before tab Sedang Berlangsung');

        // Clean up
        $draftAgenda->delete();
    }

    public function test_filter_draft_agendas_works_for_admin_and_admin_unit(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $adminAkm = User::where('username', 'admin_akademik')->first();
        $unitAkm = Unit::where('kode_unit', 'POKJA-AKM')->first();
        $unitKlb = Unit::where('kode_unit', 'POKJA-KLB')->first();

        // Draft for all units
        $draftUniversal = Agenda::create([
            'created_by' => $superadmin->id,
            'judul_rapat' => 'Universal Draft ' . uniqid(),
            'slug' => 'universal-draft-' . uniqid(),
            'jenis_rapat' => 'pleno',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Pleno',
            'waktu_mulai' => now()->addDays(4),
            'waktu_selesai' => now()->addDays(4)->addHours(2),
            'is_all_units' => true,
            'status' => 'draft',
        ]);

        // Draft for AKM unit only
        $draftAkm = Agenda::create([
            'created_by' => $adminAkm->id,
            'judul_rapat' => 'AKM Draft ' . uniqid(),
            'slug' => 'akm-draft-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang AKM',
            'waktu_mulai' => now()->addDays(5),
            'waktu_selesai' => now()->addDays(5)->addHours(2),
            'is_all_units' => false,
            'status' => 'draft',
        ]);
        $draftAkm->units()->attach($unitAkm->id);

        // Draft for KLB unit only
        $draftKlb = Agenda::create([
            'created_by' => $superadmin->id,
            'judul_rapat' => 'KLB Draft ' . uniqid(),
            'slug' => 'klb-draft-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang KLB',
            'waktu_mulai' => now()->addDays(6),
            'waktu_selesai' => now()->addDays(6)->addHours(2),
            'is_all_units' => false,
            'status' => 'draft',
        ]);
        $draftKlb->units()->attach($unitKlb->id);

        // Verify scopeDraft method
        $this->assertTrue(Agenda::draft()->where('id', $draftUniversal->id)->exists());

        // Superadmin filters ?status=draft -> sees all 3 drafts
        $superadminResponse = $this->actingAs($superadmin)->get('/admin/agendas?status=draft');
        $superadminResponse->assertStatus(200);
        $superadminResponse->assertSee($draftUniversal->judul_rapat);
        $superadminResponse->assertSee($draftAkm->judul_rapat);
        $superadminResponse->assertSee($draftKlb->judul_rapat);

        // Admin AKM filters ?status=draft -> sees universal and AKM draft, but NOT KLB draft
        $akmResponse = $this->actingAs($adminAkm)->get('/admin/agendas?status=draft');
        $akmResponse->assertStatus(200);
        $akmResponse->assertSee($draftUniversal->judul_rapat);
        $akmResponse->assertSee($draftAkm->judul_rapat);
        $akmResponse->assertDontSee($draftKlb->judul_rapat);

        // Clean up
        $draftAkm->units()->detach();
        $draftAkm->delete();
        $draftKlb->units()->detach();
        $draftKlb->delete();
        $draftUniversal->delete();
    }

    public function test_create_and_edit_agenda_page_renders_wib_schedule_picker(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        // 1. Create page checks
        $createRes = $this->actingAs($superadmin)->get('/admin/agendas/create');
        $createRes->assertStatus(200);
        $createRes->assertSee('wib-schedule-picker-root', false);
        $createRes->assertSee('Waktu Mulai Rapat');
        $createRes->assertSee('Waktu Selesai Rapat');
        $createRes->assertSee('Pukul (WIB)');
        $createRes->assertSee('WIB (UTC+7)');
        $createRes->assertSee('Hingga Selesai');
        $createRes->assertDontSee('24 Jam (WIB)');
        $createRes->assertDontSee('(Opsional)');
        $createRes->assertDontSee('Pilihan Cepat Jam:');
        $createRes->assertDontSee('Tambah Durasi / Preset:');
        $createRes->assertDontSee('type="datetime-local"', false);

        // 2. Edit page checks
        $editRes = $this->actingAs($superadmin)->get("/admin/agendas/{$agenda->id}/edit");
        $editRes->assertStatus(200);
        $editRes->assertSee('wib-schedule-picker-root', false);
        $editRes->assertSee('Waktu Mulai Rapat');
        $editRes->assertSee('Waktu Selesai Rapat');
        $editRes->assertSee('Pukul (WIB)');
        $editRes->assertDontSee('24 Jam (WIB)');
        $editRes->assertDontSee('(Opsional)');
        $editRes->assertDontSee('Pilihan Cepat Jam:');
        $editRes->assertDontSee('type="datetime-local"', false);
    }

    public function test_admin_can_create_agenda_without_end_time(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $title = 'Rapat Koordinasi Hingga Selesai ' . uniqid();
        $startStr = now()->addDays(2)->format('Y-m-d') . 'T09:00';

        $response = $this->actingAs($superadmin)->post('/admin/agendas', [
            'judul_rapat' => $title,
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Rapat Utama',
            'waktu_mulai' => $startStr,
            'waktu_selesai' => '', // Empty string for "Hingga Selesai"
            'is_all_units' => true,
            'status' => 'scheduled',
        ]);

        $agenda = Agenda::where('judul_rapat', $title)->first();
        $this->assertNotNull($agenda);
        // The system stores null for "Hingga Selesai" agendas
        $this->assertNull($agenda->waktu_selesai);
        $this->assertStringContainsString('WIB s.d. Selesai', $agenda->rentang_waktu);
        $response->assertRedirect("/admin/agendas/{$agenda->id}");

        $agenda->delete();
    }

    public function test_admin_can_update_agenda_to_until_finished(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $startTime = now()->addDays(4)->setTime(10, 0);
        $endTime = now()->addDays(4)->setTime(12, 0);

        $agenda = Agenda::create([
            'created_by' => $superadmin->id,
            'judul_rapat' => 'Rapat Update Hingga Selesai ' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Rapat 2',
            'waktu_mulai' => $startTime,
            'waktu_selesai' => $endTime,
            'is_all_units' => true,
            'status' => 'scheduled',
        ]);

        $this->assertNotNull($agenda->waktu_selesai);

        // Update with empty waktu_selesai (checking "Hingga Selesai")
        $response = $this->actingAs($superadmin)->put("/admin/agendas/{$agenda->id}", [
            'judul_rapat' => $agenda->judul_rapat,
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Rapat 2',
            'waktu_mulai' => $startTime->format('Y-m-d H:i:s'),
            'waktu_selesai' => '',
            'is_all_units' => true,
            'status' => 'scheduled',
        ]);

        $response->assertRedirect("/admin/agendas/{$agenda->id}");

        $agenda->refresh();
        $this->assertNull($agenda->waktu_selesai);
        $this->assertEquals('10:00 WIB s.d. Selesai', $agenda->rentang_waktu);

        // Verify views render correctly without errors
        $indexResponse = $this->actingAs($superadmin)->get('/admin/agendas');
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('WIB s.d. Selesai');

        $agenda->delete();
    }
}


