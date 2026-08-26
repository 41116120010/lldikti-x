<?php

namespace App\Policies;

use App\Models\Agenda;
use App\Models\User;

class AgendaPolicy
{
    /**
     * Determine whether the user can view any agendas.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the specific agenda.
     */
    public function view(User $user, Agenda $agenda): bool
    {
        if ($user->isAdministrator()) {
            return true;
        }

        if ($agenda->created_by === $user->id) {
            return true;
        }

        return $agenda->isUserEligible($user);
    }

    /**
     * Determine whether the user can create agendas.
     */
    public function create(User $user): bool
    {
        return $user->isAdministrator() || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the agenda.
     */
    public function update(User $user, Agenda $agenda): bool
    {
        if ($user->isAdministrator()) {
            return true;
        }

        return $user->isAdmin() && $agenda->created_by === $user->id;
    }

    /**
     * Determine whether the user can delete the agenda.
     */
    public function delete(User $user, Agenda $agenda): bool
    {
        if ($user->isAdministrator()) {
            return true;
        }

        return $user->isAdmin() && $agenda->created_by === $user->id;
    }

    /**
     * Determine whether the user can change status of the agenda.
     */
    public function manageStatus(User $user, Agenda $agenda): bool
    {
        if ($user->isAdministrator()) {
            return true;
        }

        return $user->isAdmin() && $agenda->created_by === $user->id;
    }

    /**
     * Determine whether the user can manage notulensi and documentation.
     */
    public function manageMinutes(User $user, Agenda $agenda): bool
    {
        if ($user->isAdministrator()) {
            return true;
        }

        return $user->isAdmin() && $agenda->created_by === $user->id;
    }
}
