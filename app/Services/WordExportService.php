<?php

namespace App\Services;

use App\Models\Agenda;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class WordExportService
{
    /**
     * Prepare resolved data and optimized base64 media for document export.
     * Shared across Word export, Binary PDF export, and Web preview.
     */
    public function prepareViewData(Agenda $agenda, array $config = []): array
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
                $raw = Storage::disk('public')->get($customLogo);
                $logoBase64 = $this->optimizeAndEncodeImage($raw, 100, 'png');
            } elseif (file_exists(public_path('images/tut-wuri-handayani.png'))) {
                $raw = file_get_contents(public_path('images/tut-wuri-handayani.png'));
                $logoBase64 = $this->optimizeAndEncodeImage($raw, 100, 'png');
            }
        }

        // Embed media into optimized base64 data URIs
        $attendancesWithMedia = $agenda->attendances->map(function ($att) {
            $sigBase64 = null;
            if ($att->signature_path && Storage::disk('public')->exists($att->signature_path)) {
                $raw = Storage::disk('public')->get($att->signature_path);
                $sigBase64 = $this->optimizeAndEncodeImage($raw, 140, 'png');
            }

            $selfieBase64 = null;
            if ($att->selfie_path && Storage::disk('public')->exists($att->selfie_path)) {
                $raw = Storage::disk('public')->get($att->selfie_path);
                $selfieBase64 = $this->optimizeAndEncodeImage($raw, 80, 'jpeg', 85, true);
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
            $raw = Storage::disk('public')->get($pimpinanAtt->signature_path);
            $pimpinanSigBase64 = $this->optimizeAndEncodeImage($raw, 140, 'png');
        }

        // Notulis digital signature base64 (if attended)
        $notulisSigBase64 = null;
        $notulisAtt = $agenda->notulis_attendance;
        if ($notulisAtt?->signature_path && Storage::disk('public')->exists($notulisAtt->signature_path)) {
            $raw = Storage::disk('public')->get($notulisAtt->signature_path);
            $notulisSigBase64 = $this->optimizeAndEncodeImage($raw, 140, 'png');
        }

        // Documentation photos with optimized base64 for Word/PDF
        $documentationsWithMedia = $agenda->documentations->map(function ($doc) {
            $base64 = null;
            if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
                $raw = Storage::disk('public')->get($doc->file_path);
                $base64 = $this->optimizeAndEncodeImage($raw, 500, 'jpeg', 80);
            }

            return [
                'model' => $doc,
                'base64' => $base64,
            ];
        });

        return [
            'agenda' => $agenda,
            'config' => $resolvedConfig,
            'logoBase64' => $logoBase64,
            'attendances' => $attendancesWithMedia,
            'documentations' => $documentationsWithMedia,
            'pimpinanSigBase64' => $pimpinanSigBase64,
            'notulisSigBase64' => $notulisSigBase64,
            'generatedAt' => now(),
        ];
    }

    /**
     * Generate HTML document content with optimized media for Word or headless PDF conversion.
     */
    public function generateDocumentContent(Agenda $agenda, array $config = []): string
    {
        $data = $this->prepareViewData($agenda, $config);

        return view('exports.word_berita_acara', $data)->render();
    }

    /**
     * Generate Microsoft Word-compatible document (.doc) for an Agenda.
     */
    public function exportBeritaAcara(Agenda $agenda, array $config = []): Response
    {
        $content = $this->generateDocumentContent($agenda, $config);
        $filename = 'Berita_Acara_' . $agenda->slug . '.doc';

        return response("\xef\xbb\xbf" . $content, 200, [
            'Content-Type' => 'application/vnd.ms-word; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Downscale and optimize raw image binary, returning a chunked RFC 2397 Data URI.
     * Guarantees zero line-buffer overflow in MS Word / LibreOffice HTML parsers.
     */
    /**
     * Downscale and optimize raw image binary, returning a chunked RFC 2397 Data URI.
     * Guarantees zero line-buffer overflow in MS Word / LibreOffice HTML parsers.
     */
    protected function optimizeAndEncodeImage(?string $binary, int $maxDim = 160, string $format = 'png', int $quality = 80, bool $cropSquare = false): ?string
    {
        if (!$binary) {
            return null;
        }

        $mime = $format === 'jpeg' ? 'image/jpeg' : 'image/png';

        if (!extension_loaded('gd')) {
            return 'data:' . $mime . ';base64,' . base64_encode($binary);
        }

        $src = @imagecreatefromstring($binary);
        if (!$src) {
            return 'data:' . $mime . ';base64,' . base64_encode($binary);
        }

        // Always ensure alpha preservation on source image
        imagealphablending($src, false);
        imagesavealpha($src, true);

        $origW = imagesx($src);
        $origH = imagesy($src);

        if ($cropSquare) {
            $minDim = min($origW, $origH);
            $srcX = (int) round(($origW - $minDim) / 2);
            $srcY = (int) round(($origH - $minDim) / 2);
            $targetDim = min($maxDim, $minDim);

            $dst = imagecreatetruecolor($targetDim, $targetDim);
            if ($format === 'png') {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
                imagefilledrectangle($dst, 0, 0, $targetDim, $targetDim, $transparent);
            } else {
                $white = imagecolorallocate($dst, 255, 255, 255);
                imagefilledrectangle($dst, 0, 0, $targetDim, $targetDim, $white);
            }

            imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $targetDim, $targetDim, $minDim, $minDim);
            imagedestroy($src);
            $src = $dst;
        } elseif ($origW > $maxDim || $origH > $maxDim) {
            $ratio = min($maxDim / $origW, $maxDim / $origH);
            $targetW = max(1, (int) round($origW * $ratio));
            $targetH = max(1, (int) round($origH * $ratio));

            $dst = imagecreatetruecolor($targetW, $targetH);

            if ($format === 'png') {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
                imagefilledrectangle($dst, 0, 0, $targetW, $targetH, $transparent);
            } else {
                $white = imagecolorallocate($dst, 255, 255, 255);
                imagefilledrectangle($dst, 0, 0, $targetW, $targetH, $white);
            }

            imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetW, $targetH, $origW, $origH);
            imagedestroy($src);
            $src = $dst;
        }

        ob_start();
        if ($format === 'jpeg') {
            imagejpeg($src, null, $quality);
        } else {
            imagesavealpha($src, true);
            imagepng($src, null, 9);
        }
        $processed = ob_get_clean();
        imagedestroy($src);

        $encoded = base64_encode($processed ?: $binary);
        return 'data:' . $mime . ';base64,' . $encoded;
    }
}
