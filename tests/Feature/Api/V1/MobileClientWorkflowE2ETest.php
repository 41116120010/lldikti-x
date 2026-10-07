<?php

namespace Tests\Feature\Api\V1;

use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * End-to-End lifecycle simulation of a React Native mobile client interacting
 * with the SIPERAPAT REST API v1.
 */
class MobileClientWorkflowE2ETest extends TestCase
{
    use DatabaseTransactions;

    public function test_complete_mobile_client_lifecycle_workflow(): void
    {
        Storage::fake('public');

        // =========================================================================
        // 1. Authentication & Bootstrap (Staff ASN)
        // =========================================================================
        $staff = User::where('role', 'staff')->where('is_active', true)->first();
        $this->assertNotNull($staff, 'Staff user must exist in pristine seed data.');

        $staffLoginResponse = $this->postJson('/api/v1/auth/login', [
            'identifier' => $staff->nip ?? $staff->username,
            'password' => 'Password123!',
            'device_name' => 'Pixel 8 Pro (React Native Client)',
        ]);

        $staffLoginResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer');

        $staffToken = $staffLoginResponse->json('data.token');
        $this->assertNotEmpty($staffToken);

        $withToken = function (string $token) {
            $this->app['auth']->forgetGuards();
            return $this->withHeader('Authorization', "Bearer {$token}");
        };

        // Staff inspects authenticated identity & dashboard
        $meResponse = $withToken($staffToken)->getJson('/api/v1/auth/me');
        $meResponse->assertOk()
            ->assertJsonPath('data.id', $staff->id);

        $dashboardResponse = $withToken($staffToken)->getJson('/api/v1/dashboard');
        $dashboardResponse->assertOk()
            ->assertJsonPath('success', true);

        $portalFeedResponse = $withToken($staffToken)->getJson('/api/v1/attendances/portal');
        $portalFeedResponse->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'ongoing_agendas',
                    'upcoming_agendas',
                    'recent_attendances',
                ],
            ]);

        // =========================================================================
        // 2. Meeting Lifecycle Management (Admin Actions)
        // =========================================================================
        $admin = User::where('role', 'administrator')->first();
        $this->assertNotNull($admin);

        $adminLoginResponse = $this->postJson('/api/v1/auth/login', [
            'identifier' => $admin->nip ?? $admin->username,
            'password' => 'Password123!',
            'device_name' => 'Galaxy Tab S9 (Admin)',
        ]);
        $adminLoginResponse->assertOk();
        $adminToken = $adminLoginResponse->json('data.token');

        // Admin creates a new meeting agenda
        $createAgendaResponse = $withToken($adminToken)
            ->postJson('/api/v1/agendas', [
                'judul_rapat' => 'Rapat Pleno Penyusunan Rencana Kerja Tahunan LLDIKTI',
                'jenis_rapat' => 'pleno',
                'tipe_rapat' => 'hybrid',
                'lokasi_ruang' => 'Ruang Sidang Utama Lantai 2',
                'link_meeting' => 'https://zoom.us/j/123987654',
                'waktu_mulai' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'waktu_selesai' => now()->addDays(2)->addHours(2)->format('Y-m-d H:i:s'),
                'is_all_units' => true,
            ]);

        $createAgendaResponse->assertStatus(201)
            ->assertJsonPath('success', true);
        $agendaId = $createAgendaResponse->json('data.id');

        // Admin assigns meeting leader and minute-taker dynamically
        $rolesResponse = $withToken($adminToken)
            ->patchJson("/api/v1/agendas/{$agendaId}/roles", [
                'pimpinan_id' => $admin->id,
                'notulis_id' => $staff->id,
            ]);
        $rolesResponse->assertOk()
            ->assertJsonPath('data.pimpinan.id', $admin->id)
            ->assertJsonPath('data.notulis.id', $staff->id);

        // Admin opens attendance session (ongoing)
        $startResponse = $withToken($adminToken)
            ->patchJson("/api/v1/agendas/{$agendaId}/status", [
                'status' => 'ongoing',
            ]);
        $startResponse->assertOk()
            ->assertJsonPath('data.status.value', 'ongoing');

        // =========================================================================
        // 3. Biometric Check-In (Staff Actions)
        // =========================================================================
        // Staff checks readiness of attendance portal
        $checkPortalResponse = $withToken($staffToken)
            ->getJson("/api/v1/agendas/{$agendaId}/attendance-portal");
        $checkPortalResponse->assertOk()
            ->assertJsonPath('data.is_ongoing', true)
            ->assertJsonPath('data.is_eligible', true)
            ->assertJsonPath('data.has_attended', false);

        // Staff captures selfie & signature pad and posts multipart attendance
        $selfie = UploadedFile::fake()->image('selfie_asn.jpg', 600, 800)->size(140);
        $signature = UploadedFile::fake()->image('signature_pad.png', 400, 200)->size(28);

        $checkInResponse = $withToken($staffToken)
            ->postJson("/api/v1/agendas/{$agendaId}/attendances", [
                'selfie_file' => $selfie,
                'signature_file' => $signature,
            ]);

        $checkInResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.agenda_id', $agendaId)
            ->assertJsonPath('data.user_id', $staff->id);
        $attendanceId = $checkInResponse->json('data.id');

        // Staff views personal attendance history
        $myHistoryResponse = $withToken($staffToken)
            ->getJson('/api/v1/attendances/my-history');
        $myHistoryResponse->assertOk()
            ->assertJsonPath('success', true);

        // Staff views protected streaming selfie & signature
        $selfieResponse = $withToken($staffToken)
            ->get("/api/v1/attendances/{$attendanceId}/selfie");
        $selfieResponse->assertOk()
            ->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private');

        $sigResponse = $withToken($staffToken)
            ->get("/api/v1/attendances/{$attendanceId}/signature");
        $sigResponse->assertOk();

        // =========================================================================
        // 4. Minutes of Meeting & Documentation Upload (Notulis / Admin)
        // =========================================================================
        $docPhoto = UploadedFile::fake()->image('sesi_diskusi.jpg', 800, 600)->size(250);

        $notulenResponse = $withToken($staffToken)
            ->putJson("/api/v1/agendas/{$agendaId}/notulen", [
                'notulensi' => '<p>Rapat pleno dipimpin oleh Kepala LLDIKTI. Seluruh unit kerja memaparkan capaian program.</p>',
                'kesimpulan' => '<p>Seluruh usulan disetujui dengan catatan revisi paling lambat 7 hari kerja.</p>',
                'photos' => [$docPhoto],
                'captions' => ['Foto dokumentasi pleno pemaparan program'],
            ]);

        $notulenResponse->assertOk()
            ->assertJsonPath('success', true);

        // Admin concludes the meeting
        $finishResponse = $withToken($adminToken)
            ->patchJson("/api/v1/agendas/{$agendaId}/status", [
                'status' => 'completed',
            ]);
        $finishResponse->assertOk()
            ->assertJsonPath('data.status.value', 'completed');

        // =========================================================================
        // 5. Reporting, Document Exports & Audit Trail
        // =========================================================================
        // Fetch agenda recap
        $recapResponse = $withToken($staffToken)
            ->getJson("/api/v1/reports/agendas/{$agendaId}");
        $recapResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['export_links' => ['pdf', 'word']]]);

        // Export PDF & Word documents via API
        $pdfResponse = $withToken($staffToken)
            ->get("/api/v1/reports/agendas/{$agendaId}/export/pdf");
        $pdfResponse->assertOk();

        $wordResponse = $withToken($staffToken)
            ->get("/api/v1/reports/agendas/{$agendaId}/export/word");
        $wordResponse->assertOk();

        // Export summary CSV spreadsheet via API
        $csvResponse = $withToken($adminToken)
            ->get('/api/v1/reports/summary/csv');
        $csvResponse->assertOk();
        $this->assertStringContainsString('text/csv', (string) $csvResponse->headers->get('Content-Type'));

        // Superadmin audits activity logs
        $logsResponse = $withToken($adminToken)
            ->getJson('/api/v1/activity-logs?per_page=10');
        $logsResponse->assertOk()
            ->assertJsonPath('success', true);

        // =========================================================================
        // 6. Security & Token Revocation on Password Change
        // =========================================================================
        // Set initial password for predictable verification
        $staff->update(['password' => bcrypt('Password123!')]);

        $updatePasswordResponse = $withToken($staffToken)
            ->putJson('/api/v1/profile', [
                'name' => $staff->name,
                'email' => $staff->email,
                'current_password' => 'Password123!',
                'password' => 'PasswordBaru2026!',
                'password_confirmation' => 'PasswordBaru2026!',
            ]);
        $updatePasswordResponse->assertOk()
            ->assertJsonPath('success', true);

        // Old token is immediately revoked
        $this->app['auth']->forgetGuards();
        $staleTokenResponse = $this->withHeader('Authorization', "Bearer {$staffToken}")
            ->getJson('/api/v1/auth/me');
        $staleTokenResponse->assertStatus(401);

        // Staff can log in with new password
        $newLoginResponse = $this->postJson('/api/v1/auth/login', [
            'identifier' => $staff->nip ?? $staff->username,
            'password' => 'PasswordBaru2026!',
            'device_name' => 'Pixel 8 Pro (Re-authenticated)',
        ]);
        $newLoginResponse->assertOk()
            ->assertJsonPath('success', true);
        $newToken = $newLoginResponse->json('data.token');

        // Staff logs out
        $logoutResponse = $withToken($newToken)
            ->postJson('/api/v1/auth/logout');
        $logoutResponse->assertOk()
            ->assertJsonPath('success', true);

        $this->app['auth']->forgetGuards();
        $afterLogoutResponse = $this->withHeader('Authorization', "Bearer {$newToken}")
            ->getJson('/api/v1/auth/me');
        $afterLogoutResponse->assertStatus(401);
    }
}
