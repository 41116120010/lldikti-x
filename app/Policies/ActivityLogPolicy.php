<?php

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\User;

class ActivityLogPolicy
{
    /**
     * Determine whether the user can view any system activity logs.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can view a specific activity log.
     */
    public function view(User $user, ActivityLog $activityLog): bool
    {
        return $user->isAdministrator();
    }
}
