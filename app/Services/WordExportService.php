<?php

namespace App\Services;

use App\Models\Agenda;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WordExportService
{
    /**
     * Upper bound on the pixel dimensions accepted from an uploaded source image.
     * Anything larger is refused before it is handed to GD.
     */
    private const MAX_SOURCE_DIMENSION = 4000;

    /**
     * Prepare resolved data and optimized base64 media for document export.
     * Shared across Word export, Binary PDF export, and Web preview.
     *
     * @param  array<string,mixed>  $config  Report-config overrides for this single export.
     * @param  string  $pageMode  'named' emits "@page Section1" for Word and the
     *                           LibreOffice fallback; 'plain' emits a bare
     *                           "@page", which is the only form Dompdf reads.
     * @return array<string,mixed>
     */
    public function prepareViewData(Agenda $agenda, array $config = [], string $pageMode = 'named'): array
    {
        @ini_set('memory_limit', '256M');
        @set_time_limit(120);

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
        // Pixel dimensions of the source logo, so the template can render it at
        // its true aspect ratio. Word's HTML importer ignores object-fit, so a
        // fixed width/height pair that does not match the source would stretch
        // the emblem - the most visible element on the letterhead.
        $logoSize = null;
        if ($resolvedConfig['show_logo'] ?? true) {
            try {
                $customLogo = $resolvedConfig['custom_logo_path'] ?? null;
                if ($customLogo && Storage::disk('public')->exists($customLogo)) {
                    $raw = Storage::disk('public')->get($customLogo);
                    $logoSize = $this->assertSafeImageBinary($raw);
                    $logoBase64 = $this->optimizeAndEncodeImage($raw, 100, 'png');
                } elseif (file_exists(public_path('images/tut-wuri-handayani.png'))) {
                    $raw = file_get_contents(public_path('images/tut-wuri-handayani.png'));
                    $logoSize = $this->assertSafeImageBinary($raw);
                    $logoBase64 = $this->optimizeAndEncodeImage($raw, 100, 'png');
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to load institution logo for document export: ' . $e->getMessage());
            }
        }

        // Embed media into optimized base64 data URIs
        $attendancesWithMedia = $agenda->attendances->map(function ($att) {
            $sigBase64 = null;
            try {
                if ($att->signature_path && Storage::disk('public')->exists($att->signature_path)) {
                    $raw = Storage::disk('public')->get($att->signature_path);
                    $sigBase64 = $this->optimizeAndEncodeImage($raw, 140, 'png');
                }
            } catch (\Throwable $e) {
                Log::warning("Failed to process signature for attendance {$att->id}: " . $e->getMessage());
            }

            $selfieBase64 = null;
            try {
                if ($att->selfie_path && Storage::disk('public')->exists($att->selfie_path)) {
                    $raw = Storage::disk('public')->get($att->selfie_path);
                    $selfieBase64 = $this->optimizeAndEncodeImage($raw, 80, 'jpeg', 85, true);
                }
            } catch (\Throwable $e) {
                Log::warning("Failed to process selfie for attendance {$att->id}: " . $e->getMessage());
            }

            return [
                'model' => $att,
                'sig_base64' => $sigBase64,
                'selfie_base64' => $selfieBase64,
            ];
        });

        // Pimpinan digital signature base64 (if attended)
        $pimpinanSigBase64 = null;
        try {
            $pimpinanAtt = $agenda->pimpinan_attendance;
            if ($pimpinanAtt?->signature_path && Storage::disk('public')->exists($pimpinanAtt->signature_path)) {
                $raw = Storage::disk('public')->get($pimpinanAtt->signature_path);
                $pimpinanSigBase64 = $this->optimizeAndEncodeImage($raw, 140, 'png');
            }
        } catch (\Throwable $e) {
            Log::warning("Failed to process meeting leader signature: " . $e->getMessage());
        }

        // Notulis digital signature base64 (if attended)
        $notulisSigBase64 = null;
        try {
            $notulisAtt = $agenda->notulis_attendance;
            if ($notulisAtt?->signature_path && Storage::disk('public')->exists($notulisAtt->signature_path)) {
                $raw = Storage::disk('public')->get($notulisAtt->signature_path);
                $notulisSigBase64 = $this->optimizeAndEncodeImage($raw, 140, 'png');
            }
        } catch (\Throwable $e) {
            Log::warning("Failed to process minute taker signature: " . $e->getMessage());
        }

        // Documentation photos with optimized base64 for Word/PDF
        $documentationsWithMedia = $agenda->documentations->map(function ($doc) {
            $base64 = null;
            try {
                if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
                    $raw = Storage::disk('public')->get($doc->file_path);
                    $base64 = $this->optimizeAndEncodeImage($raw, 500, 'jpeg', 80);
                }
            } catch (\Throwable $e) {
                Log::warning("Failed to process documentation photo {$doc->id}: " . $e->getMessage());
            }

            return [
                'model' => $doc,
                'base64' => $base64,
            ];
        });

        return [
            'agenda' => $agenda,
            'config' => $resolvedConfig,
            'pageMode' => $pageMode,
            'logoBase64' => $logoBase64,
            'logoSize' => $logoSize,
            'attendances' => $attendancesWithMedia,
            'documentations' => $documentationsWithMedia,
            'pimpinanSigBase64' => $pimpinanSigBase64,
            'notulisSigBase64' => $notulisSigBase64,
            'generatedAt' => now(),
        ];
    }

    /**
     * Generate HTML document content with optimized media for Word or headless PDF conversion.
     *
     * @param  array<string,mixed>  $config  Report-config overrides for this single export.
     * @param  string  $pageMode  'named' or 'plain'; see prepareViewData().
     */
    public function generateDocumentContent(Agenda $agenda, array $config = [], string $pageMode = 'named'): string
    {
        $data = $this->prepareViewData($agenda, $config, $pageMode);

        return view('exports.word_berita_acara', $data)->render();
    }

    /**
     * Generate Microsoft Word-compatible document (.doc) for an Agenda.
     *
     * @param  array<string,mixed>  $config  Report-config overrides for this single export.
     */
    public function exportBeritaAcara(Agenda $agenda, array $config = []): Response
    {
        $content = $this->generateDocumentContent($agenda, $config);
        $filename = 'Berita_Acara_' . $agenda->slug . '.doc';

        return response("\xef\xbb\xbf" . $content, 200, [
            'Content-Type' => 'application/vnd.ms-word; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
            'X-Accel-Buffering' => 'no', // Bypass Nginx FastCGI buffer proxy caching
        ]);
    }

    /**
     * Downscale and optimize raw image binary, returning a chunked RFC 2397 Data URI.
     * Guarantees zero line-buffer overflow in MS Word / LibreOffice HTML parsers.
     *
     * Returns null when the payload is not a genuine, within-limits raster image.
     * Callers treat null as "omit this image" so a malformed or hostile upload can
     * never reach GD, nor bloat the generated document.
     */
    protected function optimizeAndEncodeImage(?string $binary, int $maxDim = 160, string $format = 'png', int $quality = 80, bool $cropSquare = false): ?string
    {
        if (!$binary) {
            return null;
        }

        if ($this->assertSafeImageBinary($binary) === null) {
            return null;
        }

        $mime = $format === 'jpeg' ? 'image/jpeg' : 'image/png';
        $encode = function (?string $payload) use ($mime): string {
            return 'data:' . $mime . ';base64,' . "\n" . rtrim(chunk_split(base64_encode($payload), 1000, "\n"));
        };

        // GD unavailable: the binary is already verified above, so embedding it
        // verbatim is safe. It simply will not be downscaled.
        if (! extension_loaded('gd')) {
            return $encode($binary);
        }

        try {
            $src = @imagecreatefromstring($binary);
            if (!$src) {
                return null;
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
                // Level 6 is the practical sweet spot; 9 costs a lot of CPU for
                // a few percent of size across hundreds of signatures.
                imagepng($src, null, 6);
            }
            $processed = ob_get_clean();
            imagedestroy($src);

            return $encode($processed ?: $binary);
        } catch (\Throwable $e) {
            Log::warning('Image optimization failed, embedding verified original: ' . $e->getMessage());
            return $encode($binary);
        }
    }

    /**
     * Verify that a binary blob really is a supported raster image of sane dimensions.
     *
     * The binary originates from user uploads, so it is treated as hostile: it is
     * sniffed rather than trusted, restricted to a known set of decoders, and capped
     * in pixel count so a crafted header cannot trigger a huge allocation.
     *
     * @return array{0:int,1:int}|null Decoded width/height, or null when rejected.
     */
    private function assertSafeImageBinary(string $binary): ?array
    {
        // getimagesizefromstring() lives in ext/standard and does not require GD,
        // but guard anyway so a stripped-down build degrades to "reject" instead
        // of raising a fatal Error.
        if (! function_exists('getimagesizefromstring')) {
            Log::warning('Image header inspection unavailable; refusing to embed unverified media.', [
                'bytes' => strlen($binary),
            ]);
            return null;
        }

        $info = @getimagesizefromstring($binary);
        if ($info === false || ! isset($info[0], $info[1], $info[2])) {
            Log::warning('Rejected non-image binary from document export', [
                'bytes' => strlen($binary),
            ]);
            return null;
        }

        [$width, $height, $type] = $info;

        $allowedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];
        if (! in_array($type, $allowedTypes, true)) {
            Log::warning('Rejected unsupported image type from document export', [
                'type' => $type,
                'bytes' => strlen($binary),
            ]);
            return null;
        }

        // Reject absurd pixel counts (decompression-bomb / GD CVE mitigation).
        if ($width <= 0 || $height <= 0 || $width > self::MAX_SOURCE_DIMENSION || $height > self::MAX_SOURCE_DIMENSION) {
            Log::warning('Rejected out-of-bounds image dimensions from document export', [
                'width' => $width,
                'height' => $height,
            ]);
            return null;
        }

        return [$width, $height];
    }
}
