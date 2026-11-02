<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\User;
use Tests\TestCase;

class SelectedUsersAgendaTest extends TestCase
{
    public function test_admin_can_create_agenda_targeted_to_selected_users(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $staffMembers = User::where('role', 'staff')->take(2)->get();

        if ($staffMembers->count() < 2) {
            $this->markTestSkipped('Dibutuhkan minimal 2 akun staf untuk pengujian.');
        }

        $staff1 = $staffMembers[0];
        $staff2 = $staffMembers[1];
        $uniqueTitle = 'Rapat Koordinasi Khusus ASN ' . uniqid();

        $response = $this->actingAs($admin)->post('/admin/agendas', [
            'judul_rapat' => $uniqueTitle,
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'online',
            'link_meeting' => 'https://meet.google.com/xyz-abcd-efg',
            'waktu_mulai' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'waktu_selesai' => now()->addDays(3)->addHours(2)->format('Y-m-d H:i:s'),
            'target_type' => 'selected_users',
            'user_ids' => [$staff1->id, $staff2->id],
            'status' => 'scheduled',
        ]);

        $agenda = Agenda::where('judul_rapat', $uniqueTitle)->first();
        $this->assertNotNull($agenda);
        $this->assertEquals('selected_users', $agenda->target_type);
        $this->assertFalse((bool) $agenda->is_all_units);

        $response->assertRedirect("/admin/agendas/{$agenda->id}");

        $this->assertDatabaseHas('agenda_users', [
            'agenda_id' => $agenda->id,
            'user_id' => $staff1->id,
        ]);
        $this->assertDatabaseHas('agenda_users', [
            'agenda_id' => $agenda->id,
            'user_id' => $staff2->id,
        ]);

        $this->assertTrue($agenda->isUserEligible($staff1));
        $this->assertTrue($agenda->isUserEligible($staff2));
    }

    public function test_creation_fails_when_selected_users_has_empty_user_ids(): void
    {
        $admin = User::where('role', 'administrator')->first();

        $response = $this->actingAs($admin)->post('/admin/agendas', [
            'judul_rapat' => 'Rapat Tanpa Peserta ' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'online',
            'link_meeting' => 'https://meet.google.com/xyz-abcd-efg',
            'waktu_mulai' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'target_type' => 'selected_users',
            // intentionally omitted user_ids
        ]);

        $response->assertSessionHasErrors('user_ids');
    }

    public function test_visibility_scoping_for_selected_users_agenda(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $staffMembers = User::where('role', 'staff')->take(3)->get();

        if ($staffMembers->count() < 3) {
            $this->markTestSkipped('Dibutuhkan minimal 3 akun staf untuk pengujian visibilitas.');
        }

        $invitedStaff = $staffMembers[0];
        $uninvitedStaff = $staffMembers[1];

        $uniqueTitle = 'Rapat Terbatas ASN ' . uniqid();

        $agenda = Agenda::create([
            'created_by' => $admin->id,
            'judul_rapat' => $uniqueTitle,
            'slug' => 'rapat-terbatas-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'online',
            'link_meeting' => 'https://meet.google.com/test-meet',
            'waktu_mulai' => now()->addDay(),
            'waktu_selesai' => now()->addDay()->addHour(),
            'target_type' => 'selected_users',
            'is_all_units' => false,
            'status' => 'scheduled',
        ]);

        $agenda->invitedUsers()->sync([$invitedStaff->id]);

        // Verifikasi visibleTo scope
        $visibleToInvited = Agenda::visibleTo($invitedStaff)->where('id', $agenda->id)->exists();
        $visibleToUninvited = Agenda::visibleTo($uninvitedStaff)->where('id', $agenda->id)->exists();

        $this->assertTrue($visibleToInvited, 'Agenda harus terlihat oleh ASN yang diundang.');
        $this->assertFalse($visibleToUninvited, 'Agenda tidak boleh terlihat oleh ASN yang tidak diundang.');
    }
}
