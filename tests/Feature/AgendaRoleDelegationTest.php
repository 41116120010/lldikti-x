<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AgendaRoleDelegationTest extends TestCase
{
    public function test_default_role_fallback_to_creator(): void
    {
        $admin = User::where('role', 'administrator')->first();

        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Fallback Peran ' . uniqid(),
            'slug' => 'RPT-' . uniqid(),
            'jenis_rapat' => 'pleno',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Sidang 1',
            'waktu_mulai' => now()->addDay(),
            'waktu_selesai' => now()->addDay()->addHours(2),
            'status' => 'scheduled',
            'is_all_units' => true,
            'created_by' => $admin->id,
            'pimpinan_id' => null,
            'notulis_id' => null,
        ]);

        $this->assertEquals($admin->id, $agenda->effective_pimpinan->id);
        $this->assertEquals($admin->id, $agenda->effective_notulis->id);
        $this->assertEquals($admin->name, $agenda->nama_pimpinan);
        $this->assertEquals($admin->name, $agenda->nama_notulis);
    }

    public function test_can_create_agenda_with_custom_pimpinan_and_notulis(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $staffPimpinan = User::where('role', 'staff')->first();
        $staffNotulis = User::where('role', 'staff')->skip(1)->first();

        $title = 'Rapat Penugasan Khusus ' . uniqid();

        $response = $this->actingAs($superadmin)->post('/admin/agendas', [
            'judul_rapat' => $title,
            'jenis_rapat' => 'pleno',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Senat',
            'waktu_mulai' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'waktu_selesai' => now()->addDays(3)->addHours(2)->format('Y-m-d H:i:s'),
            'is_all_units' => true,
            'pimpinan_id' => $staffPimpinan->id,
            'notulis_id' => $staffNotulis->id,
            'status' => 'scheduled',
        ]);

        $agenda = Agenda::where('judul_rapat', $title)->first();
        $this->assertNotNull($agenda);
        $this->assertEquals($staffPimpinan->id, $agenda->pimpinan_id);
        $this->assertEquals($staffNotulis->id, $agenda->notulis_id);
        $this->assertEquals($staffPimpinan->name, $agenda->nama_pimpinan);
        $this->assertEquals($staffNotulis->name, $agenda->nama_notulis);
    }

    public function test_dynamic_role_assignment_via_update_roles_endpoint(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $staffPimpinan = User::where('role', 'staff')->first();
        $staffNotulis = User::where('role', 'staff')->skip(1)->first();

        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Dinamis Peran ' . uniqid(),
            'slug' => 'RPT-' . uniqid(),
            'jenis_rapat' => 'pleno',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Rapat 2',
            'waktu_mulai' => now(),
            'waktu_selesai' => now()->addHours(2),
            'status' => 'ongoing',
            'is_all_units' => true,
            'created_by' => $superadmin->id,
        ]);

        // Superadmin updates roles during meeting
        $response = $this->actingAs($superadmin)->patch("/admin/agendas/{$agenda->id}/roles", [
            'pimpinan_id' => $staffPimpinan->id,
            'notulis_id' => $staffNotulis->id,
        ]);

        $response->assertSessionHas('success');
        $agenda->refresh();

        $this->assertEquals($staffPimpinan->id, $agenda->pimpinan_id);
        $this->assertEquals($staffNotulis->id, $agenda->notulis_id);

        $this->assertDatabaseHas('activity_logs', [
            'activity_type' => 'UPDATE_AGENDA_ROLES',
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_staff_notulis_has_minutes_access_ONLY_during_ongoing_meeting(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $staffNotulis = User::where('role', 'staff')->first();

        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Time-Bound Notulis ' . uniqid(),
            'slug' => 'RPT-' . uniqid(),
            'jenis_rapat' => 'pleno',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Pimpinan',
            'waktu_mulai' => now(),
            'status' => 'ongoing', // Currently ONGOING
            'is_all_units' => true,
            'created_by' => $superadmin->id,
            'notulis_id' => $staffNotulis->id,
        ]);

        // 1. When status is ONGOING -> Staff Notulis CAN view and submit notulen form
        $this->actingAs($staffNotulis)
            ->get("/admin/agendas/{$agenda->id}/notulen")
            ->assertStatus(200)
            ->assertSee('Notulensi / Catatan Jalannya Rapat');

        $response = $this->actingAs($staffNotulis)
            ->put("/admin/agendas/{$agenda->id}/notulen", [
                'notulensi' => '<p>Pembahasan progres pekerjaan unit berjalan lancar.</p>',
                'kesimpulan' => '<p>RTL: Laporan diserahkan pekan depan.</p>',
            ]);

        $response->assertRedirect("/agendas/{$agenda->id}");
        $response->assertSessionHas('success');

        $agenda->refresh();
        $this->assertStringContainsString('Pembahasan progres', $agenda->notulensi);

        // 2. Now change status to COMPLETED
        $agenda->update(['status' => 'completed']);

        // Staff Notulis MUST BE FORBIDDEN (403) once meeting is completed
        $this->actingAs($staffNotulis)
            ->get("/admin/agendas/{$agenda->id}/notulen")
            ->assertStatus(403);

        $this->actingAs($staffNotulis)
            ->put("/admin/agendas/{$agenda->id}/notulen", [
                'notulensi' => 'Perubahan ilegal setelah rapat selesai',
            ])
            ->assertStatus(403);

        // 3. When status is SCHEDULED -> Staff Notulis MUST ALSO BE FORBIDDEN (403)
        $agenda->update(['status' => 'scheduled']);
        $this->actingAs($staffNotulis)
            ->get("/admin/agendas/{$agenda->id}/notulen")
            ->assertStatus(403);
    }

    public function test_automatic_digital_signature_rendering_for_pimpinan_and_notulis(): void
    {
        Storage::fake('public');

        $superadmin = User::where('role', 'administrator')->first();
        $pimpinanUser = User::where('role', 'staff')->first();
        $notulisUser = User::where('role', 'staff')->skip(1)->first();

        // Create dummy signature images in storage
        $pimpinanSigPath = 'signatures/pimpinan_test.png';
        $notulisSigPath = 'signatures/notulis_test.png';
        Storage::disk('public')->put($pimpinanSigPath, 'fake-png-data-pimpinan');
        Storage::disk('public')->put($notulisSigPath, 'fake-png-data-notulis');

        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Uji Tanda Tangan ' . uniqid(),
            'slug' => 'RPT-' . uniqid(),
            'jenis_rapat' => 'pleno',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Rapat Utama',
            'waktu_mulai' => now(),
            'status' => 'ongoing',
            'is_all_units' => true,
            'created_by' => $superadmin->id,
            'pimpinan_id' => $pimpinanUser->id,
            'notulis_id' => $notulisUser->id,
            'notulensi' => 'Poin pembahasan terverifikasi.',
            'kesimpulan' => 'Disepakati bersama.',
        ]);

        // Pimpinan attends and signs
        Attendance::create([
            'agenda_id' => $agenda->id,
            'user_id' => $pimpinanUser->id,
            'selfie_path' => 'selfies/test.jpg',
            'signature_path' => $pimpinanSigPath,
            'signed_at' => now(),
        ]);

        // Notulis attends and signs
        Attendance::create([
            'agenda_id' => $agenda->id,
            'user_id' => $notulisUser->id,
            'selfie_path' => 'selfies/test.jpg',
            'signature_path' => $notulisSigPath,
            'signed_at' => now(),
        ]);

        $agenda->refresh();

        // Check model accessors
        $this->assertNotNull($agenda->pimpinan_attendance);
        $this->assertEquals($pimpinanSigPath, $agenda->pimpinan_attendance->signature_path);
        $this->assertNotNull($agenda->notulis_attendance);
        $this->assertEquals($notulisSigPath, $agenda->notulis_attendance->signature_path);

        // 1. Check Web Detail Page
        $this->actingAs($superadmin)
            ->get("/admin/agendas/{$agenda->id}")
            ->assertStatus(200)
            ->assertSee($agenda->nama_pimpinan)
            ->assertSee($agenda->nama_notulis)
            ->assertSee($pimpinanSigPath);

        // 2. Check Official Report Page
        $this->actingAs($superadmin)
            ->get("/admin/reports/{$agenda->id}")
            ->assertStatus(200)
            ->assertSee($agenda->nama_pimpinan)
            ->assertSee($agenda->nama_notulis)
            ->assertSee($pimpinanSigPath);

        // 3. Check PDF Export
        $pdfResponse = $this->actingAs($superadmin)
            ->get("/admin/reports/{$agenda->id}/export/pdf");
        $pdfResponse->assertStatus(200);
        $pdfContent = $pdfResponse->getContent();
        $this->assertStringContainsString($agenda->nama_pimpinan, $pdfContent);
        $this->assertStringContainsString($agenda->nama_notulis, $pdfContent);
        $this->assertStringContainsString('data:image/png;base64,', $pdfContent);

        // 4. Check Word Export
        $wordResponse = $this->actingAs($superadmin)
            ->get("/admin/reports/{$agenda->id}/export/word");
        $wordResponse->assertStatus(200);
        $wordContent = $wordResponse->getContent();
        $this->assertStringContainsString($agenda->nama_pimpinan, $wordContent);
        $this->assertStringContainsString($agenda->nama_notulis, $wordContent);
        $this->assertStringContainsString('data:image/png;base64,', $wordContent);
    }

    /**
     * Test Permasalahan 32: Admin unit lain yang BUKAN pembuat rapat dan TIDAK ditunjuk
     * DILARANG KERAS mengelola status, mengubah data/peran, atau mengelola notulensi rapat universal (is_all_units = true).
     */
    public function test_participating_admin_unit_cannot_manage_universal_agenda(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $adminAkademik = User::where('username', 'admin_akademik')->first();

        // 1. Superadmin creates a universal plenary meeting in 'scheduled' status
        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Koordinasi Pleno ' . uniqid(),
            'slug' => 'RPT-' . uniqid(),
            'jenis_rapat' => 'pleno',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Auditorium Utama',
            'waktu_mulai' => now()->addDays(2),
            'waktu_selesai' => now()->addDays(2)->addHours(2),
            'status' => 'scheduled',
            'is_all_units' => true,
            'created_by' => $superadmin->id,
        ]);

        // 2. Admin Akademik (participating unit) attempts mutating actions -> MUST BE 403 FORBIDDEN
        // A. Mutate status (e.g. attempt to start meeting)
        $this->actingAs($adminAkademik)
            ->patch("/admin/agendas/{$agenda->id}/status", ['status' => 'ongoing'])
            ->assertStatus(403);

        // B. Access and submit notulen form
        $this->actingAs($adminAkademik)
            ->get("/admin/agendas/{$agenda->id}/notulen")
            ->assertStatus(403);

        $this->actingAs($adminAkademik)
            ->put("/admin/agendas/{$agenda->id}/notulen", [
                'notulensi' => 'Percobaan pembobolan notulensi oleh unit partisipan',
            ])
            ->assertStatus(403);

        // C. Update agenda details or dynamically assign roles
        $this->actingAs($adminAkademik)
            ->patch("/admin/agendas/{$agenda->id}/roles", [
                'pimpinan_id' => $adminAkademik->id,
            ])
            ->assertStatus(403);

        $this->actingAs($adminAkademik)
            ->put("/admin/agendas/{$agenda->id}", [
                'judul_rapat' => 'Judul Dibajak',
                'jenis_rapat' => 'pleno',
                'tipe_rapat' => 'offline',
                'waktu_mulai' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'is_all_units' => true,
            ])
            ->assertStatus(403);

        // D. Delete agenda
        $this->actingAs($adminAkademik)
            ->delete("/admin/agendas/{$agenda->id}")
            ->assertStatus(403);

        // 3. UI Verification: Admin Akademik can view index and show, but without mutation buttons
        $indexResponse = $this->actingAs($adminAkademik)->get('/admin/agendas');
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Unit Partisipan');

        $showResponse = $this->actingAs($adminAkademik)->get("/admin/agendas/{$agenda->id}");
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Mode Pemantauan (Unit Partisipan)');
        $showResponse->assertDontSee('Buka Sesi Presensi');
        $showResponse->assertDontSee('Tugaskan Peran');
        $showResponse->assertDontSee('Edit Agenda');
    }

    /**
     * Test Permasalahan 32: Admin unit yang ditunjuk sebagai pimpinan atau notulis saat rapat berlangsung (ongoing)
     * DIBERIKAN hak kelola status dan notulensi khusus selama sesi berlangsung.
     */
    public function test_appointed_admin_unit_can_manage_agenda_during_ongoing_session(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $adminAkademik = User::where('username', 'admin_akademik')->first();
        $adminKelembagaan = User::where('username', 'admin_kelembagaan')->first();

        // 1. Universal meeting currently ONGOING, Admin Akademik is appointed as Pimpinan
        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Pleno Dipimpin Admin Unit ' . uniqid(),
            'slug' => 'RPT-' . uniqid(),
            'jenis_rapat' => 'pleno',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Sidang Utama',
            'waktu_mulai' => now(),
            'status' => 'ongoing',
            'is_all_units' => true,
            'created_by' => $superadmin->id,
            'pimpinan_id' => $adminAkademik->id,
            'notulis_id' => null,
        ]);

        // 2. Admin Akademik (Appointed Pimpinan during ONGOING) CAN manage minutes, roles, and status
        $this->actingAs($adminAkademik)
            ->get("/admin/agendas/{$agenda->id}/notulen")
            ->assertStatus(200);

        $this->actingAs($adminAkademik)
            ->put("/admin/agendas/{$agenda->id}/notulen", [
                'notulensi' => '<p>Catatan jalannya rapat oleh Pimpinan Rapat Terpilih.</p>',
            ])
            ->assertSessionHas('success');

        $this->actingAs($adminAkademik)
            ->patch("/admin/agendas/{$agenda->id}/roles", [
                'pimpinan_id' => $adminAkademik->id,
                'notulis_id' => $adminKelembagaan->id,
            ])
            ->assertSessionHas('success');

        // 3. Admin Kelembagaan is now NOTULIS during ONGOING -> Can also manage minutes
        $this->actingAs($adminKelembagaan)
            ->put("/admin/agendas/{$agenda->id}/notulen", [
                'kesimpulan' => '<p>Tindak lanjut disahkan oleh Notulis Terpilih.</p>',
            ])
            ->assertSessionHas('success');

        // 4. Admin Akademik (Pimpinan) completes the meeting
        $this->actingAs($adminAkademik)
            ->patch("/admin/agendas/{$agenda->id}/status", [
                'status' => 'completed',
            ])
            ->assertSessionHas('success');

        $agenda->refresh();
        $this->assertEquals('completed', $agenda->status);

        // 5. Once COMPLETED, Admin Akademik's mutation rights lock back to read-only archival
        $this->actingAs($adminAkademik)
            ->patch("/admin/agendas/{$agenda->id}/status", [
                'status' => 'scheduled',
            ])
            ->assertStatus(403);

        $this->actingAs($adminAkademik)
            ->patch("/admin/agendas/{$agenda->id}/roles", [
                'pimpinan_id' => null,
            ])
            ->assertStatus(403);
    }
}
