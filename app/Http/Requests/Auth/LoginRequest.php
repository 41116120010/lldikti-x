<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
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
        if (!$this->filled('login') && $this->filled('identifier')) {
            $this->merge([
                'login' => $this->input('identifier'),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
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
     * @throws \Illuminate\Validation\ValidationException
     */
    public function resolveUser(): User
    {
        $this->ensureIsNotRateLimited();

        $rawInput = trim($this->input('login'));
        $password = (string) $this->input('password');

        // Check if raw input stripped of spaces consists solely of digits (NIP format)
        $digitsOnly = preg_replace('/\s+/', '', $rawInput);
        if ($digitsOnly !== '' && ctype_digit($digitsOnly)) {
            $field = 'nip';
            $loginValue = $digitsOnly;
        } else {
            $field = 'username';
            $loginValue = $rawInput;
        }

        $user = User::where($field, $loginValue)->first();

        /*
         * Always run a real hash comparison, even when no account matches.
         *
         * Auth::attempt() short-circuits when the identifier is unknown, so it
         * returns in ~1ms for a bad NIP but takes ~100ms for a valid one. That gap
         * is trivially measurable and lets an attacker enumerate which NIPs and
         * usernames exist in the agency without ever seeing an error message.
         * Verifying against a throwaway bcrypt hash when $user is null keeps the
         * cost identical on both paths.
         */
        $hash = $user?->getAuthPassword() ?? self::DUMMY_HASH;
        $passwordMatches = Hash::check($password, $hash);

        $isActive = (bool) ($user?->is_active ?? false);

        if ($user === null || ! $passwordMatches || ! $isActive) {
            RateLimiter::hit($this->throttleKey());

            /*
             * One message for every failure mode: unknown identifier, wrong
             * password, and deactivated account. Distinguishing them would tell an
             * attacker which NIPs are registered and which are active, and the
             * deactivation hint is an invitation to probe for a way to re-enable
             * an account.
             */
            $errors = ['login' => 'Kredensial tidak valid atau akun sedang dinonaktifkan.'];
            if ($this->has('identifier')) {
                $errors['identifier'] = 'Kredensial tidak valid atau akun sedang dinonaktifkan.';
            }

            throw ValidationException::withMessages($errors);
        }

        RateLimiter::clear($this->throttleKey());

        return $user;
    }

    /**
     * Attempt to authenticate the request's credentials into a web session.
     *
     * @throws \Illuminate\Validation\ValidationException
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
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
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
