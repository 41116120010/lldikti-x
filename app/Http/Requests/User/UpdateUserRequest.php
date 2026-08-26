<?php

namespace App\Http\Requests\User;

use App\Models\User;
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

    public function rules(): array
    {
        $currentUser = $this->user();
        $targetUser = $this->route('user');

        $allowedRoles = $currentUser->isAdministrator() 
            ? ['administrator', 'admin', 'staff'] 
            : ['admin', 'staff'];

        return [
            'name' => ['required', 'string', 'max:150'],
            'nip' => [
                'required',
                'string',
                'digits_between:8,30',
                Rule::unique('users', 'nip')->ignore($targetUser->id),
            ],
            'username' => [
                'required',
                'string',
                'alpha_dash',
                'max:50',
                Rule::unique('users', 'username')->ignore($targetUser->id),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:100',
                Rule::unique('users', 'email')->ignore($targetUser->id),
            ],
            'password' => ['nullable', 'string', Password::min(8)->letters()->numbers()],
            'role' => ['required', 'string', Rule::in($allowedRoles)],
            'unit_id' => [
                $currentUser->isAdministrator() ? 'nullable' : 'required',
                'exists:units,id',
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
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
