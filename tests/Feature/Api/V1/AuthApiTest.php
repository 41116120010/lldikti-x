<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_can_login_with_valid_nip_and_receives_bearer_token(): void
    {
        $user = User::where('is_active', true)->whereNotNull('nip')->first();
        $this->assertNotNull($user);

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => $user->nip,
            'password' => 'Password123!',
            'device_name' => 'Redmi Note 12',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.name', $user->name);

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_user_can_login_with_valid_username_and_receives_bearer_token(): void
    {
        $user = User::where('is_active', true)->whereNotNull('username')->first();
        $this->assertNotNull($user);

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => $user->username,
            'password' => 'Password123!',
            'device_name' => 'Pixel 7',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.id', $user->id);
    }

    public function test_user_can_login_with_case_insensitive_username(): void
    {
        $user = User::where('is_active', true)->whereNotNull('username')->first();
        $this->assertNotNull($user);

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => strtoupper($user->username),
            'password' => 'Password123!',
            'device_name' => 'Pixel 7',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.id', $user->id);
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        $user = User::where('is_active', true)->first();
        $this->assertNotNull($user);

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => $user->username ?? $user->nip,
            'password' => 'wrong-password-123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['identifier']);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::where('is_active', true)->first();
        $this->assertNotNull($user);

        // Temporarily deactivate user
        $user->update(['is_active' => false]);

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => $user->username ?? $user->nip,
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['identifier']);
    }

    public function test_authenticated_user_can_access_me_profile(): void
    {
        $user = User::where('is_active', true)->first();
        $this->assertNotNull($user);

        $token = $user->createToken('Test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', $user->name);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_user_can_logout_and_revoke_token(): void
    {
        $user = User::where('is_active', true)->first();
        $this->assertNotNull($user);

        $token = $user->createToken('Mobile')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->app['auth']->forgetGuards();

        // Token should now be invalid
        $retry = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');

        $retry->assertStatus(401);
    }

    public function test_user_can_update_profile_via_api(): void
    {
        $user = User::where('is_active', true)->first();
        $this->assertNotNull($user);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', [
                'name' => 'Nama Pengguna Terupdate',
                'email' => $user->email,
                'phone' => '081234567890',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Nama Pengguna Terupdate')
            ->assertJsonPath('data.phone', '081234567890');

        $this->assertEquals('Nama Pengguna Terupdate', $user->fresh()->name);
    }

    public function test_user_can_change_password_and_old_tokens_are_revoked(): void
    {
        $user = User::where('is_active', true)->first();
        $this->assertNotNull($user);

        // Set a known initial password
        $user->update(['password' => bcrypt('OldPassword123!')]);

        // Create an active token for this user
        $oldToken = $user->createToken('MobileDevice')->plainTextToken;
        $this->assertEquals(1, $user->tokens()->count());

        $response = $this->withHeader('Authorization', "Bearer {$oldToken}")
            ->putJson('/api/v1/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'current_password' => 'OldPassword123!',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        // All active tokens must be revoked
        $this->assertEquals(0, $user->tokens()->count());

        // Old token must now be rejected
        $this->app['auth']->forgetGuards();
        $retry = $this->withHeader('Authorization', "Bearer {$oldToken}")
            ->getJson('/api/v1/auth/me');

        $retry->assertStatus(401);
    }
}

