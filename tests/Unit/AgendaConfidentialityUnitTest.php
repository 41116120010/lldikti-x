<?php

namespace Tests\Unit;

use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\User;
use App\Policies\AgendaPolicy;
use PHPUnit\Framework\TestCase;

class AgendaConfidentialityUnitTest extends TestCase
{
    private AgendaPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new AgendaPolicy();
    }

    public function test_administrator_can_always_view_completed_agenda_without_attendance(): void
    {
        $admin = new User();
        $admin->id = 1;
        $admin->role = 'administrator';

        $agenda = new Agenda([
            'status' => 'completed',
            'target_type' => 'all_units',
        ]);
        $agenda->setRelation('attendances', collect());

        $response = $this->policy->viewStaff($admin, $agenda);
        $this->assertTrue($response->allowed());
    }

    public function test_meeting_creator_can_always_view_completed_agenda(): void
    {
        $creator = new User();
        $creator->id = 10;
        $creator->role = 'admin';

        $agenda = new Agenda([
            'status' => 'completed',
            'created_by' => 10,
            'target_type' => 'all_units',
        ]);
        $agenda->setRelation('attendances', collect());

        $response = $this->policy->viewStaff($creator, $agenda);
        $this->assertTrue($response->allowed());
    }

    public function test_appointed_pimpinan_and_notulis_can_view_completed_agenda_without_attendance(): void
    {
        $pimpinan = new User();
        $pimpinan->id = 50;
        $pimpinan->role = 'staff';

        $notulis = new User();
        $notulis->id = 60;
        $notulis->role = 'staff';

        $agenda = new Agenda([
            'status' => 'completed',
            'pimpinan_id' => 50,
            'notulis_id' => 60,
            'target_type' => 'selected_users',
        ]);
        $agenda->setRelation('attendances', collect());

        $this->assertTrue($this->policy->viewStaff($pimpinan, $agenda)->allowed());
        $this->assertTrue($this->policy->viewStaff($notulis, $agenda)->allowed());
    }

    public function test_admin_of_organizing_unit_can_view_completed_agenda(): void
    {
        $adminUnit = new User();
        $adminUnit->id = 20;
        $adminUnit->role = 'admin';
        $adminUnit->unit_id = 5;

        $creator = new User();
        $creator->id = 25;
        $creator->unit_id = 5;

        $agenda = new Agenda([
            'status' => 'completed',
            'created_by' => 25,
            'target_type' => 'selected_units',
        ]);
        $agenda->setRelation('creator', $creator);
        $agenda->setRelation('attendances', collect());

        $this->assertTrue($this->policy->viewStaff($adminUnit, $agenda)->allowed());
    }

    public function test_staff_eligible_who_attended_can_view_completed_agenda(): void
    {
        $staff = new User();
        $staff->id = 30;
        $staff->role = 'staff';

        $attendance = new Attendance();
        $attendance->user_id = 30;

        $agenda = new Agenda([
            'status' => 'completed',
            'target_type' => 'all_units',
        ]);
        $agenda->setRelation('attendances', collect([$attendance]));

        $response = $this->policy->viewStaff($staff, $agenda);
        $this->assertTrue($response->allowed());
    }

    public function test_staff_eligible_who_did_not_attend_is_denied_from_viewing_completed_agenda(): void
    {
        $staff = new User();
        $staff->id = 30;
        $staff->role = 'staff';

        $agenda = new Agenda([
            'status' => 'completed',
            'target_type' => 'all_units',
        ]);
        // Tidak ada catatan kehadiran untuk user id 30
        $agenda->setRelation('attendances', collect());

        $response = $this->policy->viewStaff($staff, $agenda);
        $this->assertTrue($response->denied());
        $this->assertStringContainsString('bersifat rahasia', $response->message());
        $this->assertStringContainsString('hanya diberikan kepada peserta yang telah mengisi presensi', $response->message());
    }

    public function test_staff_eligible_can_view_ongoing_and_scheduled_agendas_to_perform_checkin(): void
    {
        $staff = new User();
        $staff->id = 30;
        $staff->role = 'staff';

        // 1. Agenda sedang berlangsung (ongoing)
        $ongoingAgenda = new Agenda([
            'status' => 'ongoing',
            'target_type' => 'all_units',
        ]);
        $ongoingAgenda->setRelation('attendances', collect());

        $ongoingResponse = $this->policy->viewStaff($staff, $ongoingAgenda);
        $this->assertTrue($ongoingResponse->allowed(), 'Staf harus diizinkan membuka agenda ongoing agar dapat mengisi presensi');

        // 2. Agenda terjadwal (scheduled)
        $scheduledAgenda = new Agenda([
            'status' => 'scheduled',
            'target_type' => 'all_units',
        ]);
        $scheduledAgenda->setRelation('attendances', collect());

        $scheduledResponse = $this->policy->viewStaff($staff, $scheduledAgenda);
        $this->assertTrue($scheduledResponse->allowed(), 'Staf harus diizinkan membuka agenda scheduled agar mengetahui jadwal');
    }

    public function test_ineligible_staff_is_denied_from_viewing_agenda(): void
    {
        $staff = new User();
        $staff->id = 30;
        $staff->role = 'staff';
        $staff->unit_id = 99;

        // Rapat khusus unit 1, staff ada di unit 99
        $agenda = new Agenda([
            'status' => 'ongoing',
            'target_type' => 'selected_units',
        ]);
        $agenda->setRelation('units', collect([(object)['id' => 1]]));
        $agenda->setRelation('attendances', collect());

        $response = $this->policy->viewStaff($staff, $agenda);
        $this->assertTrue($response->denied());
        $this->assertStringContainsString('tidak terdaftar sebagai peserta', $response->message());
    }

    public function test_cancelled_or_draft_agenda_is_denied_for_staff(): void
    {
        $staff = new User();
        $staff->id = 30;
        $staff->role = 'staff';

        $draftAgenda = new Agenda([
            'status' => 'draft',
            'target_type' => 'all_units',
        ]);
        $this->assertTrue($this->policy->viewStaff($staff, $draftAgenda)->denied());

        $cancelledAgenda = new Agenda([
            'status' => 'cancelled',
            'target_type' => 'all_units',
        ]);
        $this->assertTrue($this->policy->viewStaff($staff, $cancelledAgenda)->denied());
    }
}
