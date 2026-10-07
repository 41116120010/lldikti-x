<?php

namespace App\Services;

use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttendanceService
{
    /**
     * Record an attendance check-in atomically with biometric files and activity logging.
     *
     * @param  array{
     *     selfie_data?: ?string,
     *     selfie_file?: ?UploadedFile,
     *     signature_data?: ?string,
     *     signature_file?: ?UploadedFile,
     *     ip_address?: ?string,
     *     user_agent?: ?string
     * } $data
     * @return array{attendance: Attendance, is_duplicate: bool}
     *
     * @throws \DomainException If agenda is not ongoing.
     * @throws \Illuminate\Auth\Access\AuthorizationException If user is not eligible for the agenda.
     */
    public function recordAttendance(Agenda $agenda, User $user, array $data): array
    {
        // 1. Check if user already attended
        $existing = Attendance::where('agenda_id', $agenda->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return [
                'attendance' => $existing,
                'is_duplicate' => true,
            ];
        }

        // 2. Validate agenda status
        if ($agenda->status !== 'ongoing') {
            throw new \DomainException('Sesi presensi untuk agenda rapat ini tidak sedang dibuka.');
        }

        // 3. Validate user eligibility
        if (!$agenda->isUserEligible($user)) {
            throw new AuthorizationException('Unit kerja Anda tidak termasuk dalam daftar undangan agenda rapat ini.');
        }

        $savedPaths = [];

        try {
            $attendance = DB::transaction(function () use ($agenda, $user, $data, &$savedPaths) {
                $selfiePath = $this->saveImageFile(
                    dataUri: $data['selfie_data'] ?? null,
                    file: $data['selfie_file'] ?? null,
                    directory: "attendances/{$agenda->id}/selfies",
                    prefix: "selfie_{$user->id}"
                );
                $savedPaths[] = $selfiePath;

                $signaturePath = $this->saveImageFile(
                    dataUri: $data['signature_data'] ?? null,
                    file: $data['signature_file'] ?? null,
                    directory: "attendances/{$agenda->id}/signatures",
                    prefix: "sig_{$user->id}"
                );
                $savedPaths[] = $signaturePath;

                $record = Attendance::create([
                    'agenda_id' => $agenda->id,
                    'user_id' => $user->id,
                    'signed_at' => now(),
                    'selfie_path' => $selfiePath,
                    'signature_path' => $signaturePath,
                    'ip_address' => $data['ip_address'] ?? null,
                    'user_agent' => $data['user_agent'] ?? null,
                ]);

                ActivityLogger::log(
                    type: 'RECORD_ATTENDANCE',
                    description: "Pegawai {$user->name} (NIP: {$user->nip}) melakukan presensi pada agenda '{$agenda->judul_rapat}'.",
                    targetModel: Attendance::class,
                    targetId: $record->id,
                    properties: [
                        'agenda_id' => $agenda->id,
                        'user_id' => $user->id,
                        'signed_at' => $record->signed_at->toDateTimeString(),
                    ]
                );

                return $record;
            });

            return [
                'attendance' => $attendance,
                'is_duplicate' => false,
            ];
        } catch (QueryException $e) {
            $this->cleanupFiles($savedPaths);

            // Handle race-condition duplicate check-in collision (SQLSTATE 23000 / Error 1062)
            $existing = Attendance::where('agenda_id', $agenda->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existing) {
                return [
                    'attendance' => $existing,
                    'is_duplicate' => true,
                ];
            }

            throw $e;
        } catch (\Throwable $e) {
            $this->cleanupFiles($savedPaths);

            throw $e;
        }
    }

    /**
     * Helper to process and store Base64 Data URI or File Upload to disk safely.
     *
     * @param  string|null  $dataUri   Raw `data:image/...;base64,...` payload from canvas.
     * @param  \Illuminate\Http\UploadedFile|null  $file Direct upload fallback.
     * @param  string  $directory Disk-relative directory.
     * @param  string  $prefix    Human-readable filename prefix, e.g. `selfie_7`.
     * @return string Path relative to the public disk.
     */
    public function saveImageFile(?string $dataUri, ?UploadedFile $file, string $directory, string $prefix): string
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        // 1. If uploaded as direct file
        if ($file) {
            $ext = strtolower((string) $file->guessExtension());
            if (!in_array($ext, $allowedExtensions, true)) {
                throw new \InvalidArgumentException('Tipe berkas gambar tidak diizinkan.');
            }

            $filename = "{$prefix}_" . Str::random(16) . '.' . $ext;
            return $file->storeAs($directory, $filename, 'public');
        }

        // 2. If uploaded as Base64 Data URI from Canvas
        if ($dataUri && preg_match('/^data:image\/([a-zA-Z0-9\+]+);base64,/', $dataUri, $type)) {
            $rawExtension = strtolower($type[1]);
            $extension = ($rawExtension === 'jpeg') ? 'jpg' : $rawExtension;

            if (!in_array($extension, $allowedExtensions, true)) {
                throw new \InvalidArgumentException('Format gambar base64 tidak didukung.');
            }

            $data = substr($dataUri, strpos($dataUri, ',') + 1);
            $decoded = base64_decode($data, true);
            if ($decoded === false) {
                throw new \InvalidArgumentException('Format gambar base64 tidak valid.');
            }

            $filename = "{$prefix}_" . Str::random(16) . ".{$extension}";
            $path = "{$directory}/{$filename}";

            Storage::disk('public')->put($path, $decoded);

            return $path;
        }

        throw new \InvalidArgumentException('Data berkas gambar tidak ditemukan.');
    }

    /**
     * Safely cleanup files on storage failure.
     *
     * @param  list<string>  $paths
     */
    protected function cleanupFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }
}
