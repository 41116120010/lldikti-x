<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any users list.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator() || $user->isAdmin();
    }

    /**
     * Determine whether the user can view a specific user.
     */
    public function view(User $user, User $target): bool
    {
        if ($user->isAdministrator()) {
            return true;
        }

        if ($user->isAdmin() && $user->unit_id !== null && $user->unit_id === $target->unit_id) {
            return true;
        }

        return $user->id === $target->id;
    }

    /**
     * Determine whether the user can create new users.
     */
    public function create(User $user): bool
    {
        return $user->isAdministrator() || $user->isAdmin();
    }

    /**
     * Determine whether the user can update a specific user.
     */
    public function update(User $user, User $target): bool
    {
        if ($user->isAdministrator()) {
            return true;
        }

        // Admin Unit can only update users within their own unit and cannot modify Superadmin accounts
        if ($user->isAdmin() && $user->unit_id !== null && $user->unit_id === $target->unit_id) {
            return !$target->isAdministrator();
        }

        return $user->id === $target->id;
    }

    /**
     * Determine whether the user can delete a specific user.
     */
    public function delete(User $user, User $target): bool
    {
        // Cannot delete self
        if ($user->id === $target->id) {
            return false;
        }

        if ($user->isAdministrator()) {
            return true;
        }

        // Admin Unit can only delete users in their own unit and cannot delete Superadmin
        if ($user->isAdmin() && $user->unit_id !== null && $user->unit_id === $target->unit_id) {
            return !$target->isAdministrator();
        }

        return false;
    }
}
