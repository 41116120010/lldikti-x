<?php

namespace App\Http\Requests\Agenda;

use App\Models\Agenda;
use App\Services\AgendaConflictService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAgendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Agenda::class) ?? false;
    }

    /**
     * Configure the validator instance with conflict prevention logic.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $conflictResult = AgendaConflictService::checkConflicts(
                data: $this->all(),
                ignoreAgendaId: null,
                user: $this->user()
            );

            if ($conflictResult['has_conflicts']) {
                foreach ($conflictResult['errors'] as $field => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($field, $message);
                    }
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('link_meeting') && filled($this->input('link_meeting'))) {
            $link = trim($this->input('link_meeting'));
            if (!preg_match('~^(?:f|ht)tps?://~i', $link)) {
                $link = 'https://' . $link;
            }
            $this->merge(['link_meeting' => $link]);
        }

        if ($this->has('waktu_selesai') && blank($this->input('waktu_selesai'))) {
            $this->merge(['waktu_selesai' => null]);
        }

        if ($this->has('pimpinan_id') && blank($this->input('pimpinan_id'))) {
            $this->merge(['pimpinan_id' => null]);
        }

        if ($this->has('notulis_id') && blank($this->input('notulis_id'))) {
            $this->merge(['notulis_id' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'judul_rapat' => ['required', 'string', 'max:255'],
            'pimpinan_id' => ['nullable', 'exists:users,id'],
            'notulis_id' => ['nullable', 'exists:users,id'],
            'jenis_rapat' => ['required', 'string', 'max:100'],
            'tipe_rapat' => ['required', 'string', Rule::in(['offline', 'online', 'hybrid'])],
            'lokasi_ruang' => ['nullable', 'string', 'max:150', Rule::requiredIf(fn () => in_array($this->input('tipe_rapat'), ['offline', 'hybrid']))],
            'link_meeting' => ['nullable', 'string', 'max:500', Rule::requiredIf(fn () => in_array($this->input('tipe_rapat'), ['online', 'hybrid']))],
            'waktu_mulai' => ['required', 'date'],
            'waktu_selesai' => ['nullable', 'date', 'after:waktu_mulai'],
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
            'pimpinan_id' => 'Pemimpin Rapat',
            'notulis_id' => 'Notulis Rapat',
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
