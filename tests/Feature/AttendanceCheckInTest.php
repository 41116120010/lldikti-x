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

        $response->assertRedirect("/admin/agendas/{$scheduledAgenda->id}");
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
    }

    public function test_user_can_view_personal_attendance_history(): void
    {
        $staff = User::where('username', 'staff_nurul')->first();

        $response = $this->actingAs($staff)->get('/presensi/riwayat');

        $response->assertStatus(200);
        $response->assertSee('Riwayat Kehadiran Rapat');
    }
}
