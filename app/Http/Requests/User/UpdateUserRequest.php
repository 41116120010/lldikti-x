<?php

namespace App\Http\Requests\User;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $targetUser = $this->route('user');
        return $this->user()?->can('update', $targetUser) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $merges = [];

        // 1. Username sanitization: strip leading '@' and lowercase
        if ($this->has('username') && is_string($this->input('username'))) {
            $merges['username'] = ltrim(strtolower(trim($this->input('username'))), '@');
        }

        // 2. Unit resolution: if unit_id is not passed but unit string is present, resolve unit_id
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

        // 3. Role normalization: map UI labels to enum values
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

        // 4. Status normalization: map UI status ('Aktif' / 'Nonaktif') to boolean is_active
        if ($this->has('status') && ! $this->has('is_active')) {
            $merges['is_active'] = in_array(strtolower(trim((string) $this->input('status'))), ['aktif', 'active', '1', 'true']);
        }

        // 5. Phone sanitization: convert '-' or empty string to null
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
        $targetUser = $this->route('user');

        $allowedRoles = $currentUser->isAdministrator()
            ? array_column(UserRole::cases(), 'value')
            : [UserRole::Admin->value, UserRole::Staff->value];

        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'nip' => [
                'sometimes',
                'required',
                'string',
                'digits_between:8,30',
                Rule::unique('users', 'nip')->ignore($targetUser?->id),
            ],
            'username' => [
                'sometimes',
                'required',
                'string',
                'alpha_dash',
                'max:50',
                Rule::unique('users', 'username')->ignore($targetUser?->id),
            ],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:100',
                Rule::unique('users', 'email')->ignore($targetUser?->id),
            ],
            'password' => ['nullable', 'string', Password::min(8)->letters()->numbers()],
            'role' => ['sometimes', 'required', 'string', Rule::in($allowedRoles)],
            'unit_id' => [
                'sometimes',
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
            'password' => 'Kata Sandi Baru',
            'role' => 'Peran (Role)',
            'unit_id' => 'Unit Kerja',
            'phone' => 'Nomor Telepon/WA',
            'is_active' => 'Status Keaktifan',
        ];
    }
}
