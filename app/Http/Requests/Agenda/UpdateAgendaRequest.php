<?php

namespace App\Http\Requests\Agenda;

use App\Models\Agenda;
use App\Services\AgendaConflictService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAgendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $agenda = $this->route('agenda');
        return $this->user()?->can('update', $agenda) ?? false;
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

            $agenda = $this->route('agenda');
            $agendaId = $agenda instanceof Agenda ? (int) $agenda->id : ($agenda ? (int) $agenda : null);

            try {
                $conflictResult = AgendaConflictService::checkConflicts(
                    data: $this->all(),
                    ignoreAgendaId: $agendaId,
                    user: $this->user()
                );

                if ($conflictResult['has_conflicts']) {
                    foreach ($conflictResult['errors'] as $field => $messages) {
                        foreach ($messages as $message) {
                            $validator->errors()->add($field, $message);
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Gagal memeriksa potensi konflik jadwal pada validasi form update agenda', [
                    'agenda_id' => $agendaId,
                    'user_id' => $this->user()?->id,
                    'error' => $e->getMessage(),
                ]);

                $validator->errors()->add('jadwal', 'Terjadi kendala saat memeriksa ketersediaan jadwal/ruangan. Silakan coba kembali.');
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

        if ($this->has('tipe_rapat')) {
            $tipe = strtolower(trim((string) $this->input('tipe_rapat')));
            if ($tipe === 'luring') {
                $this->merge(['tipe_rapat' => 'offline']);
            } elseif ($tipe === 'daring') {
                $this->merge(['tipe_rapat' => 'online']);
            }
        }

        if ($this->exists('waktu_selesai') && blank($this->input('waktu_selesai'))) {
            $this->merge(['waktu_selesai' => null]);
        }

        if ($this->exists('pimpinan_id') && blank($this->input('pimpinan_id'))) {
            $this->merge(['pimpinan_id' => null]);
        }

        if ($this->exists('notulis_id') && blank($this->input('notulis_id'))) {
            $this->merge(['notulis_id' => null]);
        }

        // Resolve target_type and is_all_units seamlessly
        if ($this->has('target_type')) {
            $targetType = (string) $this->input('target_type');
            $this->merge([
                'is_all_units' => ($targetType === 'all_units'),
            ]);
        } elseif ($this->has('is_all_units')) {
            $isAll = $this->boolean('is_all_units');
            if ($isAll) {
                $this->merge(['target_type' => 'all_units']);
            } elseif ($this->filled('user_ids')) {
                $this->merge(['target_type' => 'selected_users']);
            } else {
                $this->merge(['target_type' => 'selected_units']);
            }
        }
    }

    public function rules(): array
    {
        $user = $this->user();
        $isAdministrator = $user?->isAdministrator() ?? false;

        $targetTypeRule = ['required', 'string'];
        if (! $isAdministrator) {
            $targetTypeRule[] = Rule::in(['selected_units', 'selected_users']);
        } else {
            $targetTypeRule[] = Rule::in(['all_units', 'selected_units', 'selected_users']);
        }

        return [
            'judul_rapat' => ['required', 'string', 'max:255'],
            'pimpinan_id' => ['nullable', 'exists:users,id'],
            'notulis_id' => ['nullable', 'exists:users,id'],
            // Free text by design — the suggestions are rendered as a datalist, not
            // enforced as an allowlist. See config/agenda.php.
            'jenis_rapat' => ['required', 'string', 'max:100'],
            'tipe_rapat' => ['required', 'string', Rule::in(['offline', 'online', 'hybrid'])],
            'lokasi_ruang' => ['nullable', 'string', 'max:150', Rule::requiredIf(fn () => in_array($this->input('tipe_rapat'), ['offline', 'hybrid']))],
            'link_meeting' => ['nullable', 'string', 'max:500', Rule::requiredIf(fn () => in_array($this->input('tipe_rapat'), ['online', 'hybrid']))],
            'waktu_mulai' => ['required', 'date'],
            'waktu_selesai' => ['nullable', 'date', 'after:waktu_mulai'],
            'target_type' => $targetTypeRule,
            'is_all_units' => ['nullable', 'boolean'],
            'unit_ids' => ['nullable', 'array', Rule::requiredIf(fn () => $this->input('target_type') === 'selected_units')],
            'unit_ids.*' => ['exists:units,id'],
            'user_ids' => ['nullable', 'array', Rule::requiredIf(fn () => $this->input('target_type') === 'selected_users')],
            'user_ids.*' => ['exists:users,id'],
            'surat_edaran_type' => ['nullable', 'string', Rule::in(['upload', 'digital'])],
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
            'target_type' => 'Sasaran Peserta Rapat',
            'is_all_units' => 'Target Peserta',
            'unit_ids' => 'Unit Kerja yang Diundang',
            'user_ids' => 'Daftar ASN yang Diundang',
            'surat_edaran' => 'Berkas Surat Edaran / Undangan',
            'status' => 'Status Agenda',
        ];
    }

    public function messages(): array
    {
        return [
            'unit_ids.required' => 'Pilih sekurang-kurangnya satu unit kerja sasaran rapat.',
            'user_ids.required' => 'Pilih sekurang-kurangnya satu ASN sasaran rapat.',
            'target_type.in' => 'Pilihan sasaran peserta rapat tidak valid.',
        ];
    }
}
