<?php

namespace Tests\Feature\Api\V1;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ActivityLogApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_administrator_can_view_all_system_activity_logs_via_api(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $this->assertNotNull($admin);

        // Ensure at least one activity log exists
        ActivityLog::create([
            'user_id' => $admin->id,
            'activity_type' => 'LOGIN',
            'description' => 'Pengguna login ke dalam sistem.',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Test Agent',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/activity-logs');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'activity_type',
                        'description',
                        'ip_address',
                        'created_at',
                    ],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_staff_cannot_view_all_system_activity_logs_via_api(): void
    {
        $staff = User::where('role', 'staff')->first();
        $this->assertNotNull($staff);

        $response = $this->actingAs($staff, 'sanctum')
            ->getJson('/api/v1/activity-logs');

        $response->assertStatus(403);
    }

    public function test_administrator_can_filter_activity_logs_via_api(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $this->assertNotNull($admin);

        ActivityLog::create([
            'user_id' => $admin->id,
            'activity_type' => 'SPECIAL_AUDIT_MARKER',
            'description' => 'Log aktivitas tes khusus.',
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Special Filter Agent',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/activity-logs?type=SPECIAL_AUDIT_MARKER');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertSame('SPECIAL_AUDIT_MARKER', $data[0]['activity_type']);
    }

    public function test_activity_logs_response_includes_summary_telemetry_and_category_metadata(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $this->assertNotNull($admin);

        ActivityLog::create([
            'user_id' => $admin->id,
            'activity_type' => 'AUTH_LOGIN_TEST',
            'description' => 'Login simulasi untuk pengujian.',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Test',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/activity-logs');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'activity_type',
                        'category' => [
                            'value',
                            'label',
                        ],
                        'description',
                    ],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'summary' => [
                    'total_logs',
                    'auth_success',
                    'incident_count',
                    'today_count',
                    'categories' => [
                        'login',
                        'agenda',
                        'presensi',
                        'user',
                    ],
                ],
            ]);

        $this->assertGreaterThanOrEqual(1, $response->json('summary.auth_success'));
        $this->assertEquals(0, $response->json('summary.incident_count'));
    }

    public function test_administrator_can_filter_activity_logs_by_macro_category(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $this->assertNotNull($admin);

        ActivityLog::create([
            'user_id' => $admin->id,
            'activity_type' => 'AUTH_LOGIN_TEST',
            'description' => 'Aktivitas kategori login.',
            'created_at' => now(),
        ]);

        ActivityLog::create([
            'user_id' => $admin->id,
            'activity_type' => 'RECORD_ATTENDANCE_TEST',
            'description' => 'Aktivitas kategori presensi.',
            'created_at' => now(),
        ]);

        ActivityLog::create([
            'user_id' => $admin->id,
            'activity_type' => 'CREATE_AGENDA_TEST',
            'description' => 'Aktivitas kategori agenda.',
            'created_at' => now(),
        ]);

        ActivityLog::create([
            'user_id' => $admin->id,
            'activity_type' => 'UPDATE_USER_TEST',
            'description' => 'Aktivitas kategori user.',
            'created_at' => now(),
        ]);

        // Filter: Presensi
        $presensiResponse = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/activity-logs?category=presensi');

        $presensiResponse->assertOk()
            ->assertJsonPath('success', true);

        $presensiData = $presensiResponse->json('data');
        $this->assertNotEmpty($presensiData);
        foreach ($presensiData as $item) {
            $this->assertEquals('presensi', $item['category']['value']);
        }

        // Filter: Login
        $loginResponse = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/activity-logs?category=login');

        $loginResponse->assertOk()
            ->assertJsonPath('success', true);

        $loginData = $loginResponse->json('data');
        $this->assertNotEmpty($loginData);
        foreach ($loginData as $item) {
            $this->assertEquals('login', $item['category']['value']);
        }
    }

    public function test_administrator_can_filter_activity_logs_by_date_range(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $this->assertNotNull($admin);

        ActivityLog::create([
            'user_id' => $admin->id,
            'activity_type' => 'OLD_ACTIVITY',
            'description' => 'Aktivitas masa lalu.',
            'created_at' => now()->subDays(10),
        ]);

        ActivityLog::create([
            'user_id' => $admin->id,
            'activity_type' => 'RECENT_ACTIVITY',
            'description' => 'Aktivitas terkini.',
            'created_at' => now(),
        ]);

        $today = now()->format('Y-m-d');
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/activity-logs?start_date={$today}&end_date={$today}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        foreach ($data as $item) {
            $this->assertStringStartsWith($today, $item['created_at']);
        }
    }
}
