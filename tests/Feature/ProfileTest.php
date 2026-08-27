<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    public function test_guest_cannot_access_profile_page(): void
    {
        $response = $this->get(route('profile.edit'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_profile_edit_page(): void
    {
        $staff = User::where('username', 'staff_rizky')->first();

        $response = $this->actingAs($staff)->get(route('profile.edit'));

        $response->assertStatus(200);
        $response->assertSee('Pengaturan Profil Akun');
        $response->assertSee($staff->name);
        $response->assertSee($staff->nip);
    }

    public function test_user_can_update_name_and_email(): void
    {
        $staff = User::where('username', 'staff_nurul')->first();
        $originalName = $staff->name;
        $originalEmail = $staff->email;

        $response = $this->actingAs($staff)->put(route('profile.update'), [
            'name' => 'Nurul Hidayah S.Kom., M.T.I.',
            'email' => 'nurul.updated@lldikti.kemdikbud.go.id',
            'phone' => '081299998888',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('success', 'Profil akun Anda berhasil diperbarui.');

        $staff->refresh();
        $this->assertEquals('Nurul Hidayah S.Kom., M.T.I.', $staff->name);
        $this->assertEquals('nurul.updated@lldikti.kemdikbud.go.id', $staff->email);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $staff->id,
            'activity_type' => 'UPDATE_PROFILE',
        ]);

        // Restore original values
        $staff->update([
            'name' => $originalName,
            'email' => $originalEmail,
        ]);
    }

    public function test_user_can_update_password_with_valid_current_password(): void
    {
        $staff = User::where('username', 'staff_ahmad')->first();
        $staff->update(['password' => Hash::make('Password123!')]);

        $response = $this->actingAs($staff)->put(route('profile.update'), [
            'name' => $staff->name,
            'email' => $staff->email,
            'current_password' => 'Password123!',
            'password' => 'NewSecretPassword999',
            'password_confirmation' => 'NewSecretPassword999',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('success');

        $staff->refresh();
        $this->assertTrue(Hash::check('NewSecretPassword999', $staff->password));

        // Restore password for other tests
        $staff->update(['password' => Hash::make('Password123!')]);
    }

    public function test_user_cannot_update_password_with_invalid_current_password(): void
    {
        $staff = User::where('username', 'staff_rizky')->first();
        $staff->update(['password' => Hash::make('Password123!')]);

        $response = $this->actingAs($staff)->put(route('profile.update'), [
            'name' => $staff->name,
            'email' => $staff->email,
            'current_password' => 'WrongCurrentPassword',
            'password' => 'NewSecretPassword999',
            'password_confirmation' => 'NewSecretPassword999',
        ]);

        $response->assertSessionHasErrors('current_password');

        $staff->refresh();
        $this->assertTrue(Hash::check('Password123!', $staff->password));
    }
}
