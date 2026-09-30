<?php

namespace Tests\Feature\Auth;

use App\Models\ActivityLog;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MultiIdentifierAuthenticationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Masuk ke Akun Anda');
        $response->assertSee('NIP atau Username');
    }

    public function test_users_can_authenticate_using_username(): void
    {
        $user = User::where('role', 'administrator')->firstOrFail();

        $response = $this->post('/login', [
            'login' => $user->username,
            'password' => 'Password123!',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');

        // Verify activity log recorded
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'activity_type' => 'AUTH_LOGIN',
        ]);
    }

    public function test_users_can_authenticate_using_nip(): void
    {
        $user = User::where('role', 'staff')->firstOrFail();

        $response = $this->post('/login', [
            'login' => $user->nip,
            'password' => 'Password123!',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::where('role', 'administrator')->firstOrFail();

        $response = $this->post('/login', [
            'login' => $user->username,
            'password' => 'WrongPassword123!',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('login');
    }

    public function test_inactive_user_cannot_login(): void
    {
        $inactiveUser = User::create([
            'name' => 'Pegawai Non-Aktif',
            'nip' => '199001012015011999',
            'username' => 'inactive_user',
            'email' => 'inactive@lldikti.kemdikbud.go.id',
            'password' => Hash::make('Password123!'),
            'role' => 'staff',
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'login' => 'inactive_user',
            'password' => 'Password123!',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('login');

        // Cleanup
        $inactiveUser->delete();
    }

    public function test_user_can_logout(): void
    {
        $user = User::where('role', 'administrator')->firstOrFail();

        $this->actingAs($user);

        $response = $this->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'activity_type' => 'AUTH_LOGOUT',
        ]);
    }

    public function test_users_can_authenticate_using_nip_with_whitespace(): void
    {
        $user = User::where('role', 'staff')->firstOrFail();

        // Simulate clipboard copy paste with spaces: " 19940214 202012 1 004 "
        $spacedNip = ' ' . substr($user->nip, 0, 8) . ' ' . substr($user->nip, 8, 6) . ' ' . substr($user->nip, 14, 1) . ' ' . substr($user->nip, 15) . ' ';
        $response = $this->post('/login', [
            'login' => $spacedNip,
            'password' => 'Password123!',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');
    }

    public function test_authenticated_user_accessing_login_page_redirects_to_dashboard(): void
    {
        $user = User::where('role', 'administrator')->firstOrFail();

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect('/dashboard');
    }
}
