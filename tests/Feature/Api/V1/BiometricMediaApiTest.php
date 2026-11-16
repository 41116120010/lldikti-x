<?php

namespace Tests\Feature\Api\V1;

use App\Models\Agenda;
use App\Models\Attendance;
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
}
