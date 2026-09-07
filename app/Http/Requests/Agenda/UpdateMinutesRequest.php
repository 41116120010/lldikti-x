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
        if ($trimmed === '' && !str_contains($clean, '<hr')) {
            return null;
        }

        // Allowed safe tags for official meeting documentation
        $allowedTags = '<p><br><b><strong><i><em><u><s><strike><ul><ol><li><h2><h3><h4><blockquote><hr><div><span><table><thead><tbody><tr><th><td>';

        // 3. Strip all disallowed tags
        $clean = strip_tags($clean, $allowedTags);

        // 4. Strip dangerous inline event handlers (e.g. onload, onerror, onclick) and javascript: protocols
        $clean = preg_replace('/on[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $clean);
        $clean = preg_replace('/href\s*=\s*("javascript:.*?"|\'javascript:.*?\'|javascript:[^\s>]+)/i', '', $clean);
        $clean = preg_replace('/src\s*=\s*("javascript:.*?"|\'javascript:.*?\'|javascript:[^\s>]+)/i', '', $clean);

        return trim($clean);
    }
}
