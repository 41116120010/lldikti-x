<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Authenticate user via multi-identifier (NIP/Username) and issue Bearer token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = $request->resolveUser();

        $deviceName = (string) $request->input('device_name', 'Mobile App');
        $token = $user->createToken($deviceName)->plainTextToken;

        ActivityLogger::log(
            type: 'AUTH_API_LOGIN',
            description: "Pengguna {$user->name} berhasil login melalui API Mobile ({$deviceName}).",
            targetModel: get_class($user),
            targetId: $user->id,
            properties: [
                'device_name' => $deviceName,
                'ip' => $request->ip(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Autentikasi berhasil.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in_days' => 30,
                'user' => new UserResource($user->load('unit')),
            ],
        ]);
    }

    /**
     * Request self-service password reset link via multi-identifier (NIP 18 Digit, Username, or Email).
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $identity = trim((string) $request->validated('identity'));
        $digitsOnly = (string) preg_replace('/\s+/', '', $identity);
        $lowerIdentity = strtolower($identity);

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$lowerIdentity])
            ->orWhereRaw('LOWER(username) = ?', [$lowerIdentity])
            ->when($digitsOnly !== '' && ctype_digit($digitsOnly), function ($query) use ($digitsOnly) {
                $query->orWhere('nip', $digitsOnly);
            }, function ($query) use ($identity) {
                $query->orWhere('nip', $identity);
            })
            ->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Identitas (NIP, Username, atau Email) tidak ditemukan dalam data kepegawaian sistem.',
                'errors' => [
                    'identity' => ['Identitas (NIP, Username, atau Email) tidak ditemukan dalam data kepegawaian sistem.'],
                ],
            ], 422);
        }

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun dengan identitas tersebut berstatus non-aktif. Silakan hubungi Administrator Kepegawaian LLDIKTI Wilayah X.',
                'errors' => [
                    'identity' => ['Akun dengan identitas tersebut berstatus non-aktif. Silakan hubungi Administrator Kepegawaian LLDIKTI Wilayah X.'],
                ],
            ], 422);
        }

        if (empty($user->email)) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda belum memiliki alamat email kedinasan terdaftar. Silakan hubungi Administrator Sistem.',
                'errors' => [
                    'identity' => ['Akun Anda belum memiliki alamat email kedinasan terdaftar. Silakan hubungi Administrator Sistem.'],
                ],
            ], 422);
        }

        $activeMailer = (string) config('mail.default', 'log');
        if (app()->environment('production') && in_array($activeMailer, ['log', 'array'], true)) {
            Log::warning('Pengiriman email lupa kata sandi API tertahan karena MAIL_MAILER masih bernilai log/array di production.', [
                'mailer' => $activeMailer,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Konfigurasi pengiriman email server masih dalam mode log (belum disegarkan). Silakan hubungi Administrator Sistem.',
            ], 503);
        }

        $token = Password::broker()->createToken($user);

        try {
            $user->sendPasswordResetNotification($token);
        } catch (\Throwable $e) {
            Log::error('Gagal mengirimkan email reset kata sandi melalui API (Brevo): ' . $e->getMessage(), [
                'mailer' => $activeMailer,
                'recipient' => $user->email,
                'exception' => get_class($e),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghubungi layanan pengiriman email kedinasan. Pastikan kredensial Brevo telah terverifikasi.',
            ], 502);
        }

        ActivityLogger::log(
            type: 'AUTH_FORGOT_PASSWORD_REQUEST',
            description: 'Mengajukan permintaan tautan atur ulang kata sandi akun melalui API Mobile.',
            targetModel: User::class,
            targetId: $user->id,
            properties: [
                'identifier_used' => $identity,
                'mailer' => $activeMailer,
                'channel' => 'api',
            ],
            user: $user
        );

        $maskedEmail = $this->maskEmail($user->email);

        return response()->json([
            'success' => true,
            'message' => "Tautan atur ulang kata sandi telah dikirimkan ke email kedinasan terdaftar: {$maskedEmail}. Silakan periksa kotak masuk atau folder spam Anda.",
            'data' => [
                'masked_email' => $maskedEmail,
            ],
        ]);
    }

    /**
     * Reset user password using token and revoke all active Sanctum tokens.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                // Strict Rule AGENTS.md §3.6: Revoke all active Sanctum mobile tokens on password change
                $user->tokens()->delete();

                ActivityLogger::log(
                    type: 'AUTH_PASSWORD_RESET',
                    description: 'Berhasil mengatur ulang kata sandi melalui API Mobile dan mencabut seluruh sesi token aktif.',
                    targetModel: User::class,
                    targetId: $user->id,
                    properties: ['channel' => 'api'],
                    user: $user
                );

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Kata sandi berhasil diperbarui. Silakan masuk menggunakan kata sandi baru Anda.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Tautan atau token atur ulang kata sandi tidak valid atau telah kedaluwarsa. Silakan ajukan permohonan baru.',
            'errors' => [
                'email' => ['Tautan atau token atur ulang kata sandi tidak valid atau telah kedaluwarsa. Silakan ajukan permohonan baru.'],
            ],
        ], 422);
    }

    /**
     * Get authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('unit');

        return response()->json([
            'success' => true,
            'message' => 'Profil pengguna berhasil diambil.',
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Revoke active mobile token.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->currentAccessToken()?->delete();

        ActivityLogger::log(
            type: 'AUTH_API_LOGOUT',
            description: "Pengguna {$user->name} berhasil logout dari API Mobile.",
            targetModel: get_class($user),
            targetId: $user->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Berhasil keluar dan token dicabut.',
        ]);
    }

    /**
     * Mask email address for civil servant privacy.
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

