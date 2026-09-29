<?php

namespace App\Http\Requests\Agenda;

use App\Support\Html;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMinutesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $agenda = $this->route('agenda');
        return $this->user()?->can('manageMinutes', $agenda) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'notulensi' => ['nullable', 'string'],
            'kesimpulan' => ['nullable', 'string'],
            'photos' => ['nullable', 'array', 'max:10'],
            'photos.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'captions' => ['nullable', 'array'],
            'captions.*' => ['nullable', 'string', 'max:255'],

            // Document Customization Configuration (Optional / Unified)
            'has_document_config' => ['nullable', 'boolean'],
            'show_kop' => ['nullable', 'boolean'],
            'show_logo' => ['nullable', 'boolean'],
            'reset_custom_logo' => ['nullable', 'boolean'],
            'custom_logo' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:512'],
            'instansi_induk' => ['nullable', 'string', 'max:255'],
            'instansi_pelaksana' => ['nullable', 'string', 'max:255'],
            'alamat_kontak' => ['nullable', 'string', 'max:255'],
            'document_title' => ['nullable', 'string', 'max:255'],
            'show_document_number' => ['nullable', 'boolean'],
            'document_number' => ['nullable', 'string', 'max:255'],
            'show_meeting_info' => ['nullable', 'boolean'],
            'custom_agenda_title' => ['nullable', 'string', 'max:255'],
            'custom_location' => ['nullable', 'string', 'max:255'],
            'show_attendees' => ['nullable', 'boolean'],
            'show_nip' => ['nullable', 'boolean'],
            'show_unit' => ['nullable', 'boolean'],
            'show_attendance_time' => ['nullable', 'boolean'],
            'show_selfie_photos' => ['nullable', 'boolean'],
            'show_attendee_signatures' => ['nullable', 'boolean'],
            'show_notulensi' => ['nullable', 'boolean'],
            'show_kesimpulan' => ['nullable', 'boolean'],
            'show_documentation' => ['nullable', 'boolean'],
            'signing_city' => ['nullable', 'string', 'max:100'],
            'signing_date' => ['nullable', 'string', 'max:100'],
            'signer1_role' => ['nullable', 'string', 'max:100'],
            'signer1_name' => ['nullable', 'string', 'max:150'],
            'signer1_nip' => ['nullable', 'string', 'max:50'],
            'show_signer1_signature' => ['nullable', 'boolean'],
            'signer2_role' => ['nullable', 'string', 'max:100'],
            'signer2_name' => ['nullable', 'string', 'max:150'],
            'signer2_nip' => ['nullable', 'string', 'max:50'],
            'show_signer2_signature' => ['nullable', 'boolean'],
            'show_signer3' => ['nullable', 'boolean'],
            'signer3_role' => ['nullable', 'string', 'max:100'],
            'signer3_name' => ['nullable', 'string', 'max:150'],
            'signer3_nip' => ['nullable', 'string', 'max:50'],
            'show_footer_note' => ['nullable', 'boolean'],
            'footer_note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'notulensi' => 'Notulensi Jalannya Rapat',
            'kesimpulan' => 'Kesimpulan & Tindak Lanjut',
            'photos' => 'Foto Dokumentasi Kegiatan',
            'photos.*' => 'Berkas Foto Dokumentasi',
            'custom_logo' => 'Berkas Logo Kustom',
            'document_title' => 'Judul Dokumen',
            'document_number' => 'Nomor Dokumen',
            'instansi_induk' => 'Nama Instansi Induk',
            'instansi_pelaksana' => 'Nama Instansi Pelaksana',
            'signer1_name' => 'Nama Penandatangan 1',
            'signer2_name' => 'Nama Penandatangan 2',
        ];
    }

    /**
     * Normalise the rich-text fields before validation.
     *
     * This keeps the stored row clean, but it is NOT the security boundary — the
     * model accessors sanitise on read as well. Sanitising here alone left every
     * other write path (seeders, imports, future endpoints, rows written before
     * this rule existed) able to persist markup that a {!! !!} template would then
     * emit verbatim.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('notulensi')) {
            $this->merge([
                'notulensi' => Html::sanitize($this->input('notulensi')),
            ]);
        }

        if ($this->has('kesimpulan')) {
            $this->merge([
                'kesimpulan' => Html::sanitize($this->input('kesimpulan')),
            ]);
        }
    }
}
