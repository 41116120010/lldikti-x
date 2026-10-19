<?php

namespace App\Http\Requests\User;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $merges = [];

        // 1. Username sanitization: strip leading '@' and lowercase
        if ($this->has('username') && is_string($this->input('username'))) {
            $merges['username'] = ltrim(strtolower(trim($this->input('username'))), '@');
        }

        // 2. Password fallback: if omitted or empty string, default to civil service initial password
        if (! $this->has('password') || $this->input('password') === null || trim((string) $this->input('password')) === '') {
            $merges['password'] = 'Password123!';
        }

        // 3. Unit resolution: if unit_id is not passed but unit string is present, resolve unit_id
        if (! $this->has('unit_id') && $this->has('unit')) {
            $unitVal = trim((string) $this->input('unit'));
            if ($unitVal !== '') {
                $unitId = \App\Models\Unit::where('nama_unit', $unitVal)
                    ->orWhere('kode_unit', $unitVal)
                    ->value('id');
                if ($unitId) {
                    $merges['unit_id'] = $unitId;
                }
            }
        }

        // 4. Role normalization: map UI labels to enum values
        if ($this->has('role') && is_string($this->input('role'))) {
            $roleMap = [
                'administrator' => 'administrator',
                'admin unit' => 'admin',
                'admin' => 'admin',
                'staff' => 'staff',
                'pegawai' => 'staff',
                'pegawai asn' => 'staff',
            ];
            $normalizedRole = strtolower(trim($this->input('role')));
            if (isset($roleMap[$normalizedRole])) {
                $merges['role'] = $roleMap[$normalizedRole];
            }
        }

        // 5. Status normalization: map UI status ('Aktif' / 'Nonaktif') to boolean is_active
        if ($this->has('status') && ! $this->has('is_active')) {
            $merges['is_active'] = in_array(strtolower(trim((string) $this->input('status'))), ['aktif', 'active', '1', 'true']);
        }

        // 6. Phone sanitization: convert '-' or empty string to null
        if ($this->has('phone')) {
            $phone = trim((string) $this->input('phone'));
            $merges['phone'] = ($phone === '-' || $phone === '') ? null : $phone;
        }

        if (! empty($merges)) {
            $this->merge($merges);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $currentUser = $this->user();
        $allowedRoles = $currentUser->isAdministrator()
            ? array_column(UserRole::cases(), 'value')
            : [UserRole::Admin->value, UserRole::Staff->value];

        return [
            'name' => ['required', 'string', 'max:150'],
            'nip' => ['required', 'string', 'digits_between:8,30', 'unique:users,nip'],
            'username' => ['required', 'string', 'alpha_dash', 'max:50', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:users,email'],
            'password' => ['nullable', 'string', Password::min(8)->letters()->numbers()],
            'role' => ['required', 'string', Rule::in($allowedRoles)],
            'unit_id' => [
                $currentUser->isAdministrator() ? 'nullable' : 'required',
                'exists:units,id',
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
            'unit' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'string', 'max:30'],
            'jabatan' => ['nullable', 'string', 'max:150'],
            'pangkat_golongan' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nama Lengkap',
            'nip' => 'Nomor Induk Pegawai (NIP)',
            'username' => 'Username',
            'email' => 'Alamat Email',
            'password' => 'Kata Sandi',
            'role' => 'Peran (Role)',
            'unit_id' => 'Unit Kerja',
            'phone' => 'Nomor Telepon/WA',
            'is_active' => 'Status Keaktifan',
        ];
    }
}
