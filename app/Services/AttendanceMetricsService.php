<?php

namespace App\Services;

use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\User;

class AttendanceMetricsService
{
    /**
     * Compute consistent attendance compliance metrics across Dashboard, Reports, and User telemetry.
     *
     * @return array{attendance_rate: float, attendance_rate_label: string, attendance_rate_subtext: string}
     */
    public static function forDashboardOrReport(User $user): array
    {
        if ($user->isAdministrator()) {
            return self::forAdministrator();
        }

        if ($user->isAdmin()) {
            return self::forUnitAdmin($user);
        }

        return self::forStaff($user);
    }

    /**
     * @return array{attendance_rate: float, attendance_rate_label: string, attendance_rate_subtext: string}
     */
    private static function forAdministrator(): array
    {
        $totalActiveUsers = User::where('is_active', true)->count();
        $completedAgendas = Agenda::query()
            ->where('status', 'completed')
            ->select(['id', 'target_type', 'is_all_units', 'pimpinan_id', 'notulis_id'])
            ->get();

        $completedCount = $completedAgendas->count();
        if ($completedCount === 0 || $totalActiveUsers === 0) {
            return [
                'attendance_rate' => 100.0,
                'attendance_rate_label' => '100%',
                'attendance_rate_subtext' => 'Belum ada rapat dinas selesai',
            ];
        }

        Agenda::preloadTotalInvited($completedAgendas);
        $expected = (int) $completedAgendas->sum(fn (Agenda $agenda): int => $agenda->total_invited);

        $completedAttendances = Attendance::whereHas('agenda', fn ($q) => $q->where('status', 'completed'))
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->count();

        $rate = $expected <= 0
            ? 100.0
            : round(min(($completedAttendances / $expected) * 100, 100.0), 1);

        return [
            'attendance_rate' => $rate,
            'attendance_rate_label' => "{$rate}%",
            'attendance_rate_subtext' => "Rata-rata Seluruh ASN ({$completedCount} Rapat)",
        ];
    }

