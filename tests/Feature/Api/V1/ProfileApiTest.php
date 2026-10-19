<?php

namespace Tests\Feature\Api\V1;

use App\Models\ActivityLog;
use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::where('is_active', true)->firstOrFail();
        $this->user->password = Hash::make('Password123!');
        $this->user->save();

        $this->token = $this->user->createToken('TestDevice')->plainTextToken;
    }

    public function test_authenticated_user_can_get_profile_with_stats_and_security_meta(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/profile');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $this->user->id)
            ->assertJsonPath('data.name', $this->user->name)
            ->assertJsonPath('data.username', $this->user->username)
            ->assertJsonPath('data.email', $this->user->email);

        // Verify official civil service designations
        $this->assertNotEmpty($response->json('data.jabatan'));
        $this->assertNotEmpty($response->json('data.pangkat_golongan'));

        // Verify attendance stats structure
        $response->assertJsonStructure([
            'data' => [
                'attendance_stats' => [
                    'total_agendas',
                    'total_agendas_label',
                    'total_attended',
                    'attendance_rate',
                    'attendance_rate_label',
                    'on_time_count',
                    'on_time_rate',
                    'on_time_rate_label',
                    'period_label',
                ],
                'security' => [
                    'last_password_changed_at',
                    'last_password_changed_label',
                    'last_login_at',
                    'last_login_label',
                ],
            ],
        ]);
    }

    public function test_authenticated_user_can_access_auth_me_with_stats_and_security_meta(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $this->user->id)
            ->assertJsonStructure([
                'data' => [
                    'attendance_stats',
                    'security',
                    'jabatan',
                    'pangkat_golongan',
                ],
            ]);
    }

    public function test_user_can_update_profile_partially_changing_only_phone(): void
    {
        $originalName = $this->user->name;
        $originalEmail = $this->user->email;
        $newPhone = '081299887766';

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson('/api/v1/profile', [
                'phone' => $newPhone,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.phone', $newPhone)
            ->assertJsonPath('data.name', $originalName)
            ->assertJsonPath('data.email', $originalEmail);

        $this->user->refresh();
        $this->assertEquals($newPhone, $this->user->phone);
        $this->assertEquals($originalName, $this->user->name);
        $this->assertEquals($originalEmail, $this->user->email);
    }

    public function test_user_can_update_full_profile(): void
    {
        $updatedName = 'Nama Baru Pegawai ASN';
        $updatedEmail = 'pegawai.baru@lldikti.test';
        $updatedPhone = '081233445566';

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson('/api/v1/profile', [
                'name' => $updatedName,
                'email' => $updatedEmail,
                'phone' => $updatedPhone,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', $updatedName)
            ->assertJsonPath('data.email', $updatedEmail)
            ->assertJsonPath('data.phone', $updatedPhone);

        $this->user->refresh();
        $this->assertEquals($updatedName, $this->user->name);
        $this->assertEquals($updatedEmail, $this->user->email);
        $this->assertEquals($updatedPhone, $this->user->phone);
    }

    public function test_partial_profile_update_fails_if_email_is_taken_by_another_user(): void
    {
        $otherUser = User::where('id', '!=', $this->user->id)->firstOrFail();

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson('/api/v1/profile', [
                'email' => $otherUser->email,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_attendance_statistics_calculation_accuracy(): void
    {
        $admin = User::where('role', 'administrator')->firstOrFail();

        // Create a completed agenda visible to this user
        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Evaluasi Uji Paritas Profil',
            'slug' => 'rapat-evaluasi-uji-paritas-profil-' . uniqid(),
            'jenis_rapat' => 'Evaluasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Sidang 1',
            'waktu_mulai' => now('Asia/Jakarta')->subHours(3),
            'waktu_selesai' => now('Asia/Jakarta')->subHours(1),
            'status' => 'completed',
            'is_all_units' => true,
            'created_by' => $admin->id,
        ]);

        // Record on-time attendance (signed 10 mins before waktu_mulai)
        Attendance::create([
            'agenda_id' => $agenda->id,
            'user_id' => $this->user->id,
            'signed_at' => now('Asia/Jakarta')->subHours(3)->subMinutes(10),
            'selfie_path' => 'selfies/test.jpg',
            'signature_path' => 'signatures/test.png',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit/Test',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/profile');

        $response->assertOk();
        $stats = $response->json('data.attendance_stats');

        $this->assertGreaterThanOrEqual(1, $stats['total_agendas']);
        $this->assertGreaterThanOrEqual(1, $stats['total_attended']);
        $this->assertGreaterThanOrEqual(1, $stats['on_time_count']);
        $this->assertGreaterThan(0.0, $stats['attendance_rate']);
        $this->assertGreaterThan(0.0, $stats['on_time_rate']);
        $this->assertStringContainsString('%', $stats['attendance_rate_label']);
        $this->assertStringContainsString('%', $stats['on_time_rate_label']);
    }

    public function test_security_password_label_updates_after_password_change(): void
    {
        // 1. Initial check
        $initialRes = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/profile');
        $initialRes->assertOk();
        $this->assertNotEmpty($initialRes->json('data.security.last_password_changed_label'));

        // 2. Change password
        $changePassRes = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson('/api/v1/profile/password', [
                'current_password' => 'Password123!',
                'password' => 'NewSecretPass789!',
                'password_confirmation' => 'NewSecretPass789!',
                'device_name' => 'ProfileTestPhone',
            ]);
        $changePassRes->assertOk();
        $newRotatedToken = $changePassRes->json('data.token');
        $this->assertNotEmpty($newRotatedToken);

        $this->app['auth']->forgetGuards();

        // 3. Check profile with rotated token -> label should be 'Hari ini'
        $afterRes = $this->withHeader('Authorization', "Bearer {$newRotatedToken}")
            ->getJson('/api/v1/profile');

        $afterRes->assertOk()
            ->assertJsonPath('data.security.last_password_changed_label', 'Hari ini');
    }

    public function test_unauthenticated_requests_to_profile_are_rejected(): void
    {
        $this->getJson('/api/v1/profile')->assertStatus(401);
        $this->putJson('/api/v1/profile', ['phone' => '0812345678'])->assertStatus(401);
    }
}
