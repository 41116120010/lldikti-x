<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * A syntactically valid bcrypt hash of an unguessable value, used purely to
     * burn the same CPU a real verification would when the identifier is unknown.
     * It is never compared against user input successfully and grants no access.
     */
    private const DUMMY_HASH = '$2y$12$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';

    /**
     * Maximum failed attempts per identifier+IP before a temporary lockout.
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * How long the lockout lasts, in seconds.
     */
    private const DECAY_SECONDS = 60;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare inputs for validation.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->filled('login') && $this->filled('identifier')) {
            $this->merge([
                'login' => $this->input('identifier'),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required_without:identifier', 'nullable', 'string'],
            'identifier' => ['required_without:login', 'nullable', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'login' => 'NIP atau Username',
            'identifier' => 'NIP atau Username',
            'password' => 'Kata Sandi',
        ];
    }

    /**
     * Resolve and verify the authenticating user without creating a web session.
     *
     * @throws ValidationException
     */
    public function resolveUser(): User
    {
        $this->ensureIsNotRateLimited();

        $rawInput = trim((string) $this->input('login'));
        $password = (string) $this->input('password');

        $user = User::findByMultiIdentifier($rawInput, includeEmail: false);

        // 1. Kondisi: Akun tidak ditemukan dalam data kepegawaian sistem
        if ($user === null) {
            // Mitigasi Timing Attack: tetap hitung hash dummy agar waktu respon identik (~100ms)
            Hash::check($password, self::DUMMY_HASH);

            RateLimiter::hit($this->throttleKey());

            ActivityLogger::log(
                type: 'AUTH_LOGIN_FAILED',
                description: "Percobaan login gagal: Identitas [{$rawInput}] tidak terdaftar dalam sistem dari IP: {$this->ip()}.",
                targetModel: null,
                targetId: null,
                properties: [
                    'ip' => $this->ip(),
                    'user_agent' => $this->userAgent(),
                    'identifier_attempted' => $rawInput,
                    'reason' => 'USER_NOT_FOUND',
                ],
                user: null
            );

            $cleanedDigits = preg_replace('/\s+/', '', $rawInput);
            $isNipFormat = ctype_digit($cleanedDigits) && strlen($cleanedDigits) >= 9;

            $message = $isNipFormat
                ? 'Nomor Induk Pegawai (NIP) tidak terdaftar dalam data kepegawaian sistem.'
                : 'Username atau NIP tidak terdaftar dalam sistem.';

            $errors = ['login' => $message];
            if ($this->has('identifier')) {
                $errors['identifier'] = $message;
            }

            throw ValidationException::withMessages($errors);
        }

        // 2. Kondisi: Akun terdaftar tetapi berstatus non-aktif (purna tugas/mutasi)
        if (! (bool) $user->is_active) {
            // Mitigasi Timing Attack: tetap hitung hash akun autentik
            Hash::check($password, $user->getAuthPassword());

            RateLimiter::hit($this->throttleKey());

            ActivityLogger::log(
                type: 'AUTH_LOGIN_FAILED',
                description: "Percobaan login ditolak: Akun {$user->name} ([{$rawInput}]) berstatus non-aktif dari IP: {$this->ip()}.",
                targetModel: get_class($user),
                targetId: $user->id,
                properties: [
                    'ip' => $this->ip(),
                    'user_agent' => $this->userAgent(),
                    'identifier_attempted' => $rawInput,
                    'reason' => 'USER_INACTIVE',
                ],
                user: null
            );

            $message = 'Akun pegawai berstatus non-aktif. Silakan hubungi Administrator atau Subbag Kepegawaian (HKTL).';

            $errors = ['login' => $message];
            if ($this->has('identifier')) {
                $errors['identifier'] = $message;
            }

            throw ValidationException::withMessages($errors);
        }

        // 3. Kondisi: Kata sandi yang dimasukkan tidak sesuai
        if (! Hash::check($password, $user->getAuthPassword())) {
            RateLimiter::hit($this->throttleKey());

            ActivityLogger::log(
                type: 'AUTH_LOGIN_FAILED',
                description: "Percobaan login gagal: Kata sandi tidak sesuai untuk akun {$user->name} ([{$rawInput}]) dari IP: {$this->ip()}.",
                targetModel: get_class($user),
                targetId: $user->id,
                properties: [
                    'ip' => $this->ip(),
                    'user_agent' => $this->userAgent(),
                    'identifier_attempted' => $rawInput,
                    'reason' => 'WRONG_PASSWORD',
                ],
                user: null
            );

            $message = 'Kata sandi yang Anda masukkan tidak sesuai. Silakan periksa kembali kata sandi Anda.';

            $errors = ['password' => $message];

            throw ValidationException::withMessages($errors);
        }

        RateLimiter::clear($this->throttleKey());

        return $user;
    }

    /**
     * Attempt to authenticate the request's credentials into a web session.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $user = $this->resolveUser();
        $remember = $this->boolean('remember');

        Auth::login($user, $remember);
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        $message = trans('auth.throttle', [
            'seconds' => $seconds,
            'minutes' => ceil($seconds / 60),
        ]);

        $errors = ['login' => $message];
        if ($this->has('identifier')) {
            $errors['identifier'] = $message;
        }

        throw ValidationException::withMessages($errors);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        $cleaned = preg_replace('/\s+/', '', $this->input('login', ''));

        return Str::transliterate(Str::lower($cleaned).'|'.$this->ip());
    }
}
