<?php

namespace App\Services;

use App\Models\Agenda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportConfigService
{
    /**
     * Extract, sanitize, and optionally persist report configuration for an agenda.
     */
    public function syncFromRequest(Request $request, Agenda $agenda, bool $persist = true): array
    {
        $config = $this->extractFromRequest($request, $agenda);

        if ($persist) {
            $agenda->update(['report_config' => $config]);
        }

        return $config;
    }

    /**
     * Extract and sanitize report configuration from request.
     */
    public function extractFromRequest(Request $request, Agenda $agenda): array
    {
        $resolved = $agenda->resolved_report_config;
        $customLogoPath = $resolved['custom_logo_path'] ?? null;

        // 1. Process logo reset or upload
        if ($request->boolean('reset_custom_logo')) {
            if ($customLogoPath && Storage::disk('public')->exists($customLogoPath)) {
                Storage::disk('public')->delete($customLogoPath);
            }
            $customLogoPath = null;
        } elseif ($request->hasFile('custom_logo')) {
            $request->validate([
                'custom_logo' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:512'],
            ], [
                'custom_logo.image' => 'Berkas logo harus berupa gambar.',
                'custom_logo.mimes' => 'Format logo harus berupa JPEG, PNG, atau WebP.',
                'custom_logo.max' => 'Ukuran logo maksimal 512 KB.',
            ]);

            if ($customLogoPath && Storage::disk('public')->exists($customLogoPath)) {
                Storage::disk('public')->delete($customLogoPath);
            }

            $customLogoPath = $request->file('custom_logo')->store("agendas/{$agenda->id}/logos", 'public');
        } elseif ($request->filled('custom_logo_path')) {
            $customLogoPath = strip_tags($request->input('custom_logo_path'));
        }

        // 2. Build sanitized config array with fallback to resolved defaults
        $defaultNumber = 'BA-RAPAT/' . ($agenda->waktu_mulai ? $agenda->waktu_mulai->format('Y') : date('Y')) . '/' . str_pad((string) $agenda->id, 4, '0', STR_PAD_LEFT);
        $defaultSigningDate = $agenda->waktu_mulai ? $agenda->waktu_mulai->translatedFormat('d F Y') : now()->translatedFormat('d F Y');

        return [
            // Header
            'show_kop' => $request->boolean('show_kop', $resolved['show_kop'] ?? true),
            'show_logo' => $request->boolean('show_logo', $resolved['show_logo'] ?? true),
            'custom_logo_path' => $customLogoPath,
            'instansi_induk' => strip_tags($request->input('instansi_induk', $resolved['instansi_induk'] ?? 'KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI')),
            'instansi_pelaksana' => strip_tags($request->input('instansi_pelaksana', $resolved['instansi_pelaksana'] ?? 'LEMBAGA LAYANAN PENDIDIKAN TINGGI (LLDIKTI) WILAYAH X')),
            'alamat_kontak' => strip_tags($request->input('alamat_kontak', $resolved['alamat_kontak'] ?? 'Jalan Khatib Sulaiman, Padang, Sumatera Barat • Laman: lldikti10.kemdikbud.go.id')),
            'document_title' => strip_tags($request->input('document_title', $resolved['document_title'] ?? 'BERITA ACARA DAN DAFTAR HADIR RAPAT')),
            'show_document_number' => $request->boolean('show_document_number', $resolved['show_document_number'] ?? true),
            'document_number' => strip_tags($request->input('document_number', $resolved['document_number'] ?? $defaultNumber)),

            // Content
            'show_meeting_info' => $request->boolean('show_meeting_info', $resolved['show_meeting_info'] ?? true),
            'custom_agenda_title' => strip_tags($request->input('custom_agenda_title', $resolved['custom_agenda_title'] ?? $agenda->judul_rapat)),
            'custom_location' => strip_tags($request->input('custom_location', $resolved['custom_location'] ?? ($agenda->lokasi_ruang ?? 'Daring (Online Meeting)'))),
            'show_attendees' => $request->boolean('show_attendees', $resolved['show_attendees'] ?? true),
            'show_nip' => $request->boolean('show_nip', $resolved['show_nip'] ?? true),
            'show_unit' => $request->boolean('show_unit', $resolved['show_unit'] ?? true),
            'show_attendance_time' => $request->boolean('show_attendance_time', $resolved['show_attendance_time'] ?? true),
            'show_selfie_photos' => $request->boolean('show_selfie_photos', $resolved['show_selfie_photos'] ?? true),
            'show_attendee_signatures' => $request->boolean('show_attendee_signatures', $resolved['show_attendee_signatures'] ?? true),
            'show_notulensi' => $request->boolean('show_notulensi', $resolved['show_notulensi'] ?? true),
            'show_kesimpulan' => $request->boolean('show_kesimpulan', $resolved['show_kesimpulan'] ?? true),
            'show_documentation' => $request->boolean('show_documentation', $resolved['show_documentation'] ?? true),

            // Footer & Signers
            'signing_city' => strip_tags($request->input('signing_city', $resolved['signing_city'] ?? 'Padang')),
            'signing_date' => strip_tags($request->input('signing_date', $resolved['signing_date'] ?? $defaultSigningDate)),
            'signer1_role' => strip_tags($request->input('signer1_role', $resolved['signer1_role'] ?? 'Pemimpin Rapat')),
            'signer1_name' => strip_tags($request->input('signer1_name') ?: ($resolved['signer1_name'] ?? $agenda->nama_pimpinan)),
            'signer1_nip' => strip_tags($request->input('signer1_nip') ?: (($agenda->nip_pimpinan && $agenda->nip_pimpinan !== '-') ? $agenda->nip_pimpinan : ($resolved['signer1_nip'] ?? '-'))),
            'show_signer1_signature' => $request->boolean('show_signer1_signature', $resolved['show_signer1_signature'] ?? true),
            'signer2_role' => strip_tags($request->input('signer2_role', $resolved['signer2_role'] ?? 'Notulis Rapat')),
            'signer2_name' => strip_tags($request->input('signer2_name') ?: ($resolved['signer2_name'] ?? $agenda->nama_notulis)),
            'signer2_nip' => strip_tags($request->input('signer2_nip') ?: (($agenda->nip_notulis && $agenda->nip_notulis !== '-') ? $agenda->nip_notulis : ($resolved['signer2_nip'] ?? '-'))),
            'show_signer2_signature' => $request->boolean('show_signer2_signature', $resolved['show_signer2_signature'] ?? true),
            'show_signer3' => $request->boolean('show_signer3', $resolved['show_signer3'] ?? false),
            'signer3_role' => strip_tags($request->input('signer3_role', $resolved['signer3_role'] ?? 'Kepala Lembaga Layanan Pendidikan Tinggi Wilayah X')),
            'signer3_name' => strip_tags($request->input('signer3_name', $resolved['signer3_name'] ?? '')),
            'signer3_nip' => strip_tags($request->input('signer3_nip', $resolved['signer3_nip'] ?? '-')),
            'show_footer_note' => $request->boolean('show_footer_note', $resolved['show_footer_note'] ?? true),
            'footer_note' => strip_tags($request->input('footer_note', $resolved['footer_note'] ?? 'Dokumen ini diterbitkan secara resmi melalui Sistem Informasi Presensi Rapat (SIPERAPAT) LLDIKTI Wilayah X')),
        ];
    }
}
