<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'string',
                'email',
                'max:100',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'current_password' => [
                'nullable',
                'required_with:password',
                'current_password',
            ],
            'password' => [
                'nullable',
                'string',
                Password::min(8)->letters()->numbers(),
                'confirmed',
            ],
        ];
    }

    /**
     * Custom attribute names for validation.
     */
    public function attributes(): array
    {
        return [
            'name' => 'Nama Lengkap',
            'email' => 'Alamat Email',
            'phone' => 'Nomor Telepon / WhatsApp',
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
            'current_password.current_password' => 'Kata sandi saat ini yang Anda masukkan tidak sesuai.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
            'password.min' => 'Kata sandi baru minimal harus terdiri dari 8 karakter kombinasi huruf dan angka.',
        ];
    }
}
