<?php

namespace App\Services;

use App\Models\Agenda;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class PdfExportService
{
    /**
     * Generate printable official PDF / Berita Acara document for an Agenda.
     */
    public function exportBeritaAcara(Agenda $agenda): Response
    {
        $agenda->load([
            'creator.unit',
            'units',
            'attendances.user.unit',
            'documentations',
        ]);

        // Convert signature and selfie images to embedded base64 data URIs for robust standalone rendering
        $attendancesWithMedia = $agenda->attendances->map(function ($att) {
            $selfieBase64 = null;
            if ($att->selfie_path && Storage::disk('public')->exists($att->selfie_path)) {
                $content = Storage::disk('public')->get($att->selfie_path);
                $mime = Storage::disk('public')->mimeType($att->selfie_path) ?: 'image/jpeg';
                $selfieBase64 = 'data:' . $mime . ';base64,' . base64_encode($content);
            }

            $sigBase64 = null;
            if ($att->signature_path && Storage::disk('public')->exists($att->signature_path)) {
                $content = Storage::disk('public')->get($att->signature_path);
                $mime = Storage::disk('public')->mimeType($att->signature_path) ?: 'image/png';
                $sigBase64 = 'data:' . $mime . ';base64,' . base64_encode($content);
            }

            return [
                'model' => $att,
                'selfie_base64' => $selfieBase64,
                'sig_base64' => $sigBase64,
            ];
        });

        // Documentation photos with base64
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

        $html = view('exports.pdf_berita_acara', [
            'agenda' => $agenda,
            'attendances' => $attendancesWithMedia,
            'documentations' => $documentationsWithMedia,
            'generatedAt' => now(),
        ])->render();

        $filename = 'Berita_Acara_Rapat_' . $agenda->slug . '.html';

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}
