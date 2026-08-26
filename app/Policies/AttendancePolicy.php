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

        if ($user->isAdmin() && $attendance->agenda->created_by === $user->id) {
            return true;
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

        // 2. User must be eligible based on unit
        if (!$agenda->isUserEligible($user)) {
            return false;
        }

        // 3. User must not have checked in yet
        if ($agenda->hasUserAttended($user)) {
            return false;
        }

        return true;
    }
}
