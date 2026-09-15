<?php

namespace App\Services;

use App\Models\Agenda;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class AgendaConflictService
{
    /**
     * Toleransi waktu lampau untuk input agenda baru (dalam menit).
     */
    public const PAST_TOLERANCE_MINUTES = 15;

    /**
     * Check all potential conflicts for agenda creation or update.
     *
     * @param array $data Validated or raw input data
     * @param int|null $ignoreAgendaId Agenda ID to exclude when updating
     * @param User|null $user The user performing the action
     * @return array{has_conflicts: bool, errors: array<string, array<string>>}
     */
    public static function checkConflicts(array $data, ?int $ignoreAgendaId = null, ?User $user = null): array
    {
        $errors = [];

        if (empty($data['waktu_mulai'])) {
            return ['has_conflicts' => false, 'errors' => []];
        }

        try {
            $mulai = Carbon::parse($data['waktu_mulai']);
        } catch (\Throwable) {
            return ['has_conflicts' => false, 'errors' => []];
        }

        $selesai = !empty($data['waktu_selesai']) ? Carbon::parse($data['waktu_selesai']) : null;
        $status = $data['status'] ?? 'scheduled';
        $tipeRapat = $data['tipe_rapat'] ?? 'offline';
        $ruangan = isset($data['lokasi_ruang']) ? trim((string) $data['lokasi_ruang']) : null;
        $pimpinanId = !empty($data['pimpinan_id']) ? (int) $data['pimpinan_id'] : null;
        $notulisId = !empty($data['notulis_id']) ? (int) $data['notulis_id'] : null;
        $isAllUnits = isset($data['is_all_units']) ? (bool) $data['is_all_units'] : true;
        $unitIds = $data['unit_ids'] ?? [];

        // 1. Pencegahan Waktu Mulai di Masa Lampau (Hanya untuk Agenda Baru 'scheduled')
        if ($ignoreAgendaId === null && $status === 'scheduled') {
            $pastError = self::validatePastDateTime($mulai);
            if ($pastError) {
                $errors['waktu_mulai'][] = $pastError;
            }
        }

        // 2. Pencegahan Rapat Unit Belum Selesai (Active Ongoing Meeting)
        if ($user) {
            $ongoingError = self::validateOngoingUnitConflict($user, $status, $mulai, $ignoreAgendaId);
            if ($ongoingError) {
                $errors['status'][] = $ongoingError;
            }
        }

        // 3. Pencegahan Bentrok Ruangan Fisik (Physical Room Collision)
        if (in_array($tipeRapat, ['offline', 'hybrid']) && filled($ruangan)) {
            $roomError = self::validateRoomConflict($ignoreAgendaId, $ruangan, $mulai, $selesai);
            if ($roomError) {
                $errors['lokasi_ruang'][] = $roomError;
            }
        }

        // 4. Pencegahan Bentrok Jadwal Unit Kerja (Unit Schedule Collision)
        $unitError = self::validateUnitScheduleConflict($ignoreAgendaId, $user, $isAllUnits, $unitIds, $mulai, $selesai);
        if ($unitError) {
            $errors['waktu_mulai'][] = $unitError;
        }

        // 5. Pencegahan Bentrok Personel (Pimpinan & Notulis Double-Booking)
        $personnelErrors = self::validatePersonnelConflict($ignoreAgendaId, $pimpinanId, $notulisId, $mulai, $selesai);
        foreach ($personnelErrors as $field => $msg) {
            $errors[$field][] = $msg;
        }

        return [
            'has_conflicts' => !empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Mencegah agenda baru berstatus 'scheduled' dibuat dengan waktu mulai di masa lampau.
     */
    public static function validatePastDateTime(Carbon $mulai): ?string
    {
        $threshold = now()->subMinutes(self::PAST_TOLERANCE_MINUTES);
        if ($mulai->lt($threshold)) {
            return "Waktu mulai rapat terjadwal tidak boleh di masa lampau (minimal {$threshold->format('d/m/Y H:i')} WIB).";
        }
        return null;
    }

    /**
     * Memeriksa apakah unit penyelenggara masih memiliki rapat aktif berstatus 'ongoing'.
     */
    public static function validateOngoingUnitConflict(User $user, string $newStatus, Carbon $mulai, ?int $ignoreAgendaId = null): ?string
    {
        // Administrator memiliki fleksibilitas universal
        if ($user->isAdministrator()) {
            return null;
        }

        $unitId = $user->unit_id;
        if (!$unitId) {
            return null;
        }

        // Cari apakah unit pengguna memiliki rapat yang sedang berlangsung (ongoing)
        $ongoingQuery = Agenda::query()
            ->where('status', 'ongoing')
            ->where(function (Builder $q) use ($user, $unitId) {
                $q->where('created_by', $user->id)
                  ->orWhereHas('units', function (Builder $uq) use ($unitId) {
                      $uq->where('units.id', $unitId);
                  });
            });

        if ($ignoreAgendaId) {
            $ongoingQuery->where('id', '!=', $ignoreAgendaId);
        }

        $ongoingAgenda = $ongoingQuery->first();
        if (!$ongoingAgenda) {
            return null;
        }

        // Jika agenda baru langsung disetel 'ongoing', ATAU dimulai hari ini saat rapat lama masih berlangsung
        if ($newStatus === 'ongoing' || $mulai->isToday()) {
            $namaUnit = $user->unit?->nama_unit ?? 'Unit Kerja Anda';
            return "Unit kerja {$namaUnit} masih memiliki agenda rapat yang sedang berlangsung: '{$ongoingAgenda->judul_rapat}'. Harap selesaikan rapat tersebut sebelum memulai rapat baru hari ini.";
        }

        return null;
    }

    /**
     * Memeriksa apakah ruangan fisik sudah digunakan oleh agenda lain pada rentang waktu yang beririsan.
     */
    public static function validateRoomConflict(?int $ignoreAgendaId, string $ruangan, Carbon $mulai, ?Carbon $selesai): ?string
    {
        $cleanRoom = strtolower(trim($ruangan));

        $query = Agenda::query()
            ->whereIn('status', ['scheduled', 'ongoing'])
            ->whereIn('tipe_rapat', ['offline', 'hybrid'])
            ->whereRaw('LOWER(TRIM(lokasi_ruang)) = ?', [$cleanRoom]);

        if ($ignoreAgendaId) {
            $query->where('id', '!=', $ignoreAgendaId);
        }

        self::applyTimeOverlapScope($query, $mulai, $selesai);

        $conflictingAgenda = $query->first();
        if ($conflictingAgenda) {
            $rentang = $conflictingAgenda->rentang_waktu;
            return "Ruang rapat '{$conflictingAgenda->lokasi_ruang}' telah digunakan oleh agenda '{$conflictingAgenda->judul_rapat}' pada jam {$rentang}. Harap pilih ruangan atau jadwal lain.";
        }

        return null;
    }

    /**
     * Memeriksa apakah ada bentrok jadwal rapat pada unit yang sama.
     */
    public static function validateUnitScheduleConflict(
        ?int $ignoreAgendaId,
        ?User $user,
        bool $isAllUnits,
        array $unitIds,
        Carbon $mulai,
        ?Carbon $selesai
    ): ?string {
        $query = Agenda::query()
            ->whereIn('status', ['scheduled', 'ongoing']);

        if ($ignoreAgendaId) {
            $query->where('id', '!=', $ignoreAgendaId);
        }

        self::applyTimeOverlapScope($query, $mulai, $selesai);

        // Kasus 1: Rapat Pleno (is_all_units = true)
        if ($isAllUnits) {
            // Jika ada rapat pleno lain di jam yang sama
            $existingPleno = (clone $query)->where('is_all_units', true)->first();
            if ($existingPleno) {
                return "Telah terdapat agenda rapat pleno '{$existingPleno->judul_rapat}' ({$existingPleno->rentang_waktu}) di waktu yang sama. Rapat pleno melibatkan seluruh unit kerja.";
            }
        }

        // Kasus 2: Rapat Unit Terbatas (Cek jika unit pembuat yang sama mengadakan 2 rapat sekaligus)
        if ($user && $user->unit_id && !$user->isAdministrator()) {
            $unitId = $user->unit_id;
            $existingUnitMeeting = (clone $query)
                ->where(function (Builder $q) use ($unitId, $user) {
                    $q->where('created_by', $user->id)
                      ->orWhereHas('units', function (Builder $uq) use ($unitId) {
                          $uq->where('units.id', $unitId);
                      });
                })
                ->first();

            if ($existingUnitMeeting) {
                $namaUnit = $user->unit?->nama_unit ?? 'Unit Kerja Anda';
                return "Unit kerja {$namaUnit} telah memiliki agenda rapat '{$existingUnitMeeting->judul_rapat}' pada rentang waktu yang sama ({$existingUnitMeeting->rentang_waktu}).";
            }
        }

        return null;
    }

    /**
     * Memeriksa apakah pimpinan atau notulis yang ditunjuk sudah bertugas pada agenda lain di jam tersebut.
     *
     * @return array<string, string>
     */
    public static function validatePersonnelConflict(
        ?int $ignoreAgendaId,
        ?int $pimpinanId,
        ?int $notulisId,
        Carbon $mulai,
        ?Carbon $selesai
    ): array {
        $conflicts = [];

        // 1. Cek bentrok pimpinan
        if ($pimpinanId) {
            $queryPimpinan = Agenda::query()
                ->whereIn('status', ['scheduled', 'ongoing'])
                ->where(function (Builder $q) use ($pimpinanId) {
                    $q->where('pimpinan_id', $pimpinanId)
                      ->orWhere(function (Builder $sub) use ($pimpinanId) {
                          $sub->whereNull('pimpinan_id')
                              ->where('created_by', $pimpinanId);
                      });
                });

            if ($ignoreAgendaId) {
                $queryPimpinan->where('id', '!=', $ignoreAgendaId);
            }

            self::applyTimeOverlapScope($queryPimpinan, $mulai, $selesai);

            $clashingAgenda = $queryPimpinan->first();
            if ($clashingAgenda) {
                $pejabat = User::find($pimpinanId);
                $nama = $pejabat?->name ?? 'Pegawai';
                $conflicts['pimpinan_id'] = "Pegawai '{$nama}' telah ditugaskan sebagai Pemimpin Rapat pada '{$clashingAgenda->judul_rapat}' ({$clashingAgenda->rentang_waktu}).";
            }
        }

        // 2. Cek bentrok notulis
        if ($notulisId) {
            $queryNotulis = Agenda::query()
                ->whereIn('status', ['scheduled', 'ongoing'])
                ->where('notulis_id', $notulisId);

            if ($ignoreAgendaId) {
                $queryNotulis->where('id', '!=', $ignoreAgendaId);
            }

            self::applyTimeOverlapScope($queryNotulis, $mulai, $selesai);

            $clashingAgenda = $queryNotulis->first();
            if ($clashingAgenda) {
                $pegawai = User::find($notulisId);
                $nama = $pegawai?->name ?? 'Pegawai';
                $conflicts['notulis_id'] = "Pegawai '{$nama}' telah ditugaskan sebagai Notulis pada '{$clashingAgenda->judul_rapat}' ({$clashingAgenda->rentang_waktu}).";
            }
        }

        return $conflicts;
    }

    /**
     * Menerapkan filter interval waktu tumpang tindih (Time Interval Overlap Scope).
     * Logika: StartA < EndB AND EndA > StartB
     */
    public static function applyTimeOverlapScope(Builder $query, Carbon $mulai, ?Carbon $selesai): void
    {
        // Jika agenda baru tidak memiliki waktu selesai, estimasi hingga akhir hari (endOfDay)
        $effectiveEnd = $selesai ? $selesai->copy() : $mulai->copy()->endOfDay();

        $query->where(function (Builder $q) use ($mulai, $effectiveEnd) {
            // Syarat 1: Agenda lain mulai sebelum waktu selesai agenda baru
            $q->where('waktu_mulai', '<', $effectiveEnd)
              ->where(function (Builder $sub) use ($mulai) {
                  // Syarat 2A: Agenda lain memiliki waktu_selesai dan waktu_selesai > waktu_mulai agenda baru
                  $sub->where(function (Builder $s1) use ($mulai) {
                      $s1->whereNotNull('waktu_selesai')
                         ->where('waktu_selesai', '>', $mulai);
                  })
                  // Syarat 2B: Agenda lain "Hingga Selesai" (waktu_selesai IS NULL) pada hari yang sama
                  ->orWhere(function (Builder $s2) use ($mulai) {
                      $s2->whereNull('waktu_selesai')
                         ->whereDate('waktu_mulai', '=', $mulai->toDateString());
                  });
              });
        });
    }
}
