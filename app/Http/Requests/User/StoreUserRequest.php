<?php

namespace App\Http\Requests\User;

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

    public function rules(): array
    {
        $currentUser = $this->user();
        $allowedRoles = $currentUser->isAdministrator() 
            ? ['administrator', 'admin', 'staff'] 
            : ['admin', 'staff'];

        return [
            'name' => ['required', 'string', 'max:150'],
            'nip' => ['required', 'string', 'digits_between:8,30', 'unique:users,nip'],
            'username' => ['required', 'string', 'alpha_dash', 'max:50', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers()],
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
            'password' => 'Kata Sandi',
            'role' => 'Peran (Role)',
            'unit_id' => 'Unit Kerja',
            'phone' => 'Nomor Telepon/WA',
            'is_active' => 'Status Keaktifan',
        ];
    }
}
