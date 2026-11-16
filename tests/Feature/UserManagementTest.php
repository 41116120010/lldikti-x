<?php

namespace Tests\Feature;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    public function test_administrator_can_view_all_users(): void
    {
        $superadmin = User::where('role', 'administrator')->first();

        $response = $this->actingAs($superadmin)->get('/admin/users');

        $response->assertStatus(200);
        $response->assertSee('Kelola Seluruh Pengguna');
        $response->assertSee('Afdalisma');
        $response->assertViewHas('userStats');
        $response->assertSee('Total Pengguna');
        $response->assertSee('Akun Aktif');
        $response->assertSee('Akun Non-Aktif');
        
        $stats = $response->viewData('userStats');
        $this->assertEquals(User::count(), $stats->total);
    }

    public function test_admin_unit_can_only_view_users_in_their_unit(): void
    {
        $unitAkm = Unit::where('kode_unit', 'POKJA-AKM')->firstOrFail();
        $adminAkm = User::where('unit_id', $unitAkm->id)->firstOrFail();
        $adminAkm->update(['role' => 'admin']);
        $staffAkm = User::where('unit_id', $unitAkm->id)->where('id', '!=', $adminAkm->id)->firstOrFail();
        $staffKlb = User::whereHas('unit', fn($q) => $q->where('kode_unit', 'POKJA-KLB'))->firstOrFail();

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
        $unitAkm = Unit::where('kode_unit', 'POKJA-AKM')->firstOrFail();
        $adminAkm = User::where('unit_id', $unitAkm->id)->firstOrFail();
        $adminAkm->update(['role' => 'admin']);

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
        $unitAkm = Unit::where('kode_unit', 'POKJA-AKM')->firstOrFail();
        $adminAkm = User::where('unit_id', $unitAkm->id)->firstOrFail();
        $adminAkm->update(['role' => 'admin']);
        $staffKlb = User::whereHas('unit', fn($q) => $q->where('kode_unit', 'POKJA-KLB'))->firstOrFail();

        $this->actingAs($adminAkm)->get("/admin/users/{$staffKlb->id}/edit")->assertStatus(403);
    }

    public function test_admin_unit_cannot_update_administrator_user(): void
    {
        $unitAkm = Unit::where('kode_unit', 'POKJA-AKM')->firstOrFail();
        $adminAkm = User::where('unit_id', $unitAkm->id)->firstOrFail();
        $adminAkm->update(['role' => 'admin']);
        $superadmin = User::where('role', 'administrator')->firstOrFail();

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

        // Own fixture. This test used to look up "faisal_rahman" — a user created
        // by an earlier test in this class. That test's transaction had already
        // rolled back by the time this one ran, so the lookup returned null, the
        // `if ($user)` guard skipped the entire body, and the toggle went untested
        // while PHPUnit only warned that the test "did not perform any assertions".
        $user = User::create([
            'unit_id' => Unit::where('kode_unit', 'POKJA-AKM')->value('id'),
            'name' => 'Pegawai Uji Status',
            'nip' => '199707072020071111',
            'username' => 'user_status_uji',
            'email' => 'user_status_uji@lldikti.test',
            'password' => Hash::make('Password123!'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $response = $this->actingAs($superadmin)->patch("/admin/users/{$user->id}/toggle-status");

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => false,
        ]);

        // A second toggle must restore the flag, so the endpoint is a true flip
        // rather than a one-way deactivate.
        $this->actingAs($superadmin)->patch("/admin/users/{$user->id}/toggle-status");

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => true,
        ]);

        // No manual cleanup: the user is rolled back with the test transaction.
    }

    public function test_administrator_can_filter_users_without_unit(): void
    {
        $superadmin = User::where('role', 'administrator')->first();

        // 1. Filter by unit_id=none should show users with unit_id null (e.g. superadmin)
        $response = $this->actingAs($superadmin)->get('/admin/users?unit_id=none');
        $response->assertStatus(200);
        $response->assertSee($superadmin->name);

        $users = $response->viewData('users');
        foreach ($users as $u) {
            $this->assertNull($u->unit_id);
        }
    }

    public function test_user_cannot_be_deleted_if_they_created_agendas(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $unit = Unit::first();

        // Create an admin user who creates an agenda
        $creator = User::create([
            'unit_id' => $unit->id,
            'name' => 'Creator User ' . uniqid(),
            'nip' => '1990' . rand(10000000, 99999999),
            'username' => 'creator_' . uniqid(),
            'email' => 'creator_' . uniqid() . '@lldikti.test',
            'password' => bcrypt('Password123!'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $agenda = \App\Models\Agenda::create([
            'created_by' => $creator->id,
            'judul_rapat' => 'Rapat Penting dari Creator ' . uniqid(),
            'slug' => 'rapat-creator-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang 1',
            'waktu_mulai' => now()->addDays(1),
            'waktu_selesai' => now()->addDays(1)->addHours(2),
            'is_all_units' => true,
            'status' => 'scheduled',
        ]);

        // Attempt to delete creator user
        $response = $this->actingAs($superadmin)->delete("/admin/users/{$creator->id}");
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $creator->id]);
        $this->assertDatabaseHas('agendas', ['id' => $agenda->id]);

        // Clean up test data
        $agenda->delete();
        $creator->delete();
    }

    public function test_deactivating_user_revokes_web_sessions_and_blocks_inactive_requests(): void
    {
        $superadmin = User::where('role', 'administrator')->firstOrFail();
        $unit = Unit::firstOrFail();

        $user = User::create([
            'unit_id' => $unit->id,
            'name' => 'Pegawai Uji Deaktivasi Sesi',
            'nip' => '199808082022081001',
            'username' => 'uji_deaktivasi_' . uniqid(),
            'email' => 'uji_deaktivasi_' . uniqid() . '@lldikti.test',
            'password' => Hash::make('Password123!'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $sessionTable = (string) config('session.table', 'sessions');
        if (Schema::hasTable($sessionTable)) {
            DB::table($sessionTable)->insert([
                'id' => 'sess_' . uniqid(),
                'user_id' => $user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHPUnit',
                'payload' => 'test-payload',
                'last_activity' => time(),
            ]);
            $this->assertDatabaseHas($sessionTable, ['user_id' => $user->id]);
        }

        $user->createToken('mobile-session-test');
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id]);

        // Deactivate via toggleStatus: must mark inactive, delete web sessions & Sanctum tokens
        $this->actingAs($superadmin)
            ->patch("/admin/users/{$user->id}/toggle-status")
            ->assertRedirect();

        $user->refresh();
        $this->assertFalse((bool) $user->is_active);
        if (Schema::hasTable($sessionTable)) {
            $this->assertDatabaseMissing($sessionTable, ['user_id' => $user->id]);
        }
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);

        // Inactive user with lingering Web session must be logged out & redirected to /login
        $webResponse = $this->actingAs($user)->get('/dashboard');
        $webResponse->assertRedirect(route('login'));
        $webResponse->assertSessionHas('error');
        $this->assertGuest();

        // Inactive user with an API token must receive 403 and have that current token deleted
        $lingeringToken = $user->createToken('lingering-token')->plainTextToken;
        $apiResponse = $this->withToken($lingeringToken)->getJson('/api/v1/profile');
        $apiResponse->assertStatus(403)->assertJsonPath('success', false);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);

        // Re-activate, re-seed session + token, and verify deactivation via PUT /admin/users/{id} also revokes both
        $this->actingAs($superadmin)->patch("/admin/users/{$user->id}/toggle-status")->assertRedirect();
        if (Schema::hasTable($sessionTable)) {
            DB::table($sessionTable)->insert([
                'id' => 'sess_put_' . uniqid(),
                'user_id' => $user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHPUnit',
                'payload' => 'test-payload-put',
                'last_activity' => time(),
            ]);
        }
        $user->createToken('mobile-put-test');

        $this->actingAs($superadmin)
            ->put("/admin/users/{$user->id}", [
                'name' => $user->name,
                'nip' => $user->nip,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role instanceof \BackedEnum ? $user->role->value : $user->role,
                'unit_id' => $user->unit_id,
                'is_active' => 0,
            ])
            ->assertRedirect();

        $this->assertFalse((bool) $user->fresh()->is_active);
        if (Schema::hasTable($sessionTable)) {
            $this->assertDatabaseMissing($sessionTable, ['user_id' => $user->id]);
        }
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
    }
}
