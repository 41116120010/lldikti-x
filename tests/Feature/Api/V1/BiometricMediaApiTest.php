<?php

namespace Tests\Feature\Api\V1;

use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BiometricMediaApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_attendee_can_stream_own_selfie_via_bearer_token(): void
    {
        Storage::fake('public');

        $attendance = Attendance::first();
        $this->assertNotNull($attendance);

        $attendee = $attendance->user;
        $this->assertNotNull($attendee);

        $fakePath = "attendances/{$attendance->agenda_id}/selfies/test_selfie.jpg";
        Storage::disk('public')->put($fakePath, 'fake-selfie-binary-content');
        $attendance->update(['selfie_path' => $fakePath]);

        $response = $this->actingAs($attendee, 'sanctum')
            ->get("/api/v1/attendances/{$attendance->id}/selfie");

        $response->assertOk()
            ->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private');

        $this->assertEquals('fake-selfie-binary-content', $response->streamedContent());
    }

    public function test_attendee_can_stream_own_signature_via_bearer_token(): void
    {
        Storage::fake('public');

        $attendance = Attendance::first();
        $this->assertNotNull($attendance);

        $attendee = $attendance->user;
        $this->assertNotNull($attendee);

        $fakePath = "attendances/{$attendance->agenda_id}/signatures/test_sig.png";
        Storage::disk('public')->put($fakePath, 'fake-signature-binary-content');
        $attendance->update(['signature_path' => $fakePath]);

        $response = $this->actingAs($attendee, 'sanctum')
            ->get("/api/v1/attendances/{$attendance->id}/signature");

        $response->assertOk()
            ->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private');

        $this->assertEquals('fake-signature-binary-content', $response->streamedContent());
    }

    public function test_unauthenticated_request_cannot_stream_biometric_media(): void
    {
        $attendance = Attendance::first();
        $this->assertNotNull($attendance);

        $response = $this->getJson("/api/v1/attendances/{$attendance->id}/selfie");

        $response->assertStatus(401);
    }

    public function test_unauthorized_staff_cannot_stream_another_attendee_biometrics(): void
    {
        $attendance = Attendance::first();
        $this->assertNotNull($attendance);

        // Find a different staff user
        $otherStaff = User::where('role', 'staff')
            ->where('id', '!=', $attendance->user_id)
            ->first();
        $this->assertNotNull($otherStaff);

        $response = $this->actingAs($otherStaff, 'sanctum')
            ->getJson("/api/v1/attendances/{$attendance->id}/selfie");

        $response->assertStatus(403);
    }

    public function test_administrator_can_stream_any_attendee_biometrics_for_audit(): void
    {
        Storage::fake('public');

        $admin = User::where('role', 'administrator')->first();
        $attendance = Attendance::where('user_id', '!=', $admin->id)->first();
        $this->assertNotNull($admin);
        $this->assertNotNull($attendance);

        $fakePath = "attendances/{$attendance->agenda_id}/selfies/test_admin_selfie.jpg";
        Storage::disk('public')->put($fakePath, 'admin-audit-selfie-data');
        $attendance->update(['selfie_path' => $fakePath]);

        $response = $this->actingAs($admin, 'sanctum')
            ->get("/api/v1/attendances/{$attendance->id}/selfie");

        $response->assertOk();
        $this->assertEquals('admin-audit-selfie-data', $response->streamedContent());
    }

    public function test_api_checkin_stores_biometrics_on_private_local_disk(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $staff = User::where('role', 'staff')->firstOrFail();
        $agenda = Agenda::create([
            'created_by' => $staff->id,
            'judul_rapat' => 'Rapat API Biometric Local ' . uniqid(),
            'slug' => 'rapat-api-biometric-local-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Sidang 1',
            'waktu_mulai' => now()->subMinutes(15),
            'waktu_selesai' => now()->addHours(2),
            'is_all_units' => true,
            'status' => 'ongoing',
        ]);

        $response = $this->actingAs($staff, 'sanctum')
            ->post("/api/v1/agendas/{$agenda->id}/attendances", [
                'selfie_file' => UploadedFile::fake()->image('selfie.jpg', 400, 400),
                'signature_file' => UploadedFile::fake()->image('signature.png', 200, 100),
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertStatus(201)->assertJsonPath('success', true);

        $attendance = Attendance::where('agenda_id', $agenda->id)
            ->where('user_id', $staff->id)
            ->firstOrFail();

        Storage::disk('local')->assertExists($attendance->selfie_path);
        Storage::disk('local')->assertExists($attendance->signature_path);
        Storage::disk('public')->assertMissing($attendance->selfie_path);
        Storage::disk('public')->assertMissing($attendance->signature_path);
    }

    public function test_attendee_can_stream_own_biometrics_from_private_local_disk_via_api(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $attendance = Attendance::firstOrFail();
        $attendee = $attendance->user;

        $selfiePath = "attendances/{$attendance->agenda_id}/selfies/local_selfie.jpg";
        $sigPath = "attendances/{$attendance->agenda_id}/signatures/local_sig.png";
        Storage::disk('local')->put($selfiePath, 'private-local-selfie');
        Storage::disk('local')->put($sigPath, 'private-local-signature');
        $attendance->update([
            'selfie_path' => $selfiePath,
            'signature_path' => $sigPath,
        ]);

        $selfieRes = $this->actingAs($attendee, 'sanctum')
            ->get("/api/v1/attendances/{$attendance->id}/selfie");
        $selfieRes->assertOk();
        $this->assertEquals('private-local-selfie', $selfieRes->streamedContent());

        $sigRes = $this->actingAs($attendee, 'sanctum')
            ->get("/api/v1/attendances/{$attendance->id}/signature");
        $sigRes->assertOk();
        $this->assertEquals('private-local-signature', $sigRes->streamedContent());
    }

    public function test_staff_pimpinan_can_view_and_stream_attendee_biometrics_while_staff_notulis_cannot(): void
    {
        Storage::fake('local');

        $units = Unit::take(2)->get();
        $this->assertCount(2, $units);
        [$unitA, $unitB] = [$units[0], $units[1]];

        $creatorAdmin = User::factory()->create([
            'role' => 'admin',
            'unit_id' => $unitA->id,
            'username' => 'creator_bio_' . uniqid(),
            'nip' => '19880101' . str_pad((string) mt_rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT),
        ]);
        $pimpinanStaff = User::factory()->create([
            'role' => 'staff',
            'unit_id' => $unitB->id,
            'username' => 'pimp_bio_' . uniqid(),
            'nip' => '19890101' . str_pad((string) mt_rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT),
        ]);
        $notulisStaff = User::factory()->create([
            'role' => 'staff',
            'unit_id' => $unitB->id,
            'username' => 'notu_bio_' . uniqid(),
            'nip' => '19900101' . str_pad((string) mt_rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT),
        ]);
        $attendeeStaff = User::factory()->create([
            'role' => 'staff',
            'unit_id' => $unitA->id,
            'username' => 'peserta_bio_' . uniqid(),
            'nip' => '19910101' . str_pad((string) mt_rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT),
        ]);

        $agenda = Agenda::create([
            'created_by' => $creatorAdmin->id,
            'pimpinan_id' => $pimpinanStaff->id,
            'notulis_id' => $notulisStaff->id,
            'judul_rapat' => 'Rapat Isolasi Biometrik ' . uniqid(),
            'slug' => 'rapat-isolasi-bio-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Rapat A',
            'waktu_mulai' => now()->subHour(),
            'waktu_selesai' => now()->addHours(2),
            'target_type' => 'selected_users',
            'is_all_units' => false,
            'status' => 'ongoing',
        ]);
        $agenda->invitedUsers()->sync([$pimpinanStaff->id, $notulisStaff->id, $attendeeStaff->id]);

        $selfiePath = "attendances/{$agenda->id}/selfies/bio_selfie.jpg";
        $sigPath = "attendances/{$agenda->id}/signatures/bio_sig.png";
        Storage::disk('local')->put($selfiePath, 'attendee-selfie-bytes');
        Storage::disk('local')->put($sigPath, 'attendee-sig-bytes');

        $attendance = Attendance::create([
            'agenda_id' => $agenda->id,
            'user_id' => $attendeeStaff->id,
            'selfie_path' => $selfiePath,
            'signature_path' => $sigPath,
            'signed_at' => now()->subMinutes(10),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        // 1. Pimpinan (role: staff) receives can_view_biometrics=true + URLs and can stream 200 (API & Web)
        $pimpinanList = $this->actingAs($pimpinanStaff, 'sanctum')
            ->getJson("/api/v1/agendas/{$agenda->id}/attendances");
        $pimpinanList->assertOk()
            ->assertJsonPath('data.0.can_view_biometrics', true)
            ->assertJsonPath('data.0.has_selfie', true)
            ->assertJsonPath('data.0.has_signature', true);
        $this->assertNotNull($pimpinanList->json('data.0.selfie_url'));
        $this->assertNotNull($pimpinanList->json('data.0.signature_url'));

        $this->actingAs($pimpinanStaff, 'sanctum')
            ->get("/api/v1/attendances/{$attendance->id}/selfie")
            ->assertOk();
        $this->actingAs($pimpinanStaff, 'sanctum')
            ->get("/api/v1/attendances/{$attendance->id}/signature")
            ->assertOk();
        $this->actingAs($pimpinanStaff)
            ->get(route('attendances.selfie', $attendance))
            ->assertOk();

        // 2. Related Admin Unit (organizer unitA) can stream 200
        $this->actingAs($creatorAdmin, 'sanctum')
            ->get("/api/v1/attendances/{$attendance->id}/selfie")
            ->assertOk();

        // 3. Notulis (role: staff, not pimpinan, not owner) sees can_view_biometrics=false, null URLs, 403 stream, and privacy badge in Web Blade
        $notulisList = $this->actingAs($notulisStaff, 'sanctum')
            ->getJson("/api/v1/agendas/{$agenda->id}/attendances");
        $notulisList->assertOk()
            ->assertJsonPath('data.0.can_view_biometrics', false)
            ->assertJsonPath('data.0.has_selfie', true)
            ->assertJsonPath('data.0.has_signature', true)
            ->assertJsonPath('data.0.selfie_url', null)
            ->assertJsonPath('data.0.signature_url', null);

        $this->actingAs($notulisStaff, 'sanctum')
            ->getJson("/api/v1/attendances/{$attendance->id}/selfie")
            ->assertStatus(403);
        $this->actingAs($notulisStaff, 'sanctum')
            ->getJson("/api/v1/attendances/{$attendance->id}/signature")
            ->assertStatus(403);
        $this->actingAs($notulisStaff)
            ->get(route('attendances.selfie', $attendance))
            ->assertStatus(403);

        $notulenWeb = $this->actingAs($notulisStaff)
            ->get(route('admin.agendas.notulen', $agenda));
        $notulenWeb->assertOk();
        $notulenWeb->assertDontSee(route('attendances.selfie', $attendance), false);
        $notulenWeb->assertDontSee(route('attendances.signature', $attendance), false);

        $showWeb = $this->actingAs($notulisStaff)
            ->get(route('admin.agendas.show', $agenda));
        $showWeb->assertOk();
        $showWeb->assertDontSee(route('attendances.selfie', $attendance), false);
    }

    public function test_admin_unit_can_view_own_biometrics_in_cross_unit_selected_users_meeting_but_not_other_unit_attendee(): void
    {
        Storage::fake('local');

        $units = Unit::take(2)->get();
        $this->assertCount(2, $units);
        [$unitA, $unitB] = [$units[0], $units[1]];

        $creatorAdmin = User::factory()->create([
            'role' => 'admin',
            'unit_id' => $unitA->id,
            'username' => 'creator_cross_' . uniqid(),
            'nip' => '19870101' . str_pad((string) mt_rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT),
        ]);
        $participantAdminB = User::factory()->create([
            'role' => 'admin',
            'unit_id' => $unitB->id,
            'username' => 'adminb_cross_' . uniqid(),
            'nip' => '19860101' . str_pad((string) mt_rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT),
        ]);
        $attendeeA = User::factory()->create([
            'role' => 'staff',
            'unit_id' => $unitA->id,
            'username' => 'staffa_cross_' . uniqid(),
            'nip' => '19850101' . str_pad((string) mt_rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT),
        ]);

        $agenda = Agenda::create([
            'created_by' => $creatorAdmin->id,
            'judul_rapat' => 'Rapat Lintas Unit Selected Users ' . uniqid(),
            'slug' => 'rapat-lintas-selected-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Sidang B',
            'waktu_mulai' => now()->subHour(),
            'waktu_selesai' => now()->addHours(2),
            'target_type' => 'selected_users',
            'is_all_units' => false,
            'status' => 'ongoing',
        ]);
        $agenda->invitedUsers()->sync([$participantAdminB->id, $attendeeA->id]);

        $ownSelfie = "attendances/{$agenda->id}/selfies/own_b.jpg";
        $ownSig = "attendances/{$agenda->id}/signatures/own_b.png";
        $otherSelfie = "attendances/{$agenda->id}/selfies/other_a.jpg";
        $otherSig = "attendances/{$agenda->id}/signatures/other_a.png";
        Storage::disk('local')->put($ownSelfie, 'own-b-bytes');
        Storage::disk('local')->put($ownSig, 'own-b-sig-bytes');
        Storage::disk('local')->put($otherSelfie, 'other-a-bytes');
        Storage::disk('local')->put($otherSig, 'other-a-sig-bytes');

        $ownAttendance = Attendance::create([
            'agenda_id' => $agenda->id,
            'user_id' => $participantAdminB->id,
            'selfie_path' => $ownSelfie,
            'signature_path' => $ownSig,
            'signed_at' => now()->subMinutes(5),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);
        $otherAttendance = Attendance::create([
            'agenda_id' => $agenda->id,
            'user_id' => $attendeeA->id,
            'selfie_path' => $otherSelfie,
            'signature_path' => $otherSig,
            'signed_at' => now()->subMinutes(5),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        // Own attendance -> 200 OK & can_view_biometrics=true
        $this->actingAs($participantAdminB, 'sanctum')
            ->get("/api/v1/attendances/{$ownAttendance->id}/selfie")
            ->assertOk();
        $this->actingAs($participantAdminB, 'sanctum')
            ->getJson("/api/v1/attendances/{$ownAttendance->id}")
            ->assertOk()
            ->assertJsonPath('data.can_view_biometrics', true);

        // Other unit's participant in unrelated selected_users meeting -> 403
        $this->actingAs($participantAdminB, 'sanctum')
            ->getJson("/api/v1/attendances/{$otherAttendance->id}/selfie")
            ->assertStatus(403);
    }
}
