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
}
