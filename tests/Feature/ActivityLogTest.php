<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    public function test_administrator_can_view_activity_logs(): void
    {
        $superadmin = User::where('role', 'administrator')->first();

        $response = $this->actingAs($superadmin)->get('/admin/logs');

        $response->assertStatus(200);
        $response->assertSee('Audit Trail & Log Aktivitas');
    }

    public function test_admin_unit_and_staff_cannot_view_activity_logs(): void
    {
        $adminAkm = User::where('username', 'admin_akademik')->first();
        $staff = User::where('role', 'staff')->first();

        $this->actingAs($adminAkm)->get('/admin/logs')->assertStatus(403);
        $this->actingAs($staff)->get('/admin/logs')->assertStatus(403);
    }

    public function test_activity_logs_can_be_filtered_by_type(): void
    {
        $superadmin = User::where('role', 'administrator')->first();

        $response = $this->actingAs($superadmin)->get('/admin/logs?type=AUTH_LOGIN');

        $response->assertStatus(200);
    }
}
