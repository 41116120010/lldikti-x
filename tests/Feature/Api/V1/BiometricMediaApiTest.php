<?php

namespace Tests\Feature\Api\V1;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
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
}
