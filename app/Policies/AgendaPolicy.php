<?php

namespace App\Policies;

use App\Models\Agenda;
use App\Models\User;

class AgendaPolicy
{
    /**
     * Determine whether the user can view the admin agenda management index.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator() || $user->isAdmin();
    }

    /**
     * Determine whether the user can view the specific agenda in admin management.
     */
    public function view(User $user, Agenda $agenda): bool
    {
        if ($user->isAdministrator()) {
            return true;
        }

        if ($user->isAdmin()) {
            return $agenda->created_by === $user->id 
                || ($user->unit_id !== null && ($agenda->creator?->unit_id === $user->unit_id || $agenda->units()->where('units.id', $user->unit_id)->exists() || $agenda->is_all_units));
        }

        // Designated Notulis or Pimpinan can view the agenda
        if (($agenda->notulis_id && $agenda->notulis_id === $user->id) 
            || ($agenda->pimpinan_id && $agenda->pimpinan_id === $user->id)) {
            return true;
        }

        // Staff is strictly forbidden from accessing admin agenda management view unless designated
        return false;
    }

    /**
     * Determine whether the user can view public/staff meeting details.
     */
    public function viewStaff(User $user, Agenda $agenda): bool
    {
        // Staff cannot view draft or cancelled agendas (anti-bypass)
        if (in_array($agenda->status, ['draft', 'cancelled'])) {
            return $user->isAdministrator() || ($user->isAdmin() && $agenda->created_by === $user->id);
        }

        if ($user->isAdministrator()) {
            return true;
        }

        // Must be eligible for the agenda (Pleno or matching user's unit)
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

        if ($user->isAdmin()) {
            return $agenda->created_by === $user->id 
                || ($user->unit_id !== null && $agenda->creator?->unit_id === $user->unit_id);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the agenda.
     */
    public function delete(User $user, Agenda $agenda): bool
    {
        if ($user->isAdministrator()) {
            return true;
        }

        if ($user->isAdmin()) {
            return $agenda->created_by === $user->id 
                || ($user->unit_id !== null && $agenda->creator?->unit_id === $user->unit_id);
        }

        return false;
    }

    /**
     * Determine whether the user can change status of the agenda.
     */
    public function manageStatus(User $user, Agenda $agenda): bool
    {
        if ($user->isAdministrator()) {
            return true;
        }

        if ($user->isAdmin()) {
            return $agenda->created_by === $user->id 
                || ($user->unit_id !== null && $agenda->creator?->unit_id === $user->unit_id)
                || ($user->unit_id !== null && $agenda->units()->where('units.id', $user->unit_id)->exists());
        }

        return false;
    }

    /**
     * Determine whether the user can manage notulensi and documentation.
     */
    public function manageMinutes(User $user, Agenda $agenda): bool
    {
        // Cancelled meetings cannot have their minutes modified
        if ($agenda->status === 'cancelled') {
            return false;
        }

        if ($user->isAdministrator()) {
            return true;
        }

        // Creator can always manage minutes unless cancelled
        if ($agenda->created_by === $user->id) {
            return true;
        }

        if ($user->isAdmin()) {
            return ($user->unit_id !== null && $agenda->creator?->unit_id === $user->unit_id)
                || ($user->unit_id !== null && $agenda->units()->where('units.id', $user->unit_id)->exists());
        }

        // Designated Notulis or Pimpinan for regular staff (pegawai):
        // HANYA BERLAKU SAAT STATUS RAPAT SEDANG BERLANGSUNG (ongoing)
        if ($agenda->status === 'ongoing') {
            if (($agenda->notulis_id && $agenda->notulis_id === $user->id) 
                || ($agenda->pimpinan_id && $agenda->pimpinan_id === $user->id)) {
                return true;
            }
        }

        return false;
    }
}
