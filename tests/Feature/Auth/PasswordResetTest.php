<?php

namespace Tests\Feature\Auth;

use App\Models\ActivityLog;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::where('is_active', true)->whereNotNull('email')->firstOrFail();
    }

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertStatus(200);
        $response->assertSee('Lupa Kata Sandi?');
        $response->assertSee('NIP, Username, atau Email Kedinasan');
        $response->assertSee('Kirim Tautan Atur Ulang');
    }

    public function test_reset_link_can_be_requested_using_email(): void
    {
        Notification::fake();

        $response = $this->post(route('password.email'), [
            'identity' => $this->user->email,
        ]);

        $response->assertSessionHas('status');
        Notification::assertSentTo($this->user, ResetPasswordNotification::class);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'activity_type' => 'AUTH_FORGOT_PASSWORD_REQUEST',
        ]);
    }

    public function test_reset_link_can_be_requested_using_nip(): void
    {
        Notification::fake();

        $response = $this->post(route('password.email'), [
            'identity' => $this->user->nip,
        ]);

        $response->assertSessionHas('status');
        Notification::assertSentTo($this->user, ResetPasswordNotification::class);
    }

    public function test_reset_link_can_be_requested_using_username(): void
    {
        Notification::fake();

        $response = $this->post(route('password.email'), [
            'identity' => $this->user->username,
        ]);

        $response->assertSessionHas('status');
        Notification::assertSentTo($this->user, ResetPasswordNotification::class);
    }

    public function test_reset_link_cannot_be_requested_for_inactive_user(): void
    {
        Notification::fake();

        $inactiveUser = User::factory()->create([
            'is_active' => false,
            'email' => 'nonaktif@lldiktiwilayahx.kemdiktisaintek.go.id',
            'username' => 'nonaktif_user',
            'nip' => '199901012020011999',
        ]);

        $response = $this->post(route('password.email'), [
            'identity' => $inactiveUser->nip,
        ]);

        $response->assertSessionHasErrors('identity');
        Notification::assertNothingSent();
    }

    public function test_reset_link_request_for_unknown_user_returns_safe_generic_response(): void
    {
        Notification::fake();

        $response = $this->post(route('password.email'), [
            'identity' => 'unknown_nonexistent_user',
        ]);

        $response->assertSessionHas('status');
        Notification::assertNothingSent();
    }

    public function test_reset_password_screen_can_be_rendered_with_token(): void
    {
        $response = $this->get(route('password.reset', [
            'token' => 'sample-valid-token-12345',
            'email' => $this->user->email,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Atur Ulang Kata Sandi');
        $response->assertSee('Kata Sandi Baru');
        $response->assertSee('Konfirmasi Kata Sandi Baru');
        $response->assertSee($this->user->email);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $token = Password::broker()->createToken($this->user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $this->user->email,
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        $this->assertTrue(Hash::check('NewSecurePassword123!', $this->user->fresh()->password));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'activity_type' => 'AUTH_PASSWORD_RESET',
        ]);
    }

    public function test_sanctum_tokens_are_revoked_upon_password_reset(): void
    {
        // Issue active mobile Sanctum token
        $this->user->createToken('Mobile App Device');
        $this->assertEquals(1, $this->user->tokens()->count());

        $token = Password::broker()->createToken($this->user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $this->user->email,
            'password' => 'BrandNewPassword789!',
            'password_confirmation' => 'BrandNewPassword789!',
        ]);

        $response->assertRedirect(route('login'));

        // AGENTS.md §3.6 Rule: Mobile tokens must be strictly revoked
        $this->assertEquals(0, $this->user->fresh()->tokens()->count());
    }

    public function test_user_can_authenticate_with_new_password_after_reset(): void
    {
        $token = Password::broker()->createToken($this->user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $this->user->email,
            'password' => 'FreshCivilServantPass!',
            'password_confirmation' => 'FreshCivilServantPass!',
        ]);

        $loginResponse = $this->post(route('login.attempt'), [
            'login' => $this->user->username,
            'password' => 'FreshCivilServantPass!',
        ]);

        $this->assertAuthenticatedAs($this->user);
        $loginResponse->assertRedirect(route('dashboard'));
    }

    public function test_password_cannot_be_reset_with_invalid_token(): void
    {
        $response = $this->post(route('password.update'), [
            'token' => 'invalid-token-string',
            'email' => $this->user->email,
            'password' => 'SomeValidPassword123!',
            'password_confirmation' => 'SomeValidPassword123!',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_login_page_contains_direct_link_to_password_request(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee(route('password.request'));
    }
}
