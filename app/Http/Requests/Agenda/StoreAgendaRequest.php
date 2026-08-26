<?php

namespace App\Http\Requests\Agenda;

use App\Models\Agenda;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAgendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Agenda::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'judul_rapat' => ['required', 'string', 'max:255'],
            'jenis_rapat' => ['required', 'string', Rule::in(['koordinasi', 'pleno', 'evaluasi', 'konsinyasi', 'terbatas', 'lainnya'])],
            'tipe_rapat' => ['required', 'string', Rule::in(['offline', 'online', 'hybrid'])],
            'lokasi_ruang' => ['nullable', 'string', 'max:150', Rule::requiredIf(fn () => in_array($this->input('tipe_rapat'), ['offline', 'hybrid']))],
            'link_meeting' => ['nullable', 'string', 'max:500', Rule::requiredIf(fn () => in_array($this->input('tipe_rapat'), ['online', 'hybrid']))],
            'waktu_mulai' => ['required', 'date'],
            'waktu_selesai' => ['required', 'date', 'after:waktu_mulai'],
            'is_all_units' => ['nullable', 'boolean'],
            'unit_ids' => ['nullable', 'array', Rule::requiredIf(fn () => !$this->boolean('is_all_units', true))],
            'unit_ids.*' => ['exists:units,id'],
            'surat_edaran' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'scheduled', 'ongoing', 'completed', 'cancelled'])],
        ];
    }

    public function attributes(): array
    {
        return [
            'judul_rapat' => 'Judul / Perihal Rapat',
            'jenis_rapat' => 'Jenis Rapat',
            'tipe_rapat' => 'Format Pelaksanaan',
            'lokasi_ruang' => 'Lokasi / Ruang Rapat',
            'link_meeting' => 'Tautan Daring (Zoom/GMeet)',
            'waktu_mulai' => 'Waktu Mulai',
            'waktu_selesai' => 'Waktu Selesai',
            'is_all_units' => 'Target Peserta',
            'unit_ids' => 'Unit Kerja yang Diundang',
            'surat_edaran' => 'Berkas Surat Edaran / Undangan',
            'status' => 'Status Agenda',
        ];
    }
}
