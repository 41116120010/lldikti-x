<?php

namespace App\Services;

use App\Models\Agenda;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class WordExportService
{
    /**
     * Generate Microsoft Word-compatible document (.doc) for an Agenda.
     */
    public function exportBeritaAcara(Agenda $agenda, array $config = []): Response
    {
        $agenda->load([
            'creator.unit',
            'pimpinan.unit',
            'notulis.unit',
            'units',
            'attendances.user.unit',
            'documentations',
        ]);

        $resolvedConfig = array_replace($agenda->resolved_report_config, $config);

        // Process Logo Base64 (Custom logo or default Tut Wuri Handayani)
        $logoBase64 = null;
        if ($resolvedConfig['show_logo'] ?? true) {
            $customLogo = $resolvedConfig['custom_logo_path'] ?? null;
            if ($customLogo && Storage::disk('public')->exists($customLogo)) {
                $content = Storage::disk('public')->get($customLogo);
                $mime = Storage::disk('public')->mimeType($customLogo) ?: 'image/png';
                $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode($content);
            } elseif (file_exists(public_path('images/tut-wuri-handayani.png'))) {
                $content = file_get_contents(public_path('images/tut-wuri-handayani.png'));
                $logoBase64 = 'data:image/png;base64,' . base64_encode($content);
            }
        }

        // Embed media into base64 strings
        $attendancesWithMedia = $agenda->attendances->map(function ($att) {
            $sigBase64 = null;
            if ($att->signature_path && Storage::disk('public')->exists($att->signature_path)) {
                $content = Storage::disk('public')->get($att->signature_path);
                $mime = Storage::disk('public')->mimeType($att->signature_path) ?: 'image/png';
                $sigBase64 = 'data:' . $mime . ';base64,' . base64_encode($content);
            }

            $selfieBase64 = null;
            if ($att->selfie_path && Storage::disk('public')->exists($att->selfie_path)) {
                $content = Storage::disk('public')->get($att->selfie_path);
                $mime = Storage::disk('public')->mimeType($att->selfie_path) ?: 'image/jpeg';
                $selfieBase64 = 'data:' . $mime . ';base64,' . base64_encode($content);
            }

            return [
                'model' => $att,
                'sig_base64' => $sigBase64,
                'selfie_base64' => $selfieBase64,
            ];
        });

        // Pimpinan digital signature base64 (if attended)
        $pimpinanSigBase64 = null;
        $pimpinanAtt = $agenda->pimpinan_attendance;
        if ($pimpinanAtt?->signature_path && Storage::disk('public')->exists($pimpinanAtt->signature_path)) {
            $content = Storage::disk('public')->get($pimpinanAtt->signature_path);
            $mime = Storage::disk('public')->mimeType($pimpinanAtt->signature_path) ?: 'image/png';
            $pimpinanSigBase64 = 'data:' . $mime . ';base64,' . base64_encode($content);
        }

        // Notulis digital signature base64 (if attended)
        $notulisSigBase64 = null;
        $notulisAtt = $agenda->notulis_attendance;
        if ($notulisAtt?->signature_path && Storage::disk('public')->exists($notulisAtt->signature_path)) {
            $content = Storage::disk('public')->get($notulisAtt->signature_path);
            $mime = Storage::disk('public')->mimeType($notulisAtt->signature_path) ?: 'image/png';
            $notulisSigBase64 = 'data:' . $mime . ';base64,' . base64_encode($content);
        }

        // Documentation photos with base64 for Word
        $documentationsWithMedia = $agenda->documentations->map(function ($doc) {
            $base64 = null;
            if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
                $content = Storage::disk('public')->get($doc->file_path);
                $mime = Storage::disk('public')->mimeType($doc->file_path) ?: 'image/jpeg';
                $base64 = 'data:' . $mime . ';base64,' . base64_encode($content);
            }

            return [
                'model' => $doc,
                'base64' => $base64,
            ];
        });

        $content = view('exports.word_berita_acara', [
            'agenda' => $agenda,
            'config' => $resolvedConfig,
            'logoBase64' => $logoBase64,
            'attendances' => $attendancesWithMedia,
            'documentations' => $documentationsWithMedia,
            'pimpinanSigBase64' => $pimpinanSigBase64,
            'notulisSigBase64' => $notulisSigBase64,
            'generatedAt' => now(),
        ])->render();

        $filename = 'Berita_Acara_' . $agenda->slug . '.doc';

        return response($content, 200, [
            'Content-Type' => 'application/vnd.ms-word; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
