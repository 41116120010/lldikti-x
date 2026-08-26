<?php

namespace App\Http\Requests\Agenda;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMinutesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $agenda = $this->route('agenda');
        return $this->user()?->can('manageMinutes', $agenda) ?? false;
    }

    public function rules(): array
    {
        return [
            'notulensi' => ['nullable', 'string'],
            'kesimpulan' => ['nullable', 'string'],
            'photos' => ['nullable', 'array', 'max:10'],
            'photos.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'captions' => ['nullable', 'array'],
            'captions.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'notulensi' => 'Notulensi Jalannya Rapat',
            'kesimpulan' => 'Kesimpulan & Tindak Lanjut',
            'photos' => 'Foto Dokumentasi Kegiatan',
            'photos.*' => 'Berkas Foto Dokumentasi',
        ];
    }
}
