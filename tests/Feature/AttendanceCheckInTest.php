<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendanceCheckInTest extends TestCase
{
    private string $validJpegBase64 = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=';
    private string $validPngBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    public function test_user_can_view_attendance_portal(): void
    {
        $staff = User::where('role', 'staff')->first();

        $response = $this->actingAs($staff)->get('/presensi');

        $response->assertStatus(200);
        $response->assertSee('Portal Presensi Kehadiran');
    }

    public function test_staff_sidebar_navigation_contains_only_dashboard_and_history(): void
    {
        $staff = User::where('role', 'staff')->first();

        $response = $this->actingAs($staff)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertSee('Riwayat Kehadiran');
        $response->assertDontSee('Portal Presensi');
    }

    public function test_attendance_history_filter_does_not_contain_portal_presensi_button(): void
    {
        $staff = User::where('role', 'staff')->first();

        $response = $this->actingAs($staff)->get(route('attendances.history'));

        $response->assertStatus(200);
        $response->assertDontSee('Portal Presensi Aktif');
    }

    public function test_user_can_view_checkin_form_for_ongoing_meeting(): void
    {
        $staff = User::where('username', 'staff_rizky')->first();
        $agenda = Agenda::first();
        $agenda->update(['status' => 'ongoing', 'is_all_units' => true]);

        $response = $this->actingAs($staff)->get("/agendas/{$agenda->id}/presensi");

        $response->assertStatus(200);
        $response->assertSee('Formulir Presensi Rapat Kedinasan');
        $response->assertSee('Foto Selfie Wajah');
        $response->assertSee('Tanda Tangan Digital');
    }

    public function test_user_cannot_access_checkin_for_scheduled_meeting(): void
    {
        $staff = User::where('username', 'staff_rizky')->first();
        $scheduledAgenda = Agenda::first();
        $scheduledAgenda->update(['status' => 'scheduled']);

        $response = $this->actingAs($staff)->get("/agendas/{$scheduledAgenda->id}/presensi");

        $response->assertRedirect("/agendas/{$scheduledAgenda->id}");
        $response->assertSessionHas('error');
    }

    public function test_ineligible_user_cannot_access_checkin_for_restricted_unit_meeting(): void
    {
        $unitKlb = Unit::where('kode_unit', 'POKJA-KLB')->first();
        $staffAkm = User::where('username', 'staff_rizky')->first(); // staff in POKJA-AKM

        $agendaKlb = Agenda::create([
            'created_by' => $staffAkm->id,
            'judul_rapat' => 'Rapat Terbatas Khusus KLB ' . uniqid(),
            'slug' => 'rapat-klb-' . uniqid(),
            'jenis_rapat' => 'terbatas',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang 1',
            'waktu_mulai' => now(),
            'waktu_selesai' => now()->addHours(2),
            'is_all_units' => false,
            'status' => 'ongoing',
        ]);
        $agendaKlb->units()->sync([$unitKlb->id]);

        $response = $this->actingAs($staffAkm)->get("/agendas/{$agendaKlb->id}/presensi");

        $response->assertStatus(403);
    }

    public function test_user_can_successfully_check_in_with_canvas_base64_media(): void
    {
        Storage::fake('public');
        $staff = User::where('username', 'staff_nurul')->first();
        
        $agenda = Agenda::create([
            'created_by' => $staff->id,
            'judul_rapat' => 'Rapat Presensi Test ' . uniqid(),
            'slug' => 'rapat-presensi-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'waktu_mulai' => now(),
            'waktu_selesai' => now()->addHours(2),
            'is_all_units' => true,
            'status' => 'ongoing',
        ]);

        $response = $this->actingAs($staff)->post("/agendas/{$agenda->id}/presensi", [
            'selfie_data' => $this->validJpegBase64,
            'signature_data' => $this->validPngBase64,
        ]);

        $this->assertDatabaseHas('attendances', [
            'agenda_id' => $agenda->id,
            'user_id' => $staff->id,
        ]);

        $attendance = Attendance::where('agenda_id', $agenda->id)->where('user_id', $staff->id)->first();
        $this->assertNotNull($attendance);

        // Verify files stored in storage
        Storage::disk('public')->assertExists($attendance->selfie_path);
        Storage::disk('public')->assertExists($attendance->signature_path);

        // Verify Audit Log
        $this->assertDatabaseHas('activity_logs', [
            'activity_type' => 'RECORD_ATTENDANCE',
            'user_id' => $staff->id,
        ]);

        $response->assertRedirect("/agendas/{$agenda->id}/presensi/{$attendance->id}/sukses");
    }

    public function test_anti_double_checkin_prevention(): void
    {
        Storage::fake('public');
        $staff = User::where('username', 'staff_nurul')->first();
        $agenda = Agenda::where('judul_rapat', 'like', 'Rapat Presensi Test%')->first();

        if (!$agenda) {
            $agenda = Agenda::create([
                'created_by' => $staff->id,
                'judul_rapat' => 'Rapat Double Test ' . uniqid(),
                'slug' => 'rapat-double-' . uniqid(),
                'jenis_rapat' => 'koordinasi',
                'tipe_rapat' => 'offline',
                'waktu_mulai' => now(),
                'waktu_selesai' => now()->addHours(2),
                'is_all_units' => true,
                'status' => 'ongoing',
            ]);
            Attendance::create([
                'agenda_id' => $agenda->id,
                'user_id' => $staff->id,
                'signed_at' => now(),
                'selfie_path' => 'attendances/test_selfie.jpg',
                'signature_path' => 'attendances/test_sig.png',
            ]);
        }

        // Second attempt by the same staff
        $response = $this->actingAs($staff)->post("/agendas/{$agenda->id}/presensi", [
            'selfie_data' => $this->validJpegBase64,
            'signature_data' => $this->validPngBase64,
        ]);

        $this->assertEquals(1, Attendance::where('agenda_id', $agenda->id)->where('user_id', $staff->id)->count());
    }

    public function test_user_can_view_official_attendance_receipt(): void
    {
        $staff = User::where('username', 'staff_nurul')->first();
        $attendance = Attendance::where('user_id', $staff->id)->latest('id')->first();

        if (!$attendance) {
            $agenda = Agenda::first();
            $attendance = Attendance::create([
                'agenda_id' => $agenda->id,
                'user_id' => $staff->id,
                'signed_at' => now(),
                'selfie_path' => 'attendances/test_selfie.jpg',
                'signature_path' => 'attendances/test_sig.png',
            ]);
        }

        $response = $this->actingAs($staff)->get("/agendas/{$attendance->agenda_id}/presensi/{$attendance->id}/sukses");

        $response->assertStatus(200);
        $response->assertSee('Tanda Terima Presensi Digital');
        $response->assertSee($staff->name);
        $response->assertSee($staff->nip);
        $response->assertSee('Kembali ke Dashboard');
        $response->assertDontSee('Portal Presensi');
    }

    public function test_attendance_rejects_disallowed_image_extension_payload(): void
    {
        $staff = User::where('username', 'staff_rizky')->first();
        $agenda = Agenda::first();
        $agenda->update(['status' => 'ongoing', 'is_all_units' => true]);

        // Attempt submission with php extension payload
        $maliciousPayload = 'data:image/php;base64,PD9waHAgcGhwaW5mbygpOyA/Pg==';

        $response = $this->actingAs($staff)->post("/agendas/{$agenda->id}/presensi", [
            'selfie_data' => $maliciousPayload,
            'signature_data' => $this->validPngBase64,
        ]);

        $response->assertSessionHasErrors(['selfie_data']);
    }

    public function test_responses_contain_enterprise_security_headers(): void
    {
        $response = $this->get('/login');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $this->assertStringContainsString("default-src 'self'", $response->headers->get('Content-Security-Policy'));
    }

    public function test_user_can_view_attendance_history_with_search_filter(): void
    {
        $staff = User::where('username', 'staff_nurul')->first();

        $response = $this->actingAs($staff)->get(route('attendances.history', ['search' => 'Rapat']));

        $response->assertStatus(200);
        $response->assertSee('Riwayat Kehadiran Rapat');
    }

    public function test_attendance_history_shows_empty_state_when_search_not_found(): void
    {
        $staff = User::where('username', 'staff_nurul')->first();

        $response = $this->actingAs($staff)->get(route('attendances.history', ['search' => 'NonExistentMeetingKeyword9999']));

        $response->assertStatus(200);
        $response->assertSee('Belum ada catatan kehadiran rapat yang ditemukan.');
        $response->assertSee('Reset Pencarian');
    }

    public function test_administrator_can_check_in_to_any_unit_scoped_meeting(): void
    {
        Storage::fake('public');

        $unitKlb = Unit::where('kode_unit', 'POKJA-KLB')->first();
        $superadmin = User::where('role', 'administrator')->first();

        // Meeting is strictly scoped to KLB unit (Superadmin has unit_id = null)
        $agendaKlb = Agenda::create([
            'created_by' => $superadmin->id,
            'judul_rapat' => 'Rapat Koordinasi Khusus KLB ' . uniqid(),
            'slug' => 'rapat-klb-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Rapat KLB',
            'waktu_mulai' => now()->subMinutes(15),
            'waktu_selesai' => now()->addHours(2),
            'is_all_units' => false,
            'status' => 'ongoing',
        ]);
        $agendaKlb->units()->sync([$unitKlb->id]);

        // 1. Superadmin can access check-in page without 403
        $pageResponse = $this->actingAs($superadmin)->get("/agendas/{$agendaKlb->id}/presensi");
        $pageResponse->assertStatus(200);
        $pageResponse->assertSee('Formulir Presensi Rapat Kedinasan');

        // 2. Admin agenda detail page shows "Isi Presensi Saya"
        $detailResponse = $this->actingAs($superadmin)->get("/admin/agendas/{$agendaKlb->id}");
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('Isi Presensi Saya');

        // 3. Superadmin can submit attendance check-in
        $submitResponse = $this->actingAs($superadmin)->post("/agendas/{$agendaKlb->id}/presensi", [
            'selfie_data' => $this->validJpegBase64,
            'signature_data' => $this->validPngBase64,
        ]);

        $attendance = Attendance::where('agenda_id', $agendaKlb->id)
            ->where('user_id', $superadmin->id)
            ->first();

        $this->assertNotNull($attendance);
        $submitResponse->assertRedirect(route('attendances.success', [$agendaKlb, $attendance]));

        // 4. Admin agenda detail page now shows "Anda Sudah Hadir"
        $detailAfterResponse = $this->actingAs($superadmin)->get("/admin/agendas/{$agendaKlb->id}");
        $detailAfterResponse->assertStatus(200);
        $detailAfterResponse->assertSee('Anda Sudah Hadir');
    }

    public function test_admin_unit_can_view_attendance_badge_of_staff_in_their_unit(): void
    {
        $adminAkm = User::where('username', 'admin_akademik')->first();
        $staffAkm = User::where('username', 'staff_rizky')->first(); // staff in AKM
        $superadmin = User::where('role', 'administrator')->first();

        // Agenda created by Superadmin
        $agenda = Agenda::create([
            'created_by' => $superadmin->id,
            'judul_rapat' => 'Rapat Pleno Lembaga ' . uniqid(),
            'slug' => 'rapat-pleno-' . uniqid(),
            'jenis_rapat' => 'pleno',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Aula',
            'waktu_mulai' => now()->subHours(2),
            'waktu_selesai' => now()->addHours(1),
            'is_all_units' => true,
            'status' => 'ongoing',
        ]);

        $attendance = Attendance::create([
            'agenda_id' => $agenda->id,
            'user_id' => $staffAkm->id,
            'signed_at' => now(),
            'selfie_path' => 'selfies/staff.jpg',
            'signature_path' => 'signatures/staff.png',
        ]);

        // Admin Unit can view digital attendance badge of staff in their unit
        $response = $this->actingAs($adminAkm)->get(route('attendances.success', [$agenda, $attendance]));
        $response->assertStatus(200);
        $response->assertSee('Tanda Terima Presensi Digital');
        $response->assertSee($staffAkm->name);
    }

    public function test_has_user_attended_uses_eager_loaded_relation_without_extra_queries(): void
    {
        $user = User::where('role', 'staff')->first();
        $agenda = Agenda::with('attendances')->first();

        // Enable query log
        \Illuminate\Support\Facades\DB::enableQueryLog();
        $initialQueryCount = count(\Illuminate\Support\Facades\DB::getQueryLog());

        // Call hasUserAttended when attendances is already eager loaded
        $attended = $agenda->hasUserAttended($user);

        // Zero additional queries should be executed because attendances was eager loaded
        $afterQueryCount = count(\Illuminate\Support\Facades\DB::getQueryLog());
        $this->assertEquals($initialQueryCount, $afterQueryCount);
    }
}
