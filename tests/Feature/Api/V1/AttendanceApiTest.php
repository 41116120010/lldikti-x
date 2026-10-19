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
        $agenda = Agenda::first();
        $this->assertNotNull($staff);
        $this->assertNotNull($agenda);

        // Ensure user has at least one attendance record to verify enriched agenda
        \App\Models\Attendance::firstOrCreate(
            ['agenda_id' => $agenda->id, 'user_id' => $staff->id],
            ['signed_at' => now(), 'ip_address' => '127.0.0.1', 'user_agent' => 'PHPUnit']
        );

        $response = $this->actingAs($staff, 'sanctum')
            ->getJson('/api/v1/attendances/my-history');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'agenda_id',
                        'user_id',
                        'agenda' => [
                            'id',
                            'judul_rapat',
                            'slug',
                            'jenis_rapat',
                            'tipe_rapat',
                            'lokasi_ruang',
                            'link_meeting',
                            'waktu_mulai',
                            'waktu_selesai',
                            'rentang_waktu',
                            'status' => ['value', 'label'],
                            'nomor_ba',
                        ],
                        'signed_at',
                    ],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'summary' => [
                    'total_attended',
                    'total_eligible_agendas',
                    'attendance_rate',
                    'attendance_rate_label',
                    'period_label',
                ],
            ]);
    }

    public function test_user_can_filter_my_attendance_history_by_search(): void
    {
        $staff = User::where('role', 'staff')->first();
        $agenda = Agenda::first();
        $this->assertNotNull($staff);
        $this->assertNotNull($agenda);

        \App\Models\Attendance::firstOrCreate(
            ['agenda_id' => $agenda->id, 'user_id' => $staff->id],
            ['signed_at' => now(), 'ip_address' => '127.0.0.1', 'user_agent' => 'PHPUnit']
        );

        // Search matching keyword in agenda title
        $responseMatch = $this->actingAs($staff, 'sanctum')
            ->getJson('/api/v1/attendances/my-history?search=PDDikti');

        $responseMatch->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 1);

        // Search non-matching keyword
        $responseNoMatch = $this->actingAs($staff, 'sanctum')
            ->getJson('/api/v1/attendances/my-history?search=KataKunciPalsuYangTidakAda');

        $responseNoMatch->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 0);
    }

    public function test_user_can_filter_my_attendance_history_by_period(): void
    {
        $staff = User::where('role', 'staff')->first();
        $agenda = Agenda::first();
        $this->assertNotNull($staff);
        $this->assertNotNull($agenda);

        \App\Models\Attendance::firstOrCreate(
            ['agenda_id' => $agenda->id, 'user_id' => $staff->id],
            ['signed_at' => now(), 'ip_address' => '127.0.0.1', 'user_agent' => 'PHPUnit']
        );

        $currentPeriod = now()->format('Y-m');
        $response = $this->actingAs($staff, 'sanctum')
            ->getJson("/api/v1/attendances/my-history?period={$currentPeriod}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonStructure([
                'summary' => [
                    'total_attended',
                    'total_eligible_agendas',
                    'attendance_rate',
                    'period_label',
                ],
            ]);

        // Historical past period with 0 attendances
        $responsePast = $this->actingAs($staff, 'sanctum')
            ->getJson('/api/v1/attendances/my-history?period=2020-01');

        $responsePast->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 0);
    }

    public function test_user_can_filter_my_attendance_history_by_tipe_rapat(): void
    {
        $staff = User::where('role', 'staff')->first();
        $agenda = Agenda::first();
        $this->assertNotNull($staff);
        $this->assertNotNull($agenda);

        \App\Models\Attendance::firstOrCreate(
            ['agenda_id' => $agenda->id, 'user_id' => $staff->id],
            ['signed_at' => now(), 'ip_address' => '127.0.0.1', 'user_agent' => 'PHPUnit']
        );

        // Matching tipe_rapat (agenda fixture is hybrid)
        $responseHybrid = $this->actingAs($staff, 'sanctum')
            ->getJson('/api/v1/attendances/my-history?tipe_rapat=hybrid');

        $responseHybrid->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 1);

        // Non-matching tipe_rapat (offline)
        $responseOffline = $this->actingAs($staff, 'sanctum')
            ->getJson('/api/v1/attendances/my-history?tipe_rapat=offline');

        $responseOffline->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 0);
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
                'summary' => [
                    'total_invited',
                    'total_attended',
                    'unattended_count',
                    'participation_rate',
                    'participation_rate_label',
                ],
            ]);
    }

    public function test_admin_can_filter_unattended_attendances_list(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();
        $this->assertNotNull($admin);
        $this->assertNotNull($agenda);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/agendas/{$agenda->id}/attendances?status=unattended");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'agenda_id',
                        'user_id',
                        'user' => [
                            'id',
                            'name',
                            'nip',
                        ],
                        'signed_at',
                        'status',
                    ],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'summary' => [
                    'total_invited',
                    'total_attended',
                    'unattended_count',
                    'participation_rate',
                    'participation_rate_label',
                ],
            ]);

        $this->assertEquals('belum_hadir', $response->json('data.0.status'));
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

    public function test_user_can_view_single_attendance_receipt(): void
    {
        $staff = User::where('role', 'staff')->first();
        $agenda = Agenda::first();
        $this->assertNotNull($staff);
        $this->assertNotNull($agenda);

        $attendance = \App\Models\Attendance::firstOrCreate(
            ['agenda_id' => $agenda->id, 'user_id' => $staff->id],
            [
                'signed_at' => now(),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'SIPERAPAT-Test/1.0',
            ]
        );

        $response = $this->actingAs($staff, 'sanctum')
            ->getJson("/api/v1/attendances/{$attendance->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $attendance->id)
            ->assertJsonPath('data.user_id', $staff->id)
            ->assertJsonPath('data.verification_token', $attendance->verification_token)
            ->assertJsonPath('data.receipt.nomor_bukti', $attendance->verification_token)
            ->assertJsonPath('data.receipt.institutional_header.instansi', 'LEMBAGA LAYANAN PENDIDIKAN TINGGI WILAYAH X')
            ->assertJsonPath('data.receipt.meeting.judul_rapat', $agenda->judul_rapat)
            ->assertJsonPath('data.receipt.participant.name', $staff->name)
            ->assertJsonPath('data.receipt.validation.status', 'tercatat');

        $this->assertStringStartsWith('SIP-', $response->json('data.verification_token'));
    }

    public function test_user_can_view_single_attendance_receipt_via_alias(): void
    {
        $staff = User::where('role', 'staff')->first();
        $agenda = Agenda::first();
        $this->assertNotNull($staff);
        $this->assertNotNull($agenda);

        $attendance = \App\Models\Attendance::firstOrCreate(
            ['agenda_id' => $agenda->id, 'user_id' => $staff->id],
            [
                'signed_at' => now(),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'SIPERAPAT-Test/1.0',
            ]
        );

        $response = $this->actingAs($staff, 'sanctum')
            ->getJson("/api/v1/attendances/{$attendance->id}/receipt");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $attendance->id)
            ->assertJsonPath('data.verification_token', $attendance->verification_token);
    }

    public function test_user_cannot_view_other_users_attendance_receipt(): void
    {
        $staffUsers = User::where('role', 'staff')->take(2)->get();
        $this->assertCount(2, $staffUsers);
        $staffA = $staffUsers[0];
        $staffB = $staffUsers[1];

        $agenda = Agenda::first();
        $this->assertNotNull($agenda);

        $attendanceA = \App\Models\Attendance::firstOrCreate(
            ['agenda_id' => $agenda->id, 'user_id' => $staffA->id],
            [
                'signed_at' => now(),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'SIPERAPAT-Test/1.0',
            ]
        );

        // Staff B tries to access Staff A's receipt -> 403 Forbidden
        $response = $this->actingAs($staffB, 'sanctum')
            ->getJson("/api/v1/attendances/{$attendanceA->id}");

        $response->assertForbidden();
    }

    public function test_administrator_can_view_any_attendance_receipt(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $staff = User::where('role', 'staff')->first();
        $agenda = Agenda::first();
        $this->assertNotNull($admin);
        $this->assertNotNull($staff);
        $this->assertNotNull($agenda);

        $attendance = \App\Models\Attendance::firstOrCreate(
            ['agenda_id' => $agenda->id, 'user_id' => $staff->id],
            [
                'signed_at' => now(),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'SIPERAPAT-Test/1.0',
            ]
        );

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/attendances/{$attendance->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $attendance->id);
    }
}

