<?php

namespace Tests\Feature\Api\V1;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChangePasswordApiTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::where('is_active', true)->firstOrFail();
        $this->user->password = Hash::make('Password123!');
        $this->user->save();
    }

    public function test_user_can_change_password_with_valid_credentials_and_receives_rotated_token(): void
    {
        // 1. Initial Login to obtain an active token
        $loginRes = $this->postJson('/api/v1/auth/login', [
            'identifier' => $this->user->nip ?: $this->user->username,
            'password' => 'Password123!',
            'device_name' => 'Initial Device',
        ]);
        $loginRes->assertOk();
        $oldToken = $loginRes->json('data.token');

        // 2. Change Password using the active token
        $response = $this->withHeader('Authorization', 'Bearer '.$oldToken)
            ->putJson('/api/v1/profile/password', [
                'current_password' => 'Password123!',
                'password' => 'NewSecurePass456!',
                'password_confirmation' => 'NewSecurePass456!',
                'device_name' => 'Pixel 8 Pro',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.id', $this->user->id);

        $newToken = $response->json('data.token');
        $this->assertNotEmpty($newToken);
        $this->assertNotEquals($oldToken, $newToken);

        $this->app['auth']->forgetGuards();

        // 3. Verify Old Token is strictly revoked (AGENTS.md Rule 3.6)
        $unauthCheck = $this->withHeader('Authorization', 'Bearer '.$oldToken)
            ->getJson('/api/v1/auth/me');
        $unauthCheck->assertStatus(401);

        $this->app['auth']->forgetGuards();

        // 4. Verify New Rotated Token works seamlessly
        $authCheck = $this->withHeader('Authorization', 'Bearer '.$newToken)
            ->getJson('/api/v1/auth/me');
        $authCheck->assertOk()
            ->assertJsonPath('data.id', $this->user->id);

        // 5. Verify Password in database can be used for new login
        $newLogin = $this->postJson('/api/v1/auth/login', [
            'identifier' => $this->user->nip ?: $this->user->username,
            'password' => 'NewSecurePass456!',
            'device_name' => 'Next Session',
        ]);
        $newLogin->assertOk();

        // 6. Verify Audit Trail is recorded with AUTH_CHANGE_PASSWORD
        $log = ActivityLog::where('user_id', $this->user->id)
            ->where('activity_type', 'AUTH_CHANGE_PASSWORD')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals(ActivityLog::CATEGORY_LOGIN, ActivityLog::resolveCategory($log->activity_type));
    }

    public function test_user_can_change_password_using_mobile_aliases(): void
    {
        Sanctum::actingAs($this->user);

        // Mobile aliases: new_password and confirm_password
        $response = $this->putJson('/api/v1/profile/password', [
            'current_password' => 'Password123!',
            'new_password' => 'MobilePass789!',
            'confirm_password' => 'MobilePass789!',
            'device_name' => 'SIPERAPAT Android',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('MobilePass789!', $this->user->fresh()->password));
    }

    public function test_user_can_change_password_via_auth_alias_route(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/v1/auth/password', [
            'current_password' => 'Password123!',
            'password' => 'AliasRoutePass456!',
            'password_confirmation' => 'AliasRoutePass456!',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('AliasRoutePass456!', $this->user->fresh()->password));
    }

    public function test_user_cannot_change_password_with_incorrect_current_password(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/v1/profile/password', [
            'current_password' => 'WrongCurrentPassword!',
            'password' => 'NewSecurePass456!',
            'password_confirmation' => 'NewSecurePass456!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_user_cannot_change_password_with_same_as_current_password(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/v1/profile/password', [
            'current_password' => 'Password123!',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_user_cannot_change_password_with_weak_or_invalid_complexity(): void
    {
        Sanctum::actingAs($this->user);

        // Case 1: Too short (< 8 chars)
        $resShort = $this->putJson('/api/v1/profile/password', [
            'current_password' => 'Password123!',
            'password' => 'Ab1!',
            'password_confirmation' => 'Ab1!',
        ]);
        $resShort->assertStatus(422)->assertJsonValidationErrors(['password']);

        // Case 2: No mixed-case (lowercase only with numbers)
        $resLowerOnly = $this->putJson('/api/v1/profile/password', [
            'current_password' => 'Password123!',
            'password' => 'securepass123',
            'password_confirmation' => 'securepass123',
        ]);
        $resLowerOnly->assertStatus(422)->assertJsonValidationErrors(['password']);

        // Case 3: No numbers
        $resNoNumbers = $this->putJson('/api/v1/profile/password', [
            'current_password' => 'Password123!',
            'password' => 'SecurePassOnly',
            'password_confirmation' => 'SecurePassOnly',
        ]);
        $resNoNumbers->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_user_cannot_change_password_with_unmatched_confirmation(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/v1/profile/password', [
            'current_password' => 'Password123!',
            'password' => 'NewSecurePass456!',
            'password_confirmation' => 'DoesNotMatch789!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_unauthenticated_user_cannot_access_change_password_endpoint(): void
    {
        $response = $this->putJson('/api/v1/profile/password', [
            'current_password' => 'Password123!',
            'password' => 'NewSecurePass456!',
            'password_confirmation' => 'NewSecurePass456!',
        ]);

        $response->assertStatus(401);
    }
}
