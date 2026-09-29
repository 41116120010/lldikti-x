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
                || ($user->unit_id !== null && ($agenda->creatorUnitId() === $user->unit_id || $agenda->units()->where('units.id', $user->unit_id)->exists() || $agenda->is_all_units));
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
            // Pembuat agenda atau rekan admin dari unit penyelenggara yang sama
            if ($agenda->created_by === $user->id || ($user->unit_id !== null && $agenda->creatorUnitId() === $user->unit_id)) {
                return true;
            }

            // Admin unit yang ditunjuk sebagai pimpinan atau notulis khusus saat rapat berlangsung
            if ($this->isAppointedDuringSession($user, $agenda)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can delete the agenda.
     * Hak tertinggi: Administrator (semua unit).
     * Terbatas: Admin Unit yang berkaitan (pembuat, rekan se-unit penyelenggara, atau unit sasaran non-universal).
     */
    public function delete(User $user, Agenda $agenda): bool
    {
        // 1. Administrator memiliki hak tertinggi untuk menghapus agenda di semua unit
        if ($user->isAdministrator()) {
            return true;
        }

        // 2. Admin Unit hanya dapat menghapus agenda yang berkaitan dengan unit kerjanya
        if ($user->isAdmin()) {
            // Pembuat langsung agenda
            if ($agenda->created_by === $user->id) {
                return true;
            }

            if ($user->unit_id !== null) {
                // Unit penyelenggara sama dengan unit kerja admin
                if ($agenda->creatorUnitId() === $user->unit_id) {
                    return true;
                }

                // Agenda rapat khusus non-universal yang ditujukan untuk unit kerja admin
                if (! $agenda->is_all_units && $agenda->units()->where('units.id', $user->unit_id)->exists()) {
                    return true;
                }
            }
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
            // Pembuat agenda atau rekan admin dari unit penyelenggara yang sama
            if ($agenda->created_by === $user->id || ($user->unit_id !== null && $agenda->creatorUnitId() === $user->unit_id)) {
                return true;
            }

            // Admin unit yang ditunjuk sebagai pimpinan atau notulis khusus saat rapat berlangsung
            if ($this->isAppointedDuringSession($user, $agenda)) {
                return true;
            }
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

        // Pembuat agenda atau rekan admin dari unit penyelenggara yang sama selalu berhak mengelola notulensi
        if ($agenda->created_by === $user->id) {
            return true;
        }

        if ($user->isAdmin() && $user->unit_id !== null && $agenda->creatorUnitId() === $user->unit_id) {
            return true;
        }

        // Saat rapat berstatus 'ongoing', petugas yang ditunjuk (pimpinan atau notulis, baik admin unit maupun staf)
        // berhak mengelola notulensi dan foto dokumentasi kegiatan
        if ($this->isAppointedDuringSession($user, $agenda)) {
            return true;
        }

        return false;
    }

    /**
     * Whether the user was appointed to a role for the duration of a live meeting.
     *
     * An admin unit who is neither the organiser nor a colleague from the hosting
     * unit still gets operational control over a meeting that is under way, as long
     * as the meeting itself named them as leader or minute taker. That rule is
     * identical for editing the meeting, changing its status, and maintaining the
     * minutes, so it lives here once rather than being restated in three places —
     * which is how the three copies drifted apart in the first place.
     */
    private function isAppointedDuringSession(User $user, Agenda $agenda): bool
    {
        if ($agenda->status !== 'ongoing') {
            return false;
        }

        foreach (self::roleColumns() as $column) {
            $appointee = $agenda->getAttribute($column);

            if ($appointee !== null && (int) $appointee === (int) $user->getKey()) {
                return true;
            }
        }

        return false;
    }

    /**
     * The agenda's assignable role columns, taken from the model.
     *
     * The meeting-leader column name is visually identical to several near-miss
     * variants, and a policy that compares against the wrong one silently returns
     * false — denying access with no error anywhere. That is not hypothetical: this
     * rule previously existed as three hand-written copies, one of them missing the
     * `_id` suffix and another carrying a look-alike character.
     *
     * Reading the names from Agenda::$fillable means the policy cannot disagree
     * with the schema, and the comparison uses getAttribute() rather than a literal
     * property access for the same reason.
     *
     * @return list<string>
     */
    private static function roleColumns(): array
    {
        static $columns = null;

        return $columns ??= array_values(array_filter(
            (new Agenda)->getFillable(),
            static fn (string $field): bool => str_ends_with($field, '_id'),
        ));
    }
}
