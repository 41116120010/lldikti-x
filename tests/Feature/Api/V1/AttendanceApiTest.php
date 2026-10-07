<?php

namespace Tests\Feature\Api\V1;

use App\Models\Agenda;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendanceApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_can_check_attendance_portal_status(): void
    {
        $staff = User::where('role', 'staff')->first();
        $agenda = Agenda::first();
        $this->assertNotNull($staff);
        $this->assertNotNull($agenda);

        $response = $this->actingAs($staff, 'sanctum')
            ->getJson("/api/v1/agendas/{$agenda->id}/attendance-portal");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'agenda',
                    'is_ongoing',
                    'is_eligible',
                    'has_attended',
                ],
            ]);
    }

    public function test_user_can_check_in_with_multipart_biometric_files(): void
    {
        Storage::fake('public');

        $agenda = Agenda::first();
        $this->assertNotNull($agenda);

        // Ensure agenda is ongoing and eligible
        $agenda->update(['status' => 'ongoing', 'is_all_units' => true]);

        $staff = User::where('role', 'staff')
            ->whereDoesntHave('attendances', fn ($q) => $q->where('agenda_id', $agenda->id))
            ->first();
        $this->assertNotNull($staff);

        // Create fake selfie and signature files
        $selfieFile = UploadedFile::fake()->image('selfie.jpg', 400, 400)->size(120);
        $signatureFile = UploadedFile::fake()->image('signature.png', 300, 150)->size(25);

        $response = $this->actingAs($staff, 'sanctum')
            ->postJson("/api/v1/agendas/{$agenda->id}/attendances", [
                'selfie_file' => $selfieFile,
                'signature_file' => $signatureFile,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user_id', $staff->id)
            ->assertJsonPath('data.agenda_id', $agenda->id);

        $this->assertDatabaseHas('attendances', [
            'agenda_id' => $agenda->id,
            'user_id' => $staff->id,
        ]);

        // Duplicate submission should return existing attendance
        $duplicateResponse = $this->actingAs($staff, 'sanctum')
            ->postJson("/api/v1/agendas/{$agenda->id}/attendances", [
                'selfie_file' => $selfieFile,
                'signature_file' => $signatureFile,
            ]);

        // Either 403 (policy checkIn) or 200 (duplicate handled)
        $this->assertTrue(in_array($duplicateResponse->status(), [200, 403], true));
    }

    public function test_user_can_view_my_attendance_history(): void
    {
        $staff = User::where('role', 'staff')->first();
        $this->assertNotNull($staff);

        $response = $this->actingAs($staff, 'sanctum')
            ->getJson('/api/v1/attendances/my-history');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_admin_can_view_agenda_attendances_list(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();
        $this->assertNotNull($admin);
        $this->assertNotNull($agenda);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/agendas/{$agenda->id}/attendances");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'meta',
            ]);
    }

    public function test_user_can_view_portal_feed_via_api(): void
    {
        $staff = User::where('role', 'staff')->first();
        $this->assertNotNull($staff);

        $response = $this->actingAs($staff, 'sanctum')
            ->getJson('/api/v1/attendances/portal');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'ongoing_agendas',
                    'upcoming_agendas',
                    'recent_attendances',
                ],
            ]);
    }
}

