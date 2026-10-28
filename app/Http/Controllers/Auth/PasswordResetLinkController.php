<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     */
    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        $identity = trim((string) $request->validated('identity'));

        // Support multi-identifier (Email, NIP 18 Digit, or Username)
        $user = User::query()
            ->where('email', $identity)
            ->orWhere('nip', $identity)
            ->orWhere('username', $identity)
            ->first();

        // 1. Inactive account protection
        if ($user && ! $user->is_active) {
            return back()->withInput($request->only('identity'))->withErrors([
                'identity' => 'Akun dengan identitas tersebut berstatus non-aktif. Silakan hubungi Administrator Kepegawaian LLDIKTI Wilayah X.',
            ]);
        }

        // 2. Account found and active
        if ($user) {
            if (empty($user->email)) {
                return back()->withInput($request->only('identity'))->withErrors([
                    'identity' => 'Akun Anda belum memiliki alamat email kedinasan terdaftar. Silakan hubungi Administrator Sistem.',
                ]);
            }

            // Create reset token and trigger notification (via Brevo SMTP)
            $token = Password::broker()->createToken($user);
            $user->sendPasswordResetNotification($token);

            // Audit logging
            ActivityLogger::log(
                'AUTH_FORGOT_PASSWORD_REQUEST',
                'Mengajukan permintaan tautan atur ulang kata sandi akun.',
                User::class,
                $user->id,
                ['identifier_used' => $identity],
                $user
            );

            // Mask email address for civil servant privacy
            $maskedEmail = $this->maskEmail($user->email);

            return back()->with(
                'status',
                'Tautan atur ulang kata sandi telah dikirimkan ke email kedinasan terdaftar: ' . $maskedEmail . '. Silakan periksa kotak masuk atau folder spam Anda.'
            );
        }

        // 3. Fallback anti-enumeration response
        return back()->with(
            'status',
            'Jika data kredensial terdaftar di sistem, tautan atur ulang kata sandi telah dikirimkan ke email kedinasan terkait.'
        );
    }

    /**
     * Mask email address for privacy (e.g. a***a@lldiktiwilayahx.kemdiktisaintek.go.id).
     */
    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email, 2);
        if (count($parts) < 2) {
            return $email;
        }

        $name = $parts[0];
        $domain = $parts[1];
        $len = strlen($name);

        if ($len <= 2) {
            $maskedName = substr($name, 0, 1) . '***';
        } else {
            $maskedName = substr($name, 0, 1) . str_repeat('*', max($len - 2, 3)) . substr($name, -1);
        }

        return $maskedName . '@' . $domain;
    }
}
