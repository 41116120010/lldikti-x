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
}
