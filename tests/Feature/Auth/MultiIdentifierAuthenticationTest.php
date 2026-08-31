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
        $user = User::where('username', 'superadmin')->first();

        $response = $this->post('/login', [
            'login' => 'superadmin',
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
        $user = User::where('nip', '199402142020121004')->first();

        $response = $this->post('/login', [
            'login' => '199402142020121004',
            'password' => 'Password123!',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $response = $this->post('/login', [
            'login' => 'superadmin',
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
        $user = User::where('username', 'superadmin')->first();

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
        $user = User::where('nip', '199402142020121004')->first();

        // Simulate clipboard copy paste with spaces: " 19940214 202012 1 004 "
        $response = $this->post('/login', [
            'login' => ' 19940214 202012 1 004 ',
            'password' => 'Password123!',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');
    }

    public function test_authenticated_user_accessing_login_page_redirects_to_dashboard(): void
    {
        $user = User::where('username', 'superadmin')->first();

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect('/dashboard');
    }
}
