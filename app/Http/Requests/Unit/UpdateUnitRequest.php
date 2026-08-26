<?php

namespace App\Http\Requests\Unit;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        $unit = $this->route('unit');
        return $this->user()?->can('update', $unit) ?? false;
    }

    public function rules(): array
    {
        $unit = $this->route('unit');

        return [
            'nama_unit' => ['required', 'string', 'max:150'],
            'kode_unit' => [
                'required',
                'string',
                'max:30',
                'uppercase',
                Rule::unique('units', 'kode_unit')->ignore($unit->id),
            ],
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
