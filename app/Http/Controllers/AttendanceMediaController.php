<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class AttendanceMediaController extends Controller
{
    /**
     * Stream protected selfie file.
     */
    public function selfie(Attendance $attendance): Response
    {
        Gate::authorize('view', $attendance);

        return $this->streamProtectedMedia($attendance->selfie_path, 'Berkas foto selfie tidak ditemukan.');
    }

    /**
     * Stream protected digital signature file.
     */
    public function signature(Attendance $attendance): Response
    {
        Gate::authorize('view', $attendance);

        return $this->streamProtectedMedia($attendance->signature_path, 'Berkas tanda tangan digital tidak ditemukan.');
    }

    /**
     * Stream protected attendance biometric file from private local disk with legacy public fallback.
     */
    private function streamProtectedMedia(?string $path, string $notFoundMessage): Response
    {
        if (! $path) {
            abort(404, $notFoundMessage);
        }

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->response(
                    $path,
                    headers: [
                        'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
                    ]
                );
            }
        }

        abort(404, $notFoundMessage);
    }
}
