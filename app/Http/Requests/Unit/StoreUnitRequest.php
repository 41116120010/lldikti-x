<?php

namespace App\Http\Requests\Unit;

use Illuminate\Foundation\Http\FormRequest;

class StoreUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Unit::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'nama_unit' => ['required', 'string', 'max:150'],
            'kode_unit' => ['required', 'string', 'max:30', 'uppercase', 'unique:units,kode_unit'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nama_unit' => 'Nama Unit Kerja',
            'kode_unit' => 'Kode Unit',
            'deskripsi' => 'Deskripsi Unit',
            'is_active' => 'Status Keaktifan',
        ];
    }
}
