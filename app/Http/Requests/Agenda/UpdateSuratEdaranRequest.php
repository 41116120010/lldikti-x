<?php

namespace App\Http\Requests\Agenda;

use App\Models\Agenda;
use App\Support\Html;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSuratEdaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        $agenda = $this->route('agenda');

        return $this->user()?->can('manageCircular', $agenda) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'template_type' => ['required', 'string', 'in:undangan,edaran'],
            'nomor_surat' => ['required', 'string', 'max:255'],
            'sifat' => ['nullable', 'string', 'max:100'],
            'lampiran' => ['nullable', 'string', 'max:150'],
            'perihal' => ['required', 'string', 'max:255'],
            'tempat_surat' => ['nullable', 'string', 'max:100'],
            'tanggal_surat' => ['required', 'string', 'max:100'],
            'tujuan_surat' => ['required', 'string'],
            'pembuka' => ['nullable', 'string'],
            'surat_edaran_content' => ['nullable', 'string'],
            'catatan' => ['nullable', 'string'],
            'penutup' => ['nullable', 'string'],
            'signer_role' => ['required', 'string', 'max:150'],
            'signer_name' => ['required', 'string', 'max:150'],
            'signer_nip' => ['nullable', 'string', 'max:50'],
            'show_signature' => ['nullable', 'boolean'],
            'tembusan' => ['nullable', 'string'],
            'show_lampiran' => ['nullable', 'boolean'],
            'lampiran_content' => ['nullable', 'string'],

            // Kop Surat Settings
            'show_kop' => ['nullable', 'boolean'],
            'show_logo' => ['nullable', 'boolean'],
            'reset_custom_logo' => ['nullable', 'boolean'],
            'custom_logo' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:512'],
            'instansi_induk' => ['nullable', 'string', 'max:255'],
            'instansi_pelaksana' => ['nullable', 'string', 'max:255'],
            'alamat_kontak' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'template_type' => 'Format Naskah Dinas',
            'nomor_surat' => 'Nomor Surat Dinas',
            'sifat' => 'Sifat Surat',
            'lampiran' => 'Lampiran Surat',
            'perihal' => 'Perihal / Hal Surat',
            'tempat_surat' => 'Tempat Penetapan Surat',
            'tanggal_surat' => 'Tanggal Penetapan Surat',
            'tujuan_surat' => 'Tujuan Surat (Yth.)',
            'pembuka' => 'Alinea Pembuka',
            'surat_edaran_content' => 'Isi / Rincian Tambahan Naskah',
            'catatan' => 'Catatan Tambahan',
            'penutup' => 'Alinea Penutup',
            'signer_role' => 'Jabatan Penandatangan',
            'signer_name' => 'Nama Pejabat Penandatangan',
            'signer_nip' => 'NIP Pejabat Penandatangan',
            'tembusan' => 'Daftar Tembusan',
            'custom_logo' => 'Berkas Logo Kustom',
        ];
    }

    public function messages(): array
    {
        return [
            'custom_logo.image' => 'Berkas logo kustom wajib berupa gambar.',
            'custom_logo.mimes' => 'Format logo kustom harus berupa JPEG, PNG, atau WebP.',
            'custom_logo.max' => 'Ukuran berkas logo kustom maksimal 512 KB.',
        ];
    }

    /**
     * Sanitize rich-text content and supply resilient defaults for hidden metadata fields before validation.
     */
    protected function prepareForValidation(): void
    {
        $agenda = $this->route('agenda');
        $defaults = $agenda instanceof Agenda
            ? $agenda->resolved_surat_edaran_config
            : (new Agenda)->getDefaultSuratEdaranConfig();

        $merged = [];

        $templateType = trim((string) $this->input('template_type', ''));
        if (! in_array($templateType, ['undangan', 'edaran'], true)) {
            $merged['template_type'] = $defaults['template_type'] ?? 'undangan';
        }

        $requiredFallbacks = [
            'nomor_surat' => $defaults['nomor_surat'] ?? '-',
            'perihal' => $defaults['perihal'] ?? ('Undangan Rapat ' . ($agenda instanceof Agenda ? $agenda->judul_rapat : 'Koordinasi Kedinasan')),
            'tanggal_surat' => $defaults['tanggal_surat'] ?? now()->translatedFormat('d F Y'),
            'tujuan_surat' => $defaults['tujuan_surat'] ?? 'Pimpinan Unit Kerja dan Seluruh Pegawai ASN di Lingkungan LLDIKTI Wilayah X',
            'signer_role' => $defaults['signer_role'] ?? 'Kepala',
            'signer_name' => $defaults['signer_name'] ?? 'Afdalisma',
        ];

        foreach ($requiredFallbacks as $field => $fallbackValue) {
            if (! $this->filled($field)) {
                $merged[$field] = $fallbackValue;
            }
        }

        if ($this->has('pembuka')) {
            $merged['pembuka'] = Html::sanitize((string) $this->input('pembuka'));
        }

        if ($this->has('surat_edaran_content')) {
            $merged['surat_edaran_content'] = Html::sanitize((string) $this->input('surat_edaran_content'));
        }

        if ($this->has('catatan')) {
            $merged['catatan'] = Html::sanitize((string) $this->input('catatan'));
        }

        if ($this->has('penutup')) {
            $merged['penutup'] = Html::sanitize((string) $this->input('penutup'));
        }

        if ($this->has('lampiran_content')) {
            $merged['lampiran_content'] = Html::sanitize((string) $this->input('lampiran_content'));
        }

        if (! empty($merged)) {
            $this->merge($merged);
        }
    }
}
