<?php

namespace App\Policies;

use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    /**
     * Determine whether the user can view attendances list.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view a specific attendance record.
     */
    public function view(User $user, Attendance $attendance): bool
    {
        if ($user->isAdministrator()) {
            return true;
        }

        if ($user->isAdmin()) {
            return $attendance->agenda->created_by === $user->id
                || ($user->unit_id !== null && $attendance->user?->unit_id === $user->unit_id)
                || ($user->unit_id !== null && $attendance->agenda->units()->where('units.id', $user->unit_id)->exists())
                || $attendance->agenda->is_all_units;
        }

        return $user->id === $attendance->user_id;
    }

    /**
     * Determine whether the user can check in to an agenda.
     */
    public function checkIn(User $user, Agenda $agenda): bool
    {
        // 1. Agenda must be ongoing
        if ($agenda->status !== 'ongoing') {
            return false;
        }

        // 2. User must be eligible based on unit (Administrator has global attendance authority)
        if (!$user->isAdministrator() && !$agenda->isUserEligible($user)) {
            return false;
        }

        // 3. User must not have checked in yet
        if ($agenda->hasUserAttended($user)) {
            return false;
        }

        return true;
    }
}
