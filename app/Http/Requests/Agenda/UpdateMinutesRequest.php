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
     * Prepare data for validation by sanitizing rich text HTML.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('notulensi')) {
            $this->merge([
                'notulensi' => $this->sanitizeRichText($this->input('notulensi')),
            ]);
        }

        if ($this->has('kesimpulan')) {
            $this->merge([
                'kesimpulan' => $this->sanitizeRichText($this->input('kesimpulan')),
            ]);
        }
    }

    /**
     * Sanitize rich text HTML to allow only safe formatting tags and strip XSS vectors.
     */
    protected function sanitizeRichText(?string $html): ?string
    {
        if (!$html) {
            return null;
        }

        // 1. Remove dangerous blocks including their inner contents
        $clean = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html);
        $clean = preg_replace('#<iframe(.*?)>(.*?)</iframe>#is', '', $clean);
        $clean = preg_replace('#<style(.*?)>(.*?)</style>#is', '', $clean);

        // 2. Check for completely empty content or placeholder breaks
        $trimmed = trim(strip_tags($clean));
        if ($trimmed === '' && !str_contains($clean, '<hr') && !str_contains($clean, '<table')) {
            return null;
        }

        // Allowed safe tags for official meeting documentation
        $allowedTags = '<p><br><b><strong><i><em><u><s><strike><ul><ol><li><h2><h3><h4><blockquote><hr><div><span><table><thead><tbody><tr><th><td><sup><sub><font>';

        // 3. Strip all disallowed tags
        $clean = strip_tags($clean, $allowedTags);

        // 4. Strip dangerous inline event handlers (e.g. onload, onerror, onclick) and javascript: protocols
        $clean = preg_replace('/on[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $clean);
        $clean = preg_replace('/href\s*=\s*("javascript:.*?"|\'javascript:.*?\'|javascript:[^\s>]+)/i', '', $clean);
        $clean = preg_replace('/src\s*=\s*("javascript:.*?"|\'javascript:.*?\'|javascript:[^\s>]+)/i', '', $clean);

        return trim($clean);
    }
}
