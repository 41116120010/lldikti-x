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

        if (!$attendance->selfie_path || !Storage::disk('public')->exists($attendance->selfie_path)) {
            abort(404, 'Berkas foto selfie tidak ditemukan.');
        }

        return Storage::disk('public')->response(
            $attendance->selfie_path,
            headers: [
                'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            ]
        );
    }

    /**
     * Stream protected digital signature file.
     */
    public function signature(Attendance $attendance): Response
    {
        Gate::authorize('view', $attendance);

        if (!$attendance->signature_path || !Storage::disk('public')->exists($attendance->signature_path)) {
            abort(404, 'Berkas tanda tangan digital tidak ditemukan.');
        }

        return Storage::disk('public')->response(
            $attendance->signature_path,
            headers: [
                'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            ]
        );
    }
}
