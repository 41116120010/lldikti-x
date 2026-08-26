<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EndToEndUserAcceptanceTest extends TestCase
{
    private string $validJpegBase64 = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=';
    private string $validPngBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_complete_enterprise_lifecycle_uat_flow(): void
    {
        // -------------------------------------------------------------
        // STEP 1: SUPERADMIN WORKFLOW
        // -------------------------------------------------------------
        // 1.1 Login via 18-digit NIP
        $superadmin = User::where('username', 'superadmin')->first();
        $loginRes = $this->post('/login', [
            'login' => $superadmin->nip,
            'password' => 'Password123!',
        ]);
        $loginRes->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($superadmin);

        // 1.2 Master Data: Create new Unit
        $unitName = 'Kelompok Kerja Humas ' . uniqid();
        $unitCode = 'HMS' . substr(strtoupper(uniqid()), -4);
        $unitRes = $this->actingAs($superadmin)->post('/admin/units', [
            'nama_unit' => $unitName,
            'kode_unit' => $unitCode,
            'deskripsi' => 'Unit pengelola hubungan masyarakat dan publikasi',
            'is_active' => true,
        ]);
        $unitRes->assertRedirect('/admin/units');
        $newUnit = Unit::where('kode_unit', $unitCode)->first();
        $this->assertNotNull($newUnit);

        // 1.3 Create Admin Unit User
        $randomNip = '1988' . mt_rand(10000000000000, 99999999999999);
        $userRes = $this->actingAs($superadmin)->post('/admin/users', [
            'name' => 'Dr. Hendra Pratama, M.Kom',
            'nip' => $randomNip,
            'username' => 'admin_humas_' . uniqid(),
            'email' => 'hendra_' . uniqid() . '@lldikti10.kemdikbud.go.id',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
            'unit_id' => $newUnit->id,
            'is_active' => true,
        ]);
        $userRes->assertRedirect('/admin/users');
        $adminHumas = User::where('nip', $randomNip)->first();
        $this->assertNotNull($adminHumas);

        // 1.4 Create Meeting with Circular Letter
        $circularFile = UploadedFile::fake()->create('undangan_resmi.pdf', 1024, 'application/pdf');
        $agendaTitle = 'Rapat Koordinasi Publikasi LLDIKTI ' . uniqid();
        $agendaRes = $this->actingAs($superadmin)->post('/admin/agendas', [
            'judul_rapat' => $agendaTitle,
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'hybrid',
            'lokasi_ruang' => 'Ruang Sidang Lt. 3',
            'link_meeting' => 'https://zoom.us/j/123456789',
            'waktu_mulai' => now()->format('Y-m-d H:i:s'),
            'waktu_selesai' => now()->addHours(3)->format('Y-m-d H:i:s'),
            'is_all_units' => true,
            'surat_edaran' => $circularFile,
            'status' => 'scheduled',
        ]);
        $agenda = Agenda::where('judul_rapat', $agendaTitle)->first();
        $this->assertNotNull($agenda);
        $agendaRes->assertRedirect("/admin/agendas/{$agenda->id}");

        // 1.5 Open Attendance (Change Status to Ongoing)
        $statusRes = $this->actingAs($superadmin)->patch("/admin/agendas/{$agenda->id}/status", [
            'status' => 'ongoing',
        ]);
        $statusRes->assertRedirect();
        $this->assertEquals('ongoing', $agenda->fresh()->status);

        // -------------------------------------------------------------
        // STEP 2: STAFF ATTENDANCE WORKFLOW (WebRTC + Signature Pad)
        // -------------------------------------------------------------
        $staff = User::where('username', 'staff_rizky')->first();

        // 2.1 Staff visits attendance portal
        $this->actingAs($staff)->get('/presensi')->assertStatus(200)->assertSee($agendaTitle);

        // 2.2 Staff opens check-in form
        $this->actingAs($staff)->get("/agendas/{$agenda->id}/presensi")->assertStatus(200);

        // 2.3 Staff submits check-in with Selfie & Signature
        $checkInRes = $this->actingAs($staff)->post("/agendas/{$agenda->id}/presensi", [
            'selfie_data' => $this->validJpegBase64,
            'signature_data' => $this->validPngBase64,
        ]);
        $attendance = Attendance::where('agenda_id', $agenda->id)->where('user_id', $staff->id)->first();
        $this->assertNotNull($attendance);
        $checkInRes->assertRedirect("/agendas/{$agenda->id}/presensi/{$attendance->id}/sukses");

        // 2.4 Staff views official digital receipt
        $receiptRes = $this->actingAs($staff)->get("/agendas/{$agenda->id}/presensi/{$attendance->id}/sukses");
        $receiptRes->assertStatus(200)->assertSee('Kehadiran Terverifikasi Sah')->assertSee($staff->nip);

        // 2.5 Anti double check-in attempt
        $this->actingAs($staff)->post("/agendas/{$agenda->id}/presensi", [
            'selfie_data' => $this->validJpegBase64,
            'signature_data' => $this->validPngBase64,
        ]);
        $this->assertEquals(1, Attendance::where('agenda_id', $agenda->id)->where('user_id', $staff->id)->count());

        // -------------------------------------------------------------
        // STEP 3: NOTULENSI, CONCLUSIONS & PHOTO DOCUMENTATION
        // -------------------------------------------------------------
        $photo1 = UploadedFile::fake()->create('suasana_1.jpg', 500, 'image/jpeg');
        $minutesRes = $this->actingAs($superadmin)->put("/admin/agendas/{$agenda->id}/notulen", [
            'notulensi' => "1. Pembukaan oleh Kepala LLDIKTI.\n2. Pemaparan agenda kerja.",
            'kesimpulan' => "Rencana aksi publikasi selesai dalam 14 hari kerja.",
            'photos' => [$photo1],
            'captions' => ['Foto Dokumentasi Pembukaan'],
        ]);
        $minutesRes->assertRedirect("/admin/agendas/{$agenda->id}");

        // Complete the meeting
        $this->actingAs($superadmin)->patch("/admin/agendas/{$agenda->id}/status", [
            'status' => 'completed',
        ]);
        $this->assertEquals('completed', $agenda->fresh()->status);

        // -------------------------------------------------------------
        // STEP 4: EXECUTIVE REPORTING & OFFICIAL EXPORTS (PDF/Word/CSV)
        // -------------------------------------------------------------
        // 4.1 Reports Dashboard
        $reportRes = $this->actingAs($superadmin)->get('/admin/reports');
        $reportRes->assertStatus(200)->assertSee('Rekapitulasi & Laporan Rapat');

        // 4.2 PDF Export (Berita Acara Cetak)
        $pdfRes = $this->actingAs($superadmin)->get("/admin/reports/{$agenda->id}/export/pdf");
        $pdfRes->assertStatus(200);
        $pdfRes->assertSee('BERITA ACARA DAN DAFTAR HADIR RAPAT');
        $pdfRes->assertSee($staff->name);

        // 4.3 Word Export (.doc)
        $wordRes = $this->actingAs($superadmin)->get("/admin/reports/{$agenda->id}/export/word");
        $wordRes->assertStatus(200);
        $this->assertStringContainsString('.doc', $wordRes->headers->get('Content-Disposition'));

        // 4.4 CSV Export
        $csvRes = $this->actingAs($superadmin)->get('/admin/reports/summary/csv');
        $csvRes->assertStatus(200);

        // -------------------------------------------------------------
        // STEP 5: SECURITY HEADERS & AUDIT TRAIL VERIFICATION
        // -------------------------------------------------------------
        $pdfRes->assertHeader('X-Content-Type-Options', 'nosniff');
        $pdfRes->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $pdfRes->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Verify comprehensive audit trail entries
        $this->assertDatabaseHas('activity_logs', ['activity_type' => 'CREATE_UNIT']);
        $this->assertDatabaseHas('activity_logs', ['activity_type' => 'CREATE_USER']);
        $this->assertDatabaseHas('activity_logs', ['activity_type' => 'CREATE_AGENDA']);
        $this->assertDatabaseHas('activity_logs', ['activity_type' => 'RECORD_ATTENDANCE']);
        $this->assertDatabaseHas('activity_logs', ['activity_type' => 'UPDATE_AGENDA_STATUS']);
    }

    public function test_security_hardening_and_unauthorized_isolation(): void
    {
        // 1. Inactive User cannot login
        $randomNip = '1999' . mt_rand(10000000000000, 99999999999999);
        $username = 'nonaktif_' . uniqid();
        $inactiveUser = User::create([
            'name' => 'Pegawai Nonaktif',
            'nip' => $randomNip,
            'username' => $username,
            'email' => $username . '@lldikti.kemdikbud.go.id',
            'password' => bcrypt('Password123!'),
            'role' => 'staff',
            'is_active' => false,
        ]);

        $res = $this->post('/login', [
            'login' => $username,
            'password' => 'Password123!',
        ]);
        $res->assertSessionHasErrors('login');
        $this->assertGuest();

        // 2. Staff cannot access Administrator master routes (403)
        $staff = User::where('username', 'staff_rizky')->first();
        $this->actingAs($staff)->get('/admin/units')->assertStatus(403);
        $this->actingAs($staff)->get('/admin/logs')->assertStatus(403);

        // 3. Admin Unit cannot modify users in other units (403)
        $adminAkm = User::where('username', 'admin_akademik')->first();
        $staffKlb = User::where('username', 'staff_nurul')->first(); // POKJA-KLB

        $editRes = $this->actingAs($adminAkm)->get("/admin/users/{$staffKlb->id}/edit");
        $editRes->assertStatus(403);
    }
}
