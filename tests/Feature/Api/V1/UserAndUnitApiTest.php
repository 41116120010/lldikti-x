<?php

namespace Tests\Feature\Api\V1;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserAndUnitApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_administrator_can_list_users_via_api(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $this->assertNotNull($admin);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/users');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'nip',
                        'username',
                        'role' => ['value', 'label'],
                        'is_active',
                    ],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_deactivating_user_revokes_all_active_tokens(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $staff = User::where('role', 'staff')->first();
        $this->assertNotNull($admin);
        $this->assertNotNull($staff);

        // Staff creates a token
        $token = $staff->createToken('Mobile')->plainTextToken;
        $this->assertEquals(1, $staff->tokens()->count());

        // Admin toggles staff account to inactive
        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/users/{$staff->id}/toggle-status");

        $response->assertOk()
            ->assertJsonPath('success', true);

        // Staff's tokens must now be revoked
        $this->assertEquals(0, $staff->tokens()->count());

        // Staff's token should fail auth
        $this->app['auth']->forgetGuards();
        $retry = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');

        $retry->assertStatus(401);
    }

    public function test_authenticated_user_can_list_units_via_api(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $this->assertNotNull($admin);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/units?all=true');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'nama_unit',
                        'kode_unit',
                        'is_active',
                    ],
                ],
            ]);
    }

    public function test_administrator_can_create_and_toggle_unit(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $this->assertNotNull($admin);

        $createResponse = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/units', [
                'nama_unit' => 'Unit Kerja Riset dan Publikasi Ilmiah',
                'kode_unit' => 'UK-RPI-99',
                'deskripsi' => 'Pengelolaan riset dan inovasi LLDIKTI',
                'is_active' => true,
            ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.kode_unit', 'UK-RPI-99');

        $unitId = $createResponse->json('data.id');

        $toggleResponse = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/units/{$unitId}/toggle-status");

        $toggleResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_active', false);
    }

    public function test_administrator_can_create_user_via_api(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $unit = Unit::first();
        $this->assertNotNull($admin);
        $this->assertNotNull($unit);

        $payload = [
            'name' => 'Pegawai Baru Pengujian API',
            'nip' => '199501012026011999',
            'username' => 'pegawai_baru_99',
            'email' => 'pegawai99@lldikti.test',
            'password' => 'Password123!',
            'role' => 'staff',
            'unit_id' => $unit->id,
            'phone' => '081298765432',
            'is_active' => true,
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/users', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nip', $payload['nip'])
            ->assertJsonPath('data.username', $payload['username']);

        $this->assertDatabaseHas('users', [
            'nip' => $payload['nip'],
            'username' => $payload['username'],
        ]);
    }

    public function test_authorized_user_can_view_user_detail_via_api(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $staff = User::where('role', 'staff')->first();
        $this->assertNotNull($admin);
        $this->assertNotNull($staff);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/users/{$staff->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $staff->id)
            ->assertJsonPath('data.name', $staff->name);
    }

    public function test_administrator_can_update_user_via_api(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $staff = User::where('role', 'staff')->first();
        $this->assertNotNull($admin);
        $this->assertNotNull($staff);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/users/{$staff->id}", [
                'name' => 'Nama Staff Terupdate',
                'nip' => $staff->nip,
                'username' => $staff->username,
                'email' => $staff->email,
                'role' => $staff->role,
                'unit_id' => $staff->unit_id,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Nama Staff Terupdate');

        $this->assertEquals('Nama Staff Terupdate', $staff->fresh()->name);
    }

    public function test_administrator_can_delete_user_via_api(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $this->assertNotNull($admin);

        $disposableUser = User::create([
            'name' => 'User Disposable Hapus',
            'nip' => '199999999999999999',
            'username' => 'disposable_user_99',
            'email' => 'disposable99@lldikti.test',
            'password' => bcrypt('password123'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/users/{$disposableUser->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('users', [
            'id' => $disposableUser->id,
        ]);
    }

    public function test_authorized_user_can_view_unit_detail_via_api(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $unit = Unit::first();
        $this->assertNotNull($admin);
        $this->assertNotNull($unit);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/units/{$unit->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $unit->id)
            ->assertJsonPath('data.nama_unit', $unit->nama_unit);
    }

    public function test_administrator_can_update_unit_via_api(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $this->assertNotNull($admin);

        $disposableUnit = Unit::create([
            'nama_unit' => 'Unit Sementara Sebelum Update',
            'kode_unit' => 'UK-SEMENTARA',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/units/{$disposableUnit->id}", [
                'nama_unit' => 'Unit Setelah Diperbarui',
                'kode_unit' => 'UK-PERBAIKI',
                'is_active' => true,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nama_unit', 'Unit Setelah Diperbarui');

        $this->assertEquals('Unit Setelah Diperbarui', $disposableUnit->fresh()->nama_unit);
    }

    public function test_administrator_can_delete_unit_via_api(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $this->assertNotNull($admin);

        $disposableUnit = Unit::create([
            'nama_unit' => 'Unit Untuk Dihapus',
            'kode_unit' => 'UK-HAPUS-99',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/units/{$disposableUnit->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('units', [
            'id' => $disposableUnit->id,
        ]);
    }

    public function test_user_filtering_and_search_via_api(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $this->assertNotNull($admin);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/users?role=staff&status=active');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }

    public function test_user_list_includes_telemetry_summary_for_bento_cards(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $this->assertNotNull($admin);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/users');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'meta',
                'summary' => [
                    'total_users',
                    'active_users',
                    'inactive_users',
                    'admin_users',
                    'staff_users',
                    'active_units',
                    'attendance_rate',
                    'attendance_rate_label',
                    'attendance_rate_subtext',
                ],
            ]);

        $this->assertGreaterThan(0, $response->json('summary.total_users'));
        $this->assertGreaterThan(0, $response->json('summary.active_units'));
        $this->assertNotEmpty($response->json('summary.attendance_rate_label'));
        $this->assertNotEmpty($response->json('summary.attendance_rate_subtext'));
    }

    public function test_user_filtering_with_tolerant_inputs(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $this->assertNotNull($admin);

        // 1. Tolerant status filter: 'aktif'
        $activeRes = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/users?status=aktif');
        $activeRes->assertOk();
        foreach ($activeRes->json('data') as $user) {
            $this->assertTrue($user['is_active']);
        }

        // 2. Tolerant role filter: 'Admin Unit'
        $adminUnitRes = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/users?role=Admin%20Unit');
        $adminUnitRes->assertOk();
        foreach ($adminUnitRes->json('data') as $user) {
            $this->assertEquals('admin', $user['role']['value']);
        }
    }

    public function test_create_user_with_tolerant_inputs_and_default_password(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $unit = Unit::first();
        $this->assertNotNull($admin);
        $this->assertNotNull($unit);

        // Mobile form payload: no password provided, username with '@', unit as name string, status 'Aktif', role 'Staff'
        $payload = [
            'name' => 'ASN Toleran Form Baru',
            'nip' => '199602022026021888',
            'username' => '@asntoleran88',
            'email' => 'toleran88@lldikti.test',
            'role' => 'Staff',
            'unit' => $unit->nama_unit,
            'status' => 'Aktif',
            'phone' => '-',
            'jabatan' => 'Pranata Humas Ahli Pertama',
            'pangkat_golongan' => 'Penata Muda (Gol. III/a)',
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/users', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.username', 'asntoleran88')
            ->assertJsonPath('data.unit_id', $unit->id)
            ->assertJsonPath('data.role.value', 'staff')
            ->assertJsonPath('data.phone', null)
            ->assertJsonPath('data.is_active', true);

        // Verify default civil service password 'Password123!' works for login
        $this->app['auth']->forgetGuards();
        $loginRes = $this->postJson('/api/v1/auth/login', [
            'login' => 'asntoleran88',
            'password' => 'Password123!',
        ]);
        $loginRes->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_update_user_with_tolerant_inputs_and_omitted_nip(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $targetStaff = User::where('role', 'staff')->first();
        $this->assertNotNull($admin);
        $this->assertNotNull($targetStaff);

        $originalNip = $targetStaff->nip;

        // Mobile update payload: username with '@', omitted nip, unit as string, role 'Staff', status 'Aktif'
        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/users/{$targetStaff->id}", [
                'name' => 'Nama Staff Diperbarui Toleran',
                'username' => '@staff_toleran_edit',
                'email' => $targetStaff->email,
                'role' => 'Staff',
                'status' => 'Aktif',
                'unit' => $targetStaff->unit?->nama_unit ?? Unit::first()->nama_unit,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Nama Staff Diperbarui Toleran')
            ->assertJsonPath('data.username', 'staff_toleran_edit')
            ->assertJsonPath('data.nip', $originalNip);

        // Check NIP was safely preserved in database
        $this->assertEquals($originalNip, $targetStaff->fresh()->nip);
    }

    public function test_admin_unit_can_view_own_unit_detail_but_forbidden_on_other_unit(): void
    {
        $unit = Unit::first();
        $this->assertNotNull($unit);
        $otherUnit = Unit::where('id', '!=', $unit->id)->first();
        $this->assertNotNull($otherUnit);

        $adminUnitUser = User::where('role', 'staff')->first();
        $this->assertNotNull($adminUnitUser);
        $adminUnitUser->update([
            'role' => 'admin',
            'unit_id' => $unit->id,
        ]);

        $ownUnitId = $unit->id;

        // 1. Admin Unit accessing own unit -> 200 OK
        $ownRes = $this->actingAs($adminUnitUser->fresh(), 'sanctum')
            ->getJson("/api/v1/units/{$ownUnitId}");
        $ownRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $ownUnitId);

        // 2. Admin Unit accessing other unit -> 403 Forbidden
        $otherRes = $this->actingAs($adminUnitUser->fresh(), 'sanctum')
            ->getJson("/api/v1/units/{$otherUnit->id}");
        $otherRes->assertStatus(403);
    }

    public function test_unit_list_includes_summary_block_and_supports_tolerant_status(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $this->assertNotNull($admin);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/units?status=aktif');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'meta',
                'summary' => [
                    'total_units',
                    'active_units',
                    'inactive_units',
                    'total_users_assigned',
                ],
            ]);

        $this->assertGreaterThan(0, $response->json('summary.total_units'));
        $this->assertGreaterThan(0, $response->json('summary.active_units'));
    }

    public function test_admin_unit_can_fetch_cross_unit_agenda_candidates_via_api(): void
    {
        $unit = Unit::first();
        $this->assertNotNull($unit);

        $adminUnitUser = User::where('role', 'staff')->first();
        $this->assertNotNull($adminUnitUser);
        $adminUnitUser->update([
            'role' => 'admin',
            'unit_id' => $unit->id,
        ]);

        $totalActiveAcrossAllUnits = User::where('is_active', true)->count();

        $res = $this->actingAs($adminUnitUser->fresh(), 'sanctum')
            ->getJson('/api/v1/users/candidates');

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', $totalActiveAcrossAllUnits);
    }
}


