<?php

namespace Tests\Feature;

use App\Models\Unit;
use App\Models\User;
use Tests\TestCase;

class UnitManagementTest extends TestCase
{
    public function test_administrator_can_view_units_index(): void
    {
        $admin = User::where('role', 'administrator')->first();

        $response = $this->actingAs($admin)->get('/admin/units');

        $response->assertStatus(200);
        $response->assertSee('Master Data Unit Kerja');
    }

    public function test_admin_unit_and_staff_cannot_access_units(): void
    {
        $adminUnit = User::where('role', 'admin')->first();
        $staff = User::where('role', 'staff')->first();

        $this->actingAs($adminUnit)->get('/admin/units')->assertStatus(403);
        $this->actingAs($staff)->get('/admin/units')->assertStatus(403);
    }

    public function test_administrator_can_create_new_unit(): void
    {
        $admin = User::where('role', 'administrator')->first();

        $response = $this->actingAs($admin)->post('/admin/units', [
            'nama_unit' => 'Pokja Sistem Informasi & Data',
            'kode_unit' => 'POKJA-SID',
            'deskripsi' => 'Pengembangan sistem informasi dan tata kelola data pendidikan tinggi.',
            'is_active' => true,
        ]);

        $response->assertRedirect('/admin/units');
        $this->assertDatabaseHas('units', [
            'kode_unit' => 'POKJA-SID',
            'nama_unit' => 'Pokja Sistem Informasi & Data',
        ]);

        // Verify Activity Log
        $this->assertDatabaseHas('activity_logs', [
            'activity_type' => 'CREATE_UNIT',
            'user_id' => $admin->id,
        ]);
    }

    public function test_administrator_can_update_unit(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $unit = Unit::where('kode_unit', 'POKJA-SID')->first();

        $response = $this->actingAs($admin)->put("/admin/units/{$unit->id}", [
            'nama_unit' => 'Pokja Sistem Informasi & Transformasi Digital',
            'kode_unit' => 'POKJA-SID',
            'deskripsi' => 'Deskripsi yang diperbarui.',
            'is_active' => true,
        ]);

        $response->assertRedirect('/admin/units');
        $this->assertDatabaseHas('units', [
            'id' => $unit->id,
            'nama_unit' => 'Pokja Sistem Informasi & Transformasi Digital',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'activity_type' => 'UPDATE_UNIT',
            'user_id' => $admin->id,
        ]);
    }

    public function test_administrator_can_toggle_unit_status(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $unit = Unit::where('kode_unit', 'POKJA-SID')->first();

        $response = $this->actingAs($admin)->patch("/admin/units/{$unit->id}/toggle-status");

        $response->assertRedirect();
        $this->assertDatabaseHas('units', [
            'id' => $unit->id,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'activity_type' => 'TOGGLE_UNIT_STATUS',
            'user_id' => $admin->id,
        ]);
    }

    public function test_administrator_cannot_delete_unit_with_active_users(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $unitWithUsers = Unit::where('kode_unit', 'POKJA-AKM')->first();

        $response = $this->actingAs($admin)->delete("/admin/units/{$unitWithUsers->id}");

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('units', ['id' => $unitWithUsers->id]);
    }

    public function test_administrator_can_delete_empty_unit(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $emptyUnit = Unit::where('kode_unit', 'POKJA-SID')->first();

        $response = $this->actingAs($admin)->delete("/admin/units/{$emptyUnit->id}");

        $response->assertRedirect('/admin/units');
        $this->assertDatabaseMissing('units', ['id' => $emptyUnit->id]);

        $this->assertDatabaseHas('activity_logs', [
            'activity_type' => 'DELETE_UNIT',
            'user_id' => $admin->id,
        ]);
    }
}
