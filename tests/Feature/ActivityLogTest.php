<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ActivityLogger;
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
        $adminAkm = User::whereHas('unit', fn ($q) => $q->where('kode_unit', 'POKJA-AKM'))->firstOrFail();
        $adminAkm->update(['role' => 'admin']);
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

    public function test_activity_logs_can_be_filtered_by_date_range_and_user(): void
    {
        $superadmin = User::where('role', 'administrator')->first();

        $response = $this->actingAs($superadmin)->get("/admin/logs?start_date=2026-01-01&end_date=2026-12-31&user_id={$superadmin->id}");

        $response->assertStatus(200);
        $response->assertSee('Audit Trail & Log Aktivitas');
    }

    public function test_activity_logs_search_by_keyword(): void
    {
        $superadmin = User::where('role', 'administrator')->first();

        $response = $this->actingAs($superadmin)->get('/admin/logs?search=login');

        $response->assertStatus(200);
    }

    public function test_activity_logs_render_payload_inspection_data(): void
    {
        $superadmin = User::where('role', 'administrator')->first();

        ActivityLogger::log(
            type: 'TEST_MUTATION',
            description: 'Melakukan pengetesan payload properties inspection',
            targetModel: User::class,
            targetId: $superadmin->id,
            properties: ['field_lama' => 'nilai_sebelumnya', 'field_baru' => 'nilai_terbaru']
        );

        $response = $this->actingAs($superadmin)->get('/admin/logs?type=TEST_MUTATION');

        $response->assertStatus(200);
        $response->assertSee('Lihat Payload Perubahan');
        $response->assertSee('nilai_terbaru');
    }

    public function test_activity_logs_table_paginates_ten_items_per_page(): void
    {
        $superadmin = User::where('role', 'administrator')->first();

        // Seed 12 distinct activity logs with specific test marker
        for ($i = 1; $i <= 12; $i++) {
            ActivityLogger::log(
                type: 'PAGINATION_LIMIT_TEST',
                description: "Percobaan paginasi log #{$i}",
                targetModel: null,
                targetId: null,
                properties: ['index' => $i],
                user: $superadmin
            );
        }

        $response = $this->actingAs($superadmin)->get('/admin/logs?type=PAGINATION_LIMIT_TEST');

        $response->assertStatus(200);
        $logs = $response->viewData('logs');
        $this->assertEquals(10, $logs->perPage());
        $this->assertEquals(10, $logs->count());
        $this->assertEquals(12, $logs->total());
        $this->assertEquals(2, $logs->lastPage());
        $response->assertSee('Menampilkan');
    }
}
