<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    /**
     * Handle an incoming new password request.
     */
    public function store(ResetPasswordRequest $request): RedirectResponse
    {
        // Broker attempts to reset the user's password using the token
        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                // Strict Rule AGENTS.md §3.6: Revoke all active Sanctum mobile tokens on password change
                $user->tokens()->delete();

                // Audit Trail
                ActivityLogger::log(
                    'AUTH_PASSWORD_RESET',
                    'Berhasil mengatur ulang kata sandi melalui tautan email pemulihan.',
                    User::class,
                    $user->id,
                    null,
                    $user
                );

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with(
                'success',
                'Kata sandi berhasil diperbarui. Silakan masuk menggunakan kata sandi baru Anda.'
            );
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors([
                'email' => 'Tautan atur ulang kata sandi tidak valid atau telah kedaluwarsa. Silakan ajukan permohonan baru.',
            ]);
    }
}
