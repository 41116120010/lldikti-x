<?php

namespace App\Http\Requests\Agenda;

use App\Models\Agenda;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddAgendaParticipantsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $agenda = $this->route('agenda');

        return $agenda instanceof Agenda
            && ($this->user()?->can('manageParticipants', $agenda) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['required', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'user_ids' => 'Daftar Pegawai ASN Tambahan',
            'user_ids.*' => 'Pegawai ASN',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_ids.required' => 'Pilih minimal satu pegawai ASN yang akan ditambahkan sebagai peserta rapat.',
            'user_ids.min' => 'Pilih minimal satu pegawai ASN yang akan ditambahkan sebagai peserta rapat.',
            'user_ids.*.exists' => 'Pegawai ASN yang dipilih tidak ditemukan atau berstatus non-aktif.',
        ];
    }
}
