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
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $user = $this->user();
        $merges = [];

        if (! $this->has('name') && $user) {
            $merges['name'] = $user->name;
        } elseif ($this->has('name')) {
            $merges['name'] = is_string($this->name) ? trim($this->name) : $this->name;
        }

        if (! $this->has('email') && $user) {
            $merges['email'] = $user->email;
        } elseif ($this->has('email')) {
            $merges['email'] = is_string($this->email) ? strtolower(trim($this->email)) : $this->email;
        }

        if ($this->has('phone')) {
            $phone = is_string($this->phone) ? trim($this->phone) : $this->phone;
            $merges['phone'] = ($phone === '') ? null : $phone;
        }

        if (! empty($merges)) {
            $this->merge($merges);
        }
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
                Password::min(8)->letters()->mixedCase()->numbers(),
                'different:current_password',
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
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Alamat email ini sudah terdaftar oleh pengguna lain.',
            'current_password.required_with' => 'Kata sandi saat ini wajib dimasukkan untuk mengonfirmasi penggantian kata sandi.',
            'current_password.current_password' => 'Kata sandi saat ini yang Anda masukkan tidak sesuai.',
            'password.different' => 'Kata sandi baru harus berbeda dari kata sandi saat ini.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
            'password.min' => 'Kata sandi baru minimal harus terdiri dari 8 karakter.',
            'password.letters' => 'Kata sandi baru harus memuat huruf.',
            'password.mixed_case' => 'Kata sandi baru harus memuat kombinasi huruf besar dan huruf kecil.',
            'password.numbers' => 'Kata sandi baru harus memuat sekurang-kurangnya satu angka (0-9).',
        ];
    }
}
