<?php

namespace Tests\Feature;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    public function test_administrator_can_view_all_users(): void
    {
        $superadmin = User::where('role', 'administrator')->first();

        $response = $this->actingAs($superadmin)->get('/admin/users');

        $response->assertStatus(200);
        $response->assertSee('Kelola Seluruh Pengguna');
        $response->assertSee('Dr. Ir. Hendra Prasetyo');
        $response->assertViewHas('userStats');
        $response->assertSee('Total Pengguna');
        $response->assertSee('Akun Aktif');
        $response->assertSee('Akun Non-Aktif');
        
        $stats = $response->viewData('userStats');
        $this->assertEquals(User::count(), $stats->total);
    }

    public function test_admin_unit_can_only_view_users_in_their_unit(): void
    {
        $adminAkm = User::where('username', 'admin_akademik')->first();
        $staffAkm = User::where('username', 'staff_rizky')->first();
        $staffKlb = User::where('username', 'staff_nurul')->first();

        $response = $this->actingAs($adminAkm)->get('/admin/users');

        $response->assertStatus(200);
        $response->assertSee($staffAkm->name);
        $response->assertDontSee($staffKlb->name);
        $response->assertViewHas('userStats');
        $response->assertSee('Pegawai di Unit');
        
        $stats = $response->viewData('userStats');
        $this->assertEquals(User::where('unit_id', $adminAkm->unit_id)->count(), $stats->total);
    }

    public function test_staff_cannot_access_user_management(): void
    {
        $staff = User::where('role', 'staff')->first();

        $this->actingAs($staff)->get('/admin/users')->assertStatus(403);
    }

    public function test_administrator_can_create_any_user_role(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $unit = Unit::where('kode_unit', 'BAG-TU')->first();

        $response = $this->actingAs($superadmin)->post('/admin/users', [
            'name' => 'Faisal Rahman, S.Kom.',
            'nip' => '199505122021021008',
            'username' => 'faisal_rahman',
            'email' => 'faisal.rahman@lldikti.kemdikbud.go.id',
            'password' => 'Password123!',
            'role' => 'staff',
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);

        $response->assertRedirect('/admin/users');
        $this->assertDatabaseHas('users', [
            'nip' => '199505122021021008',
            'username' => 'faisal_rahman',
            'unit_id' => $unit->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'activity_type' => 'CREATE_USER',
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_admin_unit_cannot_create_administrator(): void
    {
        $adminAkm = User::where('username', 'admin_akademik')->first();
        $unitAkm = Unit::where('kode_unit', 'POKJA-AKM')->first();

        $response = $this->actingAs($adminAkm)->post('/admin/users', [
            'name' => 'Hacker Admin',
            'nip' => '199901012022011999',
            'username' => 'hacker_admin',
            'email' => 'hacker@lldikti.kemdikbud.go.id',
            'password' => 'Password123!',
            'role' => 'administrator', // forbidden role for Admin Unit
            'unit_id' => $unitAkm->id,
            'is_active' => true,
        ]);

        $response->assertSessionHasErrors('role');
    }

    public function test_admin_unit_cannot_edit_user_from_other_unit(): void
    {
        $adminAkm = User::where('username', 'admin_akademik')->first();
        $staffKlb = User::where('username', 'staff_nurul')->first();

        $this->actingAs($adminAkm)->get("/admin/users/{$staffKlb->id}/edit")->assertStatus(403);
    }

    public function test_admin_unit_cannot_update_administrator_user(): void
    {
        $adminAkm = User::where('username', 'admin_akademik')->first();
        $superadmin = User::where('role', 'administrator')->first();

        $this->actingAs($adminAkm)
            ->put("/admin/users/{$superadmin->id}", [
                'name' => 'Hacked Name',
                'email' => $superadmin->email,
                'role' => 'administrator',
            ])
            ->assertStatus(403);
    }

    public function test_user_status_can_be_toggled(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $user = User::where('username', 'faisal_rahman')->first();

        if ($user) {
            $response = $this->actingAs($superadmin)->patch("/admin/users/{$user->id}/toggle-status");

            $response->assertRedirect();
            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'is_active' => false,
            ]);

            // Clean up test user
            $user->delete();
        }
    }
}
