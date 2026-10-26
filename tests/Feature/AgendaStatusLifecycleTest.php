<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AgendaStatusLifecycleTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superadmin;
    protected User $adminUnit1;
    protected User $adminUnit2;
    protected User $staffUser;
    protected Unit $unit1;
    protected Unit $unit2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::where('role', 'administrator')->firstOrFail();

        $units = Unit::active()->take(2)->get();
        $this->unit1 = $units[0];
        $this->unit2 = $units[1];

        $this->adminUnit1 = User::create([
            'nip' => '19900101' . rand(10000000, 99999999),
            'name' => 'Admin Unit 1 Test',
            'username' => 'admin.unit1.' . uniqid(),
            'email' => 'admin.unit1.' . uniqid() . '@lldikti.test',
            'password' => bcrypt('Password123!'),
            'role' => 'admin',
            'unit_id' => $this->unit1->id,
            'is_active' => true,
        ]);

        $this->adminUnit2 = User::create([
            'nip' => '19900102' . rand(10000000, 99999999),
            'name' => 'Admin Unit 2 Test',
            'username' => 'admin.unit2.' . uniqid(),
            'email' => 'admin.unit2.' . uniqid() . '@lldikti.test',
            'password' => bcrypt('Password123!'),
            'role' => 'admin',
            'unit_id' => $this->unit2->id,
            'is_active' => true,
        ]);

        $this->staffUser = User::where('role', 'staff')->firstOrFail();
    }

    /**
     * 1. Pembuatan agenda baru via API hanya mengizinkan status 'draft' atau 'scheduled'.
     */
    public function test_agenda_creation_via_api_only_accepts_draft_or_scheduled(): void
    {
        // Status 'ongoing' saat create harus ditolak 422
        $responseOngoing = $this->actingAs($this->superadmin, 'sanctum')->postJson('/api/v1/agendas', [
            'judul_rapat' => 'Rapat Ilegal Langsung Ongoing '.uniqid(),
            'jenis_rapat' => 'Rapat Koordinasi',
            'tipe_rapat' => 'online',
            'link_meeting' => 'https://zoom.us/j/999999999',
            'waktu_mulai' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'status' => 'ongoing',
        ]);

        $responseOngoing->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        // Status 'completed' saat create harus ditolak 422
        $responseCompleted = $this->actingAs($this->superadmin, 'sanctum')->postJson('/api/v1/agendas', [
            'judul_rapat' => 'Rapat Ilegal Langsung Completed '.uniqid(),
            'jenis_rapat' => 'Rapat Koordinasi',
            'tipe_rapat' => 'online',
            'link_meeting' => 'https://zoom.us/j/999999999',
            'waktu_mulai' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'status' => 'completed',
        ]);

        $responseCompleted->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        // Status 'draft' harus sukses
        $responseDraft = $this->actingAs($this->superadmin, 'sanctum')->postJson('/api/v1/agendas', [
            'judul_rapat' => 'Rapat Konsep Sah '.uniqid(),
            'jenis_rapat' => 'Rapat Koordinasi',
            'tipe_rapat' => 'online',
            'link_meeting' => 'https://zoom.us/j/999999999',
            'waktu_mulai' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'status' => 'draft',
        ]);

        $responseDraft->assertStatus(201)
            ->assertJsonPath('data.status.value', 'draft');
    }

    /**
     * 2. Normalisasi waktu ISO-8601 (UTC/Z) ke zona waktu aplikasi (Asia/Jakarta / WIB) anti-drift 7 jam.
     */
    public function test_timezone_normalization_converts_utc_iso_string_to_wib_anti_drift(): void
    {
        // Input: 2026-11-15T02:00:00Z (pukul 02:00 UTC = pukul 09:00 WIB)
        $agenda = new Agenda();
        $agenda->judul_rapat = 'Rapat Anti-Drift '.uniqid();
        $agenda->slug = 'drift-'.uniqid();
        $agenda->jenis_rapat = 'Rapat Koordinasi';
        $agenda->tipe_rapat = 'online';
        $agenda->link_meeting = 'https://zoom.us/j/123';
        $agenda->waktu_mulai = '2026-11-15T02:00:00Z';
        $agenda->waktu_selesai = '2026-11-15T05:00:00Z'; // 05:00 UTC = 12:00 WIB
        $agenda->created_by = $this->superadmin->id;
        $agenda->status = 'draft';
        $agenda->is_all_units = true;
        $agenda->save();

        $agenda->refresh();

        // Di basis data, waktu_mulai harus tersimpan pukul 09:00:00 WIB (bukan 02:00:00)
        $this->assertEquals('2026-11-15 09:00:00', $agenda->waktu_mulai->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-11-15 12:00:00', $agenda->waktu_selesai->format('Y-m-d H:i:s'));
    }

    /**
     * 3. Agenda konsep (draft) dapat diterbitkan menjadi terjadwal (scheduled) jika waktu pelaksanaan di masa depan.
     */
    public function test_draft_agenda_can_be_published_to_scheduled_with_future_date(): void
    {
        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Konsep Masa Depan '.uniqid(),
            'slug' => 'draft-'.uniqid(),
            'jenis_rapat' => 'Rapat Koordinasi',
            'tipe_rapat' => 'online',
            'link_meeting' => 'https://zoom.us/j/12345',
            'waktu_mulai' => now()->addDays(3)->setTime(9, 0),
            'waktu_selesai' => now()->addDays(3)->setTime(11, 0),
            'status' => 'draft',
            'created_by' => $this->superadmin->id,
            'is_all_units' => true,
        ]);

        $response = $this->actingAs($this->superadmin, 'sanctum')
            ->patchJson("/api/v1/agendas/{$agenda->id}/status", [
                'status' => 'scheduled',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status.value', 'scheduled');

        $agenda->refresh();
        $this->assertEquals('scheduled', $agenda->status);
    }

    /**
     * 4. Agenda konsep (draft) dengan waktu lampau DITOLAK saat akan diterbitkan ke status terjadwal.
     */
    public function test_draft_agenda_cannot_be_published_to_scheduled_if_date_is_in_the_past(): void
    {
        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Konsep Masa Lampau '.uniqid(),
            'slug' => 'draft-past-'.uniqid(),
            'jenis_rapat' => 'Rapat Koordinasi',
            'tipe_rapat' => 'online',
            'link_meeting' => 'https://zoom.us/j/12345',
            'waktu_mulai' => now()->subHours(5), // 5 jam yang lalu
            'waktu_selesai' => now()->subHours(3),
            'status' => 'draft',
            'created_by' => $this->superadmin->id,
            'is_all_units' => true,
        ]);

        $response = $this->actingAs($this->superadmin, 'sanctum')
            ->patchJson("/api/v1/agendas/{$agenda->id}/status", [
                'status' => 'scheduled',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonFragment([
                'success' => false,
            ]);

        $this->assertStringContainsString('masa lampau', $response->json('message'));

        $agenda->refresh();
        $this->assertEquals('draft', $agenda->status);
    }

    /**
     * 5. Transisi ilegal dari draft langsung ke ongoing atau completed harus ditolak.
     */
    public function test_draft_agenda_transition_to_ongoing_or_completed_is_rejected(): void
    {
        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Draft Transisi Ilegal '.uniqid(),
            'slug' => 'draft-bad-'.uniqid(),
            'jenis_rapat' => 'Rapat Koordinasi',
            'tipe_rapat' => 'online',
            'link_meeting' => 'https://zoom.us/j/12345',
            'waktu_mulai' => now()->addDays(2),
            'status' => 'draft',
            'created_by' => $this->superadmin->id,
            'is_all_units' => true,
        ]);

        // draft -> ongoing: ditolak
        $resOngoing = $this->actingAs($this->superadmin, 'sanctum')
            ->patchJson("/api/v1/agendas/{$agenda->id}/status", ['status' => 'ongoing']);
        $resOngoing->assertStatus(422);

        // draft -> completed: ditolak
        $resCompleted = $this->actingAs($this->superadmin, 'sanctum')
            ->patchJson("/api/v1/agendas/{$agenda->id}/status", ['status' => 'completed']);
        $resCompleted->assertStatus(422);
    }

    /**
     * 6. Agenda scheduled TIDAK DAPAT dimulai (ongoing) jika lebih awal dari 15 menit sebelum waktu mulai.
     */
    public function test_scheduled_agenda_cannot_be_started_earlier_than_15_minutes_before_meeting(): void
    {
        // Rapat dijadwalkan 60 menit dari sekarang (di luar jendela 15 menit)
        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Terlalu Cepat Dimulai '.uniqid(),
            'slug' => 'sched-early-'.uniqid(),
            'jenis_rapat' => 'Rapat Koordinasi',
            'tipe_rapat' => 'online',
            'link_meeting' => 'https://zoom.us/j/12345',
            'waktu_mulai' => now()->addMinutes(60),
            'status' => 'scheduled',
            'created_by' => $this->superadmin->id,
            'is_all_units' => true,
        ]);

        $this->assertFalse($agenda->can_start_now);

        $response = $this->actingAs($this->superadmin, 'sanctum')
            ->patchJson("/api/v1/agendas/{$agenda->id}/status", ['status' => 'ongoing']);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertStringContainsString('15 menit sebelum', $response->json('message'));

        $agenda->refresh();
        $this->assertEquals('scheduled', $agenda->status);
    }

    /**
     * 7. Agenda scheduled DAPAT dimulai (ongoing) jika berada dalam jendela 15 menit sebelum waktu mulai.
     */
    public function test_scheduled_agenda_can_be_started_within_15_minute_window(): void
    {
        // Rapat dijadwalkan 10 menit dari sekarang (masuk jendela 15 menit)
        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Tepat Jendela Waktu '.uniqid(),
            'slug' => 'sched-window-'.uniqid(),
            'jenis_rapat' => 'Rapat Koordinasi',
            'tipe_rapat' => 'online',
            'link_meeting' => 'https://zoom.us/j/12345',
            'waktu_mulai' => now()->addMinutes(10),
            'status' => 'scheduled',
            'created_by' => $this->superadmin->id,
            'is_all_units' => true,
        ]);

        $this->assertTrue($agenda->can_start_now);

        $response = $this->actingAs($this->superadmin, 'sanctum')
            ->patchJson("/api/v1/agendas/{$agenda->id}/status", ['status' => 'ongoing']);

        $response->assertOk()
            ->assertJsonPath('data.status.value', 'ongoing');

        $agenda->refresh();
        $this->assertEquals('ongoing', $agenda->status);
    }

    /**
     * 8. Transisi ongoing -> scheduled (undo) diizinkan HANYA jika belum ada peserta yang melakukan presensi.
     */
    public function test_ongoing_agenda_can_be_reverted_to_scheduled_if_no_attendances(): void
    {
        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Ongoing Belum Ada Presensi '.uniqid(),
            'slug' => 'ongoing-clean-'.uniqid(),
            'jenis_rapat' => 'Rapat Koordinasi',
            'tipe_rapat' => 'online',
            'link_meeting' => 'https://zoom.us/j/12345',
            'waktu_mulai' => now()->addMinutes(5),
            'status' => 'ongoing',
            'created_by' => $this->superadmin->id,
            'is_all_units' => true,
        ]);

        $response = $this->actingAs($this->superadmin, 'sanctum')
            ->patchJson("/api/v1/agendas/{$agenda->id}/status", ['status' => 'scheduled']);

        $response->assertOk()
            ->assertJsonPath('data.status.value', 'scheduled');

        $agenda->refresh();
        $this->assertEquals('scheduled', $agenda->status);
    }

    /**
     * 9. Transisi ongoing -> scheduled DITOLAK keras jika sudah ada catatan presensi kehadiran ASN.
     */
    public function test_ongoing_agenda_cannot_be_reverted_to_scheduled_if_attendances_exist(): void
    {
        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Ongoing Ada Presensi '.uniqid(),
            'slug' => 'ongoing-att-'.uniqid(),
            'jenis_rapat' => 'Rapat Koordinasi',
            'tipe_rapat' => 'online',
            'link_meeting' => 'https://zoom.us/j/12345',
            'waktu_mulai' => now()->addMinutes(5),
            'status' => 'ongoing',
            'created_by' => $this->superadmin->id,
            'is_all_units' => true,
        ]);

        // Catat satu presensi
        Attendance::create([
            'agenda_id' => $agenda->id,
            'user_id' => $this->staffUser->id,
            'selfie_path' => 'attendances/test_selfie.jpg',
            'signature_path' => 'attendances/test_signature.png',
            'signed_at' => now(),
        ]);

        $response = $this->actingAs($this->superadmin, 'sanctum')
            ->patchJson("/api/v1/agendas/{$agenda->id}/status", ['status' => 'scheduled']);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertStringContainsString('integritas Berita Acara', $response->json('message'));

        $agenda->refresh();
        $this->assertEquals('ongoing', $agenda->status);
    }

    /**
     * 10. Status completed bersifat final permanen dan menolak segala bentuk perubahan status.
     */
    public function test_completed_agenda_is_strictly_final_and_rejects_any_transition(): void
    {
        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Final Completed '.uniqid(),
            'slug' => 'completed-'.uniqid(),
            'jenis_rapat' => 'Rapat Koordinasi',
            'tipe_rapat' => 'online',
            'link_meeting' => 'https://zoom.us/j/12345',
            'waktu_mulai' => now()->subHours(4),
            'waktu_selesai' => now()->subHours(2),
            'status' => 'completed',
            'created_by' => $this->superadmin->id,
            'is_all_units' => true,
        ]);

        $this->assertEquals([], $agenda->allowed_transitions);

        // Coba ubah ke scheduled
        $resSched = $this->actingAs($this->superadmin, 'sanctum')
            ->patchJson("/api/v1/agendas/{$agenda->id}/status", ['status' => 'scheduled']);
        $resSched->assertStatus(422);

        // Coba ubah ke draft
        $resDraft = $this->actingAs($this->superadmin, 'sanctum')
            ->patchJson("/api/v1/agendas/{$agenda->id}/status", ['status' => 'draft']);
        $resDraft->assertStatus(422);

        // Coba ubah ke ongoing
        $resOngoing = $this->actingAs($this->superadmin, 'sanctum')
            ->patchJson("/api/v1/agendas/{$agenda->id}/status", ['status' => 'ongoing']);
        $resOngoing->assertStatus(422);

        $agenda->refresh();
        $this->assertEquals('completed', $agenda->status);
    }

    /**
     * 11. Status cancelled dapat dikembalikan ke draft oleh Administrator dan Admin Unit pembuatnya.
     */
    public function test_cancelled_agenda_can_be_restored_to_draft_by_superadmin_and_admin_unit_creator(): void
    {
        // Agenda dibuat oleh Admin Unit 1
        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Batal Unit 1 '.uniqid(),
            'slug' => 'cancel-unit1-'.uniqid(),
            'jenis_rapat' => 'Rapat Koordinasi',
            'tipe_rapat' => 'online',
            'link_meeting' => 'https://zoom.us/j/12345',
            'waktu_mulai' => now()->addDays(3),
            'status' => 'cancelled',
            'created_by' => $this->adminUnit1->id,
            'is_all_units' => false,
        ]);
        $agenda->units()->sync([$this->unit1->id]);

        // Admin Unit 1 (penyelenggara) berhasil membuka kembali ke draft
        $responseUnit = $this->actingAs($this->adminUnit1, 'sanctum')
            ->patchJson("/api/v1/agendas/{$agenda->id}/status", ['status' => 'draft']);

        $responseUnit->assertOk()
            ->assertJsonPath('data.status.value', 'draft');

        // Batalkan lagi
        $agenda->update(['status' => 'cancelled']);

        // Superadmin juga berhasil membuka kembali ke draft
        $responseAdmin = $this->actingAs($this->superadmin, 'sanctum')
            ->patchJson("/api/v1/agendas/{$agenda->id}/status", ['status' => 'draft']);

        $responseAdmin->assertOk()
            ->assertJsonPath('data.status.value', 'draft');
    }

    /**
     * 12. Admin Unit dari unit kerja lain DITOLAK (403) saat mencoba mengelola status agenda batal milik unit lain.
     */
    public function test_cancelled_agenda_cannot_be_restored_by_unauthorized_admin_unit(): void
    {
        // Agenda milik Unit 1
        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Khusus Unit 1 '.uniqid(),
            'slug' => 'cancel-unit1-sec-'.uniqid(),
            'jenis_rapat' => 'Rapat Koordinasi',
            'tipe_rapat' => 'online',
            'link_meeting' => 'https://zoom.us/j/12345',
            'waktu_mulai' => now()->addDays(3),
            'status' => 'cancelled',
            'created_by' => $this->adminUnit1->id,
            'is_all_units' => false,
        ]);
        $agenda->units()->sync([$this->unit1->id]);

        // Admin Unit 2 mencoba mengubah status -> 403 Forbidden
        $response = $this->actingAs($this->adminUnit2, 'sanctum')
            ->patchJson("/api/v1/agendas/{$agenda->id}/status", ['status' => 'draft']);

        $response->assertStatus(403);
    }

    /**
     * 13. AttendancePolicy dan StoreAttendanceRequest menolak presensi jika waktu sudah melewati effective_end_at.
     */
    public function test_attendance_policy_and_request_reject_checkin_when_effective_end_at_passed(): void
    {
        // Rapat ongoing namun waktu selesai telah lewat 20 menit (melewati toleransi 15 menit)
        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Ongoing Telat Presensi '.uniqid(),
            'slug' => 'ongoing-late-'.uniqid(),
            'jenis_rapat' => 'Rapat Koordinasi',
            'tipe_rapat' => 'online',
            'link_meeting' => 'https://zoom.us/j/12345',
            'waktu_mulai' => now()->subHours(2),
            'waktu_selesai' => now()->subMinutes(20), // Selesai 20 menit lalu, grace 15 menit = sudah lewat 5 menit
            'status' => 'ongoing',
            'created_by' => $this->superadmin->id,
            'is_all_units' => true,
        ]);

        $this->assertTrue(now()->gt($agenda->effective_end_at));

        $fakeSelfie = UploadedFile::fake()->image('selfie.jpg', 300, 400);
        $fakeSig = UploadedFile::fake()->image('signature.png', 200, 100);

        $response = $this->actingAs($this->staffUser, 'sanctum')
            ->post("/api/v1/agendas/{$agenda->id}/attendances", [
                'selfie_file' => $fakeSelfie,
                'signature_file' => $fakeSig,
            ]);

        $response->assertStatus(403);
        $this->assertStringContainsString('Batas waktu pengisian presensi', $response->json('message'));
    }

    /**
     * 14. Scheduler Command 'agendas:sync-status' menutup otomatis rapat ongoing yang kedaluwarsa.
     */
    public function test_sync_agenda_status_command_auto_closes_expired_ongoing_meetings(): void
    {
        $expiredAgenda = Agenda::create([
            'judul_rapat' => 'Rapat Kedaluwarsa Auto-Close '.uniqid(),
            'slug' => 'expired-'.uniqid(),
            'jenis_rapat' => 'Rapat Koordinasi',
            'tipe_rapat' => 'online',
            'link_meeting' => 'https://zoom.us/j/12345',
            'waktu_mulai' => now()->subHours(3),
            'waktu_selesai' => now()->subMinutes(30),
            'status' => 'ongoing',
            'created_by' => $this->superadmin->id,
            'is_all_units' => true,
        ]);

        $exitCode = Artisan::call('agendas:sync-status');
        $this->assertEquals(0, $exitCode);

        $expiredAgenda->refresh();
        $this->assertEquals('completed', $expiredAgenda->status);

        $this->assertDatabaseHas('activity_logs', [
            'activity_type' => 'AUTO_CLOSE_AGENDA',
            'target_id' => $expiredAgenda->id,
        ]);
    }

    /**
     * 15. Scheduler Command 'agendas:sync-status' TIDAK membatalkan agenda 'scheduled' yang terlewat (Keputusan B).
     */
    public function test_sync_agenda_status_command_does_not_cancel_overdue_scheduled_meetings(): void
    {
        $overdueScheduled = Agenda::create([
            'judul_rapat' => 'Rapat Terjadwal Terlewat '.uniqid(),
            'slug' => 'sched-overdue-'.uniqid(),
            'jenis_rapat' => 'Rapat Koordinasi',
            'tipe_rapat' => 'online',
            'link_meeting' => 'https://zoom.us/j/12345',
            'waktu_mulai' => now()->subHours(5),
            'waktu_selesai' => now()->subHours(2),
            'status' => 'scheduled',
            'created_by' => $this->superadmin->id,
            'is_all_units' => true,
        ]);

        $this->assertTrue($overdueScheduled->is_overdue);

        Artisan::call('agendas:sync-status');

        $overdueScheduled->refresh();
        // Tetap 'scheduled', tidak diubah ke cancelled atau completed
        $this->assertEquals('scheduled', $overdueScheduled->status);
        $this->assertTrue($overdueScheduled->is_overdue);
    }
}
