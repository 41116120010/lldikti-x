<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use RuntimeException;
use Tests\TestCase;

/**
 * Pins the "appointed during a live meeting" authorisation rule.
 *
 * An admin unit who is neither the organiser nor a colleague from the hosting unit
 * still gets operational control over a meeting that is under way, but only while
 * the meeting names them as leader or minute taker. That rule is shared by editing
 * the meeting, changing its status, and maintaining the minutes, so it is
 * exercised across all three here.
 *
 * It previously existed as three hand-written copies that had drifted apart, so a
 * rule tested a column name that did not exist. That fails silently rather than
 * loudly: an unknown key simply matches no validation rule and is then dropped as
 * non-fillable, leaving the appointment permanently null and denying the admin
 * with a 403 that points nowhere near the real cause.
 *
 * This test therefore never spells the leader column out. It reads it from
 * Agenda::$fillable, the same single source of truth UpdateRolesRequest and
 * AgendaPolicy use, so the column cannot drift here either.
 */
class AppointedAdminAuthorisationTest extends TestCase
{
    use DatabaseTransactions;

    private User $superadmin;

    private User $hostUnitAdmin;

    private User $otherUnitAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::where('role', 'administrator')->firstOrFail();
        $this->hostUnitAdmin = User::where('username', 'admin_akademik')->firstOrFail();
        $this->otherUnitAdmin = User::where('username', 'admin_kelembagaan')->firstOrFail();
    }

    public function test_the_meeting_organiser_always_regains_full_control(): void
    {
        $agenda = $this->makeLiveMeeting();

        $this->assertTrue(
            $this->superadmin->can('update', $agenda),
            'The superadmin who created the meeting must always be able to update it.'
        );
    }

    public function test_an_appointed_admin_unit_may_manage_a_live_meeting(): void
    {
        $agenda = $this->makeLiveMeeting();

        // Appoint the hosting admin as meeting leader, then exercise all three
        // permissions they should now hold.
        $this->actingAs($this->superadmin)
            ->patch("/admin/agendas/{$agenda->id}/roles", [
                self::leaderColumn() => $this->hostUnitAdmin->id,
                'notulis_id' => null,
            ])
            ->assertSessionHas('success');

        $agenda->refresh();

        $this->assertSame(
            (int) $this->hostUnitAdmin->id,
            (int) $agenda->getAttribute(self::leaderColumn()),
            'The role endpoint must actually persist the appointment.'
        );

        $this->assertTrue(
            $this->hostUnitAdmin->can('update', $agenda),
            'An appointed leader must be able to update the meeting.'
        );
        $this->assertTrue(
            $this->hostUnitAdmin->can('manageStatus', $agenda),
            'An appointed leader must be able to change the meeting status.'
        );
        $this->assertTrue(
            $this->hostUnitAdmin->can('manageMinutes', $agenda),
            'An appointed leader must be able to maintain the minutes.'
        );
    }

    public function test_an_admin_unit_who_is_not_appointed_has_no_control(): void
    {
        $agenda = $this->makeLiveMeeting();

        $this->assertFalse(
            $this->otherUnitAdmin->can('update', $agenda),
            'An unappointed admin unit must not be able to update the meeting.'
        );
        $this->assertFalse(
            $this->otherUnitAdmin->can('manageStatus', $agenda),
            'An unappointed admin unit must not be able to change the meeting status.'
        );
    }

    public function test_appointment_only_holds_while_the_meeting_is_live(): void
    {
        $agenda = $this->makeLiveMeeting(leaderId: $this->hostUnitAdmin->id);
        $agenda->update(['status' => 'completed']);
        $agenda->refresh();

        $this->assertFalse(
            $this->hostUnitAdmin->can('manageStatus', $agenda),
            'Once the meeting is over, the temporary appointment must no longer grant status control.'
        );
    }

    /**
     * A live, universal meeting owned by the superadmin, so the only thing granting
     * an admin unit access is the appointment under test.
     */
    private function makeLiveMeeting(?int $leaderId = null): Agenda
    {
        return Agenda::create([
            'created_by' => $this->superadmin->id,
            self::leaderColumn() => $leaderId,
            'notulis_id' => null,
            'judul_rapat' => 'Rapat Uji Penugasan Peran ' . uniqid(),
            'slug' => 'uji-penugasan-' . uniqid(),
            'jenis_rapat' => 'Rapat Koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Uji Peran',
            'waktu_mulai' => now(),
            'waktu_selesai' => now()->addHours(2),
            'is_all_units' => true,
            'status' => 'ongoing',
        ]);
    }

    /**
     * The meeting-leader column, read from the model instead of typed out.
     *
     * @throws RuntimeException when the schema no longer matches the assumption
     */
    private static function leaderColumn(): string
    {
        static $column = null;

        if ($column !== null) {
            return $column;
        }

        $candidates = array_values(array_filter(
            (new Agenda)->getFillable(),
            static fn (string $field): bool => str_ends_with($field, '_id')
                && ! in_array($field, ['created_by', 'notulis_id'], true),
        ));

        if (count($candidates) !== 1) {
            throw new RuntimeException(
                'Expected exactly one meeting-leader column on Agenda, found: '.implode(', ', $candidates)
            );
        }

        return $column = $candidates[0];
    }
}
