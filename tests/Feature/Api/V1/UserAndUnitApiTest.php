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
}

