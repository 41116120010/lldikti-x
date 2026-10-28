<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
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
        $digitsOnly = (string) preg_replace('/\s+/', '', $identity);
        $lowerIdentity = strtolower($identity);

        // Support multi-identifier (Email, NIP 18 Digit, or Username) case-insensitively across PostgreSQL & MySQL
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$lowerIdentity])
            ->orWhereRaw('LOWER(username) = ?', [$lowerIdentity])
            ->when($digitsOnly !== '' && ctype_digit($digitsOnly), function ($query) use ($digitsOnly) {
                $query->orWhere('nip', $digitsOnly);
            }, function ($query) use ($identity) {
                $query->orWhere('nip', $identity);
            })
            ->first();

        // 1. Explicit validation when identity is not registered in employee database
        if (! $user) {
            return back()->withInput($request->only('identity'))->withErrors([
                'identity' => 'Identitas (NIP, Username, atau Email) tidak ditemukan dalam data kepegawaian sistem.',
            ]);
        }

        // 2. Inactive account protection
        if (! $user->is_active) {
            return back()->withInput($request->only('identity'))->withErrors([
                'identity' => 'Akun dengan identitas tersebut berstatus non-aktif. Silakan hubungi Administrator Kepegawaian LLDIKTI Wilayah X.',
            ]);
        }

        // 3. Ensure account has a valid email address
        if (empty($user->email)) {
            return back()->withInput($request->only('identity'))->withErrors([
                'identity' => 'Akun Anda belum memiliki alamat email kedinasan terdaftar. Silakan hubungi Administrator Sistem.',
            ]);
        }

        // 4. Guard against stale config:cache where MAIL_MAILER is still 'log' in production
        $activeMailer = (string) config('mail.default', 'log');
        if (app()->environment('production') && in_array($activeMailer, ['log', 'array'], true)) {
            Log::warning('Pengiriman email lupa kata sandi tertahan karena MAIL_MAILER masih bernilai log/array di production. Pastikan menjalankan php artisan config:cache setelah mengubah .env.', [
                'mailer' => $activeMailer,
                'user_id' => $user->id,
            ]);

            return back()->withInput($request->only('identity'))->withErrors([
                'identity' => 'Konfigurasi pengiriman email server masih dalam mode log (belum disegarkan). Silakan jalankan perintah "./deploy.sh" atau "php artisan config:cache" di server.',
            ]);
        }

        // 5. Create reset token and dispatch notification via Brevo (SMTP / HTTP API)
        $token = Password::broker()->createToken($user);

        try {
            $user->sendPasswordResetNotification($token);
        } catch (\Throwable $e) {
            Log::error('Gagal mengirimkan email reset kata sandi melalui Brevo: ' . $e->getMessage(), [
                'mailer' => $activeMailer,
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'scheme' => config('mail.mailers.smtp.scheme'),
                'from' => config('mail.from.address'),
                'recipient' => $user->email,
                'exception' => get_class($e),
            ]);

            return back()->withInput($request->only('identity'))->withErrors([
                'identity' => 'Gagal menghubungi layanan pengiriman email kedinasan. Pastikan kredensial Brevo dan alamat email pengirim (MAIL_FROM_ADDRESS) telah terverifikasi.',
            ]);
        }

        // 6. Audit logging
        ActivityLogger::log(
            'AUTH_FORGOT_PASSWORD_REQUEST',
            'Mengajukan permintaan tautan atur ulang kata sandi akun.',
            User::class,
            $user->id,
            [
                'identifier_used' => $identity,
                'mailer' => $activeMailer,
            ],
            $user
        );

        // 7. Mask email address for civil servant privacy
        $maskedEmail = $this->maskEmail($user->email);

        return back()->with(
            'status',
            'Tautan atur ulang kata sandi telah dikirimkan ke email kedinasan terdaftar: ' . $maskedEmail . '. Silakan periksa kotak masuk atau folder spam Anda.'
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
