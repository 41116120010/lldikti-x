<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Prepare input data for validation, normalizing mobile client aliases.
     */
    protected function prepareForValidation(): void
    {
        $merge = [];

        // Support mobile client alias: new_password -> password
        if ($this->has('new_password') && ! $this->has('password')) {
            $merge['password'] = $this->input('new_password');
        }

        // Support mobile client alias: confirm_password / new_password_confirmation -> password_confirmation
        if ($this->has('confirm_password') && ! $this->has('password_confirmation')) {
            $merge['password_confirmation'] = $this->input('confirm_password');
        } elseif ($this->has('new_password_confirmation') && ! $this->has('password_confirmation')) {
            $merge['password_confirmation'] = $this->input('new_password_confirmation');
        }

        if (! empty($merge)) {
            $this->merge($merge);
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
            'current_password' => ['required', 'string', 'current_password'],
            'password' => [
                'required',
                'string',
                Password::min(8)->letters()->mixedCase()->numbers(),
                'different:current_password',
                'confirmed',
            ],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    /**
     * Custom attribute names for validation.
     */
    public function attributes(): array
    {
        return [
            'current_password' => 'Kata Sandi Saat Ini',
            'password' => 'Kata Sandi Baru',
            'password_confirmation' => 'Konfirmasi Kata Sandi Baru',
        ];
    }

    /**
     * Custom validation error messages in Indonesian (PUEBI standard).
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'current_password.current_password' => 'Kata sandi saat ini yang Anda masukkan tidak sesuai.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi baru minimal harus terdiri dari 8 karakter.',
            'password.letters' => 'Kata sandi baru harus memuat huruf.',
            'password.mixed_case' => 'Kata sandi baru harus memuat kombinasi huruf besar dan huruf kecil.',
            'password.numbers' => 'Kata sandi baru harus memuat sekurang-kurangnya satu angka (0-9).',
            'password.different' => 'Kata sandi baru harus berbeda dari kata sandi saat ini.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
            'password_confirmation.required' => 'Konfirmasi kata sandi baru wajib diisi.',
        ];
    }
}
