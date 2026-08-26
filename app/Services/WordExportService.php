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
    public function exportBeritaAcara(Agenda $agenda): Response
    {
        $agenda->load([
            'creator.unit',
            'units',
            'attendances.user.unit',
            'documentations',
        ]);

        // Embed media into base64 strings
        $attendancesWithMedia = $agenda->attendances->map(function ($att) {
            $sigBase64 = null;
            if ($att->signature_path && Storage::disk('public')->exists($att->signature_path)) {
                $content = Storage::disk('public')->get($att->signature_path);
                $mime = Storage::disk('public')->mimeType($att->signature_path) ?: 'image/png';
                $sigBase64 = 'data:' . $mime . ';base64,' . base64_encode($content);
            }

            return [
                'model' => $att,
                'sig_base64' => $sigBase64,
            ];
        });

        $content = view('exports.word_berita_acara', [
            'agenda' => $agenda,
            'attendances' => $attendancesWithMedia,
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