    /**
     * @return array{attendance_rate: float, attendance_rate_label: string, attendance_rate_subtext: string}
     */
    private static function forUnitAdmin(User $user): array
    {
        $unitId = $user->unit_id;
        $activeUnitUserIds = $unitId
            ? User::forUnit($unitId)->where('is_active', true)->pluck('id')->all()
            : [];
        $activeUnitUsersCount = count($activeUnitUserIds);

        if ($activeUnitUsersCount === 0) {
            $hasCompleted = Agenda::where('status', 'completed')->exists();

            return [
                'attendance_rate' => 100.0,
                'attendance_rate_label' => '100%',
                'attendance_rate_subtext' => $hasCompleted
                    ? 'Kehadiran Unit (0 Pegawai)'
                    : 'Belum ada rapat dinas selesai',
            ];
        }

        $completedAgendas = Agenda::query()
            ->where('status', 'completed')
            ->where(function ($q) use ($unitId, $activeUnitUserIds) {
                $q->where('is_all_units', true)
                    ->orWhere('target_type', 'all_units')
                    ->orWhereHas('units', fn ($uq) => $uq->where('units.id', $unitId))
                    ->orWhereIn('pimpinan_id', $activeUnitUserIds)
                    ->orWhereIn('notulis_id', $activeUnitUserIds)
                    ->orWhereHas('invitedUsers', fn ($iq) => $iq->whereIn('users.id', $activeUnitUserIds));
            })
            ->select(['id', 'target_type', 'is_all_units', 'pimpinan_id', 'notulis_id'])
            ->with([
                'units:id',
                'invitedUsers' => fn ($iq) => $iq->whereIn('users.id', $activeUnitUserIds)->select('users.id'),
            ])
            ->get();

        if ($completedAgendas->isEmpty()) {
            return [
                'attendance_rate' => 100.0,
                'attendance_rate_label' => '100%',
                'attendance_rate_subtext' => 'Belum ada rapat dinas selesai',
            ];
        }

        $activeUnitUserMap = array_flip($activeUnitUserIds);
        $expected = 0;

        foreach ($completedAgendas as $agenda) {
            if ($agenda->target_type === 'all_units' || $agenda->is_all_units) {
                $expected += $activeUnitUsersCount;

                continue;
            }

            if ($agenda->target_type === 'selected_users') {
                $eligibleInUnit = [];
                foreach ($agenda->invitedUsers as $invited) {
                    if (isset($activeUnitUserMap[$invited->id])) {
                        $eligibleInUnit[$invited->id] = true;
                    }
                }
                if ($agenda->pimpinan_id && isset($activeUnitUserMap[(int) $agenda->pimpinan_id])) {
                    $eligibleInUnit[(int) $agenda->pimpinan_id] = true;
                }
                if ($agenda->notulis_id && isset($activeUnitUserMap[(int) $agenda->notulis_id])) {
                    $eligibleInUnit[(int) $agenda->notulis_id] = true;
                }
                $expected += count($eligibleInUnit);

                continue;
            }

            if ($agenda->units->contains('id', $unitId)) {
                $expected += $activeUnitUsersCount;
            } else {
                $eligibleRolesInUnit = [];
                if ($agenda->pimpinan_id && isset($activeUnitUserMap[(int) $agenda->pimpinan_id])) {
                    $eligibleRolesInUnit[(int) $agenda->pimpinan_id] = true;
                }
                if ($agenda->notulis_id && isset($activeUnitUserMap[(int) $agenda->notulis_id])) {
                    $eligibleRolesInUnit[(int) $agenda->notulis_id] = true;
                }
                $expected += count($eligibleRolesInUnit);
            }
        }

        $agendaIds = $completedAgendas->pluck('id')->all();
        $unitCompletedAttendances = Attendance::whereIn('user_id', $activeUnitUserIds)
            ->whereIn('agenda_id', $agendaIds)
            ->count();

        $rate = $expected <= 0
            ? 100.0
            : round(min(($unitCompletedAttendances / $expected) * 100, 100.0), 1);

        return [
            'attendance_rate' => $rate,
            'attendance_rate_label' => "{$rate}%",
            'attendance_rate_subtext' => "Kehadiran Unit ({$activeUnitUsersCount} Pegawai)",
        ];
    }

    /**
     * @return array{attendance_rate: float, attendance_rate_label: string, attendance_rate_subtext: string}
     */
    private static function forStaff(User $user): array
    {
        $eligibleAgendaIds = Agenda::query()
            ->where('status', 'completed')
            ->where(function ($q) use ($user) {
                $q->where('is_all_units', true)
                    ->orWhere('target_type', 'all_units')
                    ->orWhere('pimpinan_id', $user->id)
                    ->orWhere('notulis_id', $user->id)
                    ->orWhere(function ($sq) use ($user) {
                        $sq->where('target_type', 'selected_users')
                            ->whereHas('invitedUsers', fn ($iq) => $iq->where('users.id', $user->id));
                    });

                if ($user->unit_id) {
                    $q->orWhere(function ($uq) use ($user) {
                        $uq->where(function ($tq) {
                            $tq->whereNull('target_type')
                                ->orWhere('target_type', '!=', 'selected_users');
                        })->whereHas('units', fn ($rel) => $rel->where('units.id', $user->unit_id));
                    });
                }
            })
            ->pluck('id')
            ->all();

        $eligibleCount = count($eligibleAgendaIds);
        if ($eligibleCount === 0) {
            return [
                'attendance_rate' => 100.0,
                'attendance_rate_label' => '100%',
                'attendance_rate_subtext' => 'Belum ada rapat dinas selesai',
            ];
        }

        $attendedCount = Attendance::where('user_id', $user->id)
            ->whereIn('agenda_id', $eligibleAgendaIds)
            ->count();

        $rate = round(min(($attendedCount / $eligibleCount) * 100, 100.0), 1);

        return [
            'attendance_rate' => $rate,
            'attendance_rate_label' => "{$rate}%",
            'attendance_rate_subtext' => "Dari {$eligibleCount} Rapat Selesai",
        ];
    }
}
