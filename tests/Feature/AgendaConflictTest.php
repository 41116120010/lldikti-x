<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class AgendaConflictTest extends TestCase
{
    public function test_cannot_book_same_room_at_overlapping_time(): void
    {
        $superadmin = User::where('role', 'administrator')->first();

        // 1. Existing Agenda in Ruang Sidang Utama tomorrow 09:00 - 11:00
        $existingAgenda = Agenda::create([
            'created_by' => $superadmin->id,
            'judul_rapat' => 'Rapat Koordinasi A ' . uniqid(),
            'slug' => 'rapat-a-' . uniqid(),
            'jenis_rapat' => 'Rapat Koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Sidang Utama',
            'waktu_mulai' => now()->addDays(1)->setHour(9)->setMinute(0)->setSecond(0),
            'waktu_selesai' => now()->addDays(1)->setHour(11)->setMinute(0)->setSecond(0),
            'is_all_units' => false,
            'status' => 'scheduled',
        ]);

        try {
            // 2. Attempt to create second Agenda in same room tomorrow 10:00 - 12:00 (overlaps by 1 hour)
            $response = $this->actingAs($superadmin)->post('/admin/agendas', [
                'judul_rapat' => 'Rapat Bentrok Ruangan ' . uniqid(),
                'jenis_rapat' => 'Rapat Koordinasi',
                'tipe_rapat' => 'offline',
                'lokasi_ruang' => 'Ruang Sidang Utama',
                'waktu_mulai' => now()->addDays(1)->setHour(10)->setMinute(0)->format('Y-m-d H:i'),
                'waktu_selesai' => now()->addDays(1)->setHour(12)->setMinute(0)->format('Y-m-d H:i'),
                'is_all_units' => '0',
                'unit_ids' => [Unit::first()->id],
                'status' => 'scheduled',
            ]);

            $response->assertSessionHasErrors('lokasi_ruang');
            $errors = session('errors')->get('lokasi_ruang');
            $this->assertStringContainsString('Ruang Sidang Utama', $errors[0]);
            $this->assertStringContainsString('telah digunakan', $errors[0]);
        } finally {
            $existingAgenda->delete();
        }
    }

    public function test_can_book_same_room_at_different_time_back_to_back(): void
    {
        $superadmin = User::where('role', 'administrator')->first();

        // 1. Existing Agenda tomorrow 09:00 - 11:00
        $existingAgenda = Agenda::create([
            'created_by' => $superadmin->id,
            'judul_rapat' => 'Rapat Pagi ' . uniqid(),
            'slug' => 'rapat-pagi-' . uniqid(),
            'jenis_rapat' => 'Rapat Koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Rapat 101',
            'waktu_mulai' => now()->addDays(1)->setHour(9)->setMinute(0)->setSecond(0),
            'waktu_selesai' => now()->addDays(1)->setHour(11)->setMinute(0)->setSecond(0),
            'is_all_units' => false,
            'status' => 'scheduled',
        ]);

        $createdAgendaId = null;

        try {
            // 2. Next Agenda right at 11:00 - 13:00 (back to back, non-overlapping)
            $response = $this->actingAs($superadmin)->post('/admin/agendas', [
                'judul_rapat' => 'Rapat Siang Sukses ' . uniqid(),
                'jenis_rapat' => 'Rapat Koordinasi',
                'tipe_rapat' => 'offline',
                'lokasi_ruang' => 'Ruang Rapat 101',
                'waktu_mulai' => now()->addDays(1)->setHour(11)->setMinute(0)->format('Y-m-d H:i'),
                'waktu_selesai' => now()->addDays(1)->setHour(13)->setMinute(0)->format('Y-m-d H:i'),
                'is_all_units' => '0',
                'unit_ids' => [Unit::first()->id],
                'status' => 'scheduled',
            ]);

            $response->assertSessionHasNoErrors();

            $createdAgenda = Agenda::where('judul_rapat', 'like', 'Rapat Siang Sukses%')->first();
            $this->assertNotNull($createdAgenda);
            $createdAgendaId = $createdAgenda->id;
            $response->assertRedirect(route('admin.agendas.show', $createdAgenda));
        } finally {
            $existingAgenda->delete();
            if ($createdAgendaId) {
                Agenda::find($createdAgendaId)?->delete();
            }
        }
    }

    public function test_online_meetings_do_not_cause_room_collision(): void
    {
        $superadmin = User::where('role', 'administrator')->first();

        // 1. Existing online meeting
        $existingOnline = Agenda::create([
            'created_by' => $superadmin->id,
            'judul_rapat' => 'Online A ' . uniqid(),
            'slug' => 'online-a-' . uniqid(),
            'jenis_rapat' => 'Rapat Koordinasi',
            'tipe_rapat' => 'online',
            'link_meeting' => 'https://zoom.us/j/12345678',
            'waktu_mulai' => now()->addDays(2)->setHour(9)->setMinute(0)->setSecond(0),
            'waktu_selesai' => now()->addDays(2)->setHour(11)->setMinute(0)->setSecond(0),
            'is_all_units' => false,
            'status' => 'scheduled',
        ]);

        $createdOnlineId = null;

        try {
            // 2. Another online meeting at the exact same time
            $response = $this->actingAs($superadmin)->post('/admin/agendas', [
                'judul_rapat' => 'Online B Sukses ' . uniqid(),
                'jenis_rapat' => 'Rapat Koordinasi',
                'tipe_rapat' => 'online',
                'link_meeting' => 'https://meet.google.com/abc-def-ghi',
                'waktu_mulai' => now()->addDays(2)->setHour(9)->setMinute(0)->format('Y-m-d H:i'),
                'waktu_selesai' => now()->addDays(2)->setHour(11)->setMinute(0)->format('Y-m-d H:i'),
                'is_all_units' => '0',
                'unit_ids' => [Unit::skip(1)->first()?->id ?? Unit::first()->id],
                'status' => 'scheduled',
            ]);

            $response->assertSessionHasNoErrors();
            $createdOnline = Agenda::where('judul_rapat', 'like', 'Online B Sukses%')->first();
            $this->assertNotNull($createdOnline);
            $createdOnlineId = $createdOnline->id;
        } finally {
            $existingOnline->delete();
            if ($createdOnlineId) {
                Agenda::find($createdOnlineId)?->delete();
            }
        }
    }

    public function test_cannot_book_room_when_existing_agenda_is_until_finished(): void
    {
        $superadmin = User::where('role', 'administrator')->first();

        // 1. Existing agenda with waktu_selesai = null ("Hingga Selesai")
        $existingOpenAgenda = Agenda::create([
            'created_by' => $superadmin->id,
            'judul_rapat' => 'Rapat Seharian ' . uniqid(),
            'slug' => 'seharian-' . uniqid(),
            'jenis_rapat' => 'Konsinyasi / FGD',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Aula Serbaguna',
            'waktu_mulai' => now()->addDays(3)->setHour(9)->setMinute(0)->setSecond(0),
            'waktu_selesai' => null,
            'is_all_units' => false,
            'status' => 'scheduled',
        ]);

        try {
            // 2. Attempt to schedule afternoon meeting in same room
            $response = $this->actingAs($superadmin)->post('/admin/agendas', [
                'judul_rapat' => 'Rapat Sore Bentrok ' . uniqid(),
                'jenis_rapat' => 'Rapat Terbatas',
                'tipe_rapat' => 'offline',
                'lokasi_ruang' => 'Aula Serbaguna',
                'waktu_mulai' => now()->addDays(3)->setHour(14)->setMinute(0)->format('Y-m-d H:i'),
                'waktu_selesai' => now()->addDays(3)->setHour(16)->setMinute(0)->format('Y-m-d H:i'),
                'is_all_units' => '0',
                'unit_ids' => [Unit::first()->id],
                'status' => 'scheduled',
            ]);

            $response->assertSessionHasErrors('lokasi_ruang');
        } finally {
            $existingOpenAgenda->delete();
        }
    }

    public function test_cannot_create_past_scheduled_agenda(): void
    {
        $superadmin = User::where('role', 'administrator')->first();

        // Attempt to create scheduled meeting in the past (yesterday)
        $response = $this->actingAs($superadmin)->post('/admin/agendas', [
            'judul_rapat' => 'Rapat Lampau Gagal ' . uniqid(),
            'jenis_rapat' => 'Rapat Koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Sidang 2',
            'waktu_mulai' => now()->subDay()->setHour(9)->format('Y-m-d H:i'),
            'waktu_selesai' => now()->subDay()->setHour(11)->format('Y-m-d H:i'),
            'is_all_units' => '1',
            'status' => 'scheduled',
        ]);

        $response->assertSessionHasErrors('waktu_mulai');
        $errors = session('errors')->get('waktu_mulai');
        $this->assertStringContainsString('tidak boleh di masa lampau', $errors[0]);
    }

    public function test_admin_unit_cannot_create_meeting_today_if_unit_has_ongoing_agenda(): void
    {
        $adminUnit = User::where('role', 'admin')->whereNotNull('unit_id')->first();
        $this->assertNotNull($adminUnit, 'Admin unit user must exist');

        // 1. Create an ongoing agenda for this admin's unit
        $ongoingAgenda = Agenda::create([
            'created_by' => $adminUnit->id,
            'judul_rapat' => 'Rapat Unit Sedang Aktif ' . uniqid(),
            'slug' => 'ongoing-unit-' . uniqid(),
            'jenis_rapat' => 'Rapat Koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Kerja Unit',
            'waktu_mulai' => now()->setHour(8)->setMinute(0),
            'waktu_selesai' => now()->setHour(12)->setMinute(0),
            'is_all_units' => false,
            'status' => 'ongoing',
        ]);
        $ongoingAgenda->units()->sync([$adminUnit->unit_id]);

        try {
            // 2. Admin unit attempts to create another meeting today
            $response = $this->actingAs($adminUnit)->post('/admin/agendas', [
                'judul_rapat' => 'Rapat Kedua Unit Hari Ini ' . uniqid(),
                'jenis_rapat' => 'Rapat Koordinasi',
                'tipe_rapat' => 'offline',
                'lokasi_ruang' => 'Ruang Rapat Alternatif',
                'waktu_mulai' => now()->setHour(13)->setMinute(0)->format('Y-m-d H:i'),
                'waktu_selesai' => now()->setHour(15)->setMinute(0)->format('Y-m-d H:i'),
                'is_all_units' => '0',
                'unit_ids' => [$adminUnit->unit_id],
                'status' => 'scheduled',
            ]);

            $response->assertSessionHasErrors('status');
            $errors = session('errors')->get('status');
            $this->assertStringContainsString('masih memiliki agenda rapat yang sedang berlangsung', $errors[0]);
        } finally {
            $ongoingAgenda->delete();
        }
    }

    public function test_admin_unit_can_create_future_scheduled_meeting_even_with_ongoing_agenda(): void
    {
        $adminUnit = User::where('role', 'admin')->whereNotNull('unit_id')->first();
        $this->assertNotNull($adminUnit);

        // 1. Ongoing agenda today
        $ongoingAgenda = Agenda::create([
            'created_by' => $adminUnit->id,
            'judul_rapat' => 'Rapat Unit Aktif Hari Ini ' . uniqid(),
            'slug' => 'ongoing-unit-future-' . uniqid(),
            'jenis_rapat' => 'Rapat Koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Kerja Unit',
            'waktu_mulai' => now()->setHour(8)->setMinute(0),
            'waktu_selesai' => now()->setHour(12)->setMinute(0),
            'is_all_units' => false,
            'status' => 'ongoing',
        ]);
        $ongoingAgenda->units()->sync([$adminUnit->unit_id]);

        $createdFutureId = null;

        try {
            // 2. Admin unit creates meeting for next week (future date allowed)
            $response = $this->actingAs($adminUnit)->post('/admin/agendas', [
                'judul_rapat' => 'Rapat Minggu Depan Berhasil ' . uniqid(),
                'jenis_rapat' => 'Rapat Koordinasi',
                'tipe_rapat' => 'offline',
                'lokasi_ruang' => 'Ruang Rapat Alternatif',
                'waktu_mulai' => now()->addDays(7)->setHour(10)->setMinute(0)->format('Y-m-d H:i'),
                'waktu_selesai' => now()->addDays(7)->setHour(12)->setMinute(0)->format('Y-m-d H:i'),
                'is_all_units' => '0',
                'unit_ids' => [$adminUnit->unit_id],
                'status' => 'scheduled',
            ]);

            $response->assertSessionHasNoErrors();
            $created = Agenda::where('judul_rapat', 'like', 'Rapat Minggu Depan Berhasil%')->first();
            $this->assertNotNull($created);
            $createdFutureId = $created->id;
        } finally {
            $ongoingAgenda->delete();
            if ($createdFutureId) {
                Agenda::find($createdFutureId)?->delete();
            }
        }
    }

    public function test_cannot_assign_same_leader_to_overlapping_meetings(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $targetLeader = User::where('role', 'staff')->first();
        $this->assertNotNull($targetLeader);

        // 1. Existing agenda with leader assigned tomorrow 09:00 - 11:00
        $existingAgenda = Agenda::create([
            'created_by' => $superadmin->id,
            'pimpinan_id' => $targetLeader->id,
            'judul_rapat' => 'Rapat Dipimpin Pejabat ' . uniqid(),
            'slug' => 'pimpinan-a-' . uniqid(),
            'jenis_rapat' => 'Rapat Koordinasi',
            'tipe_rapat' => 'online',
            'link_meeting' => 'https://zoom.us/j/111222',
            'waktu_mulai' => now()->addDays(4)->setHour(9)->setMinute(0)->setSecond(0),
            'waktu_selesai' => now()->addDays(4)->setHour(11)->setMinute(0)->setSecond(0),
            'is_all_units' => false,
            'status' => 'scheduled',
        ]);

        try {
            // 2. Attempt to assign same leader on tomorrow 10:00 - 12:00
            $response = $this->actingAs($superadmin)->post('/admin/agendas', [
                'judul_rapat' => 'Rapat Bentrok Pimpinan ' . uniqid(),
                'pimpinan_id' => $targetLeader->id,
                'jenis_rapat' => 'Rapat Koordinasi',
                'tipe_rapat' => 'online',
                'link_meeting' => 'https://zoom.us/j/333444',
                'waktu_mulai' => now()->addDays(4)->setHour(10)->setMinute(0)->format('Y-m-d H:i'),
                'waktu_selesai' => now()->addDays(4)->setHour(12)->setMinute(0)->format('Y-m-d H:i'),
                'is_all_units' => '0',
                'unit_ids' => [Unit::first()->id],
                'status' => 'scheduled',
            ]);

            $response->assertSessionHasErrors('pimpinan_id');
            $errors = session('errors')->get('pimpinan_id');
            $this->assertStringContainsString('telah ditugaskan sebagai Pemimpin Rapat', $errors[0]);
        } finally {
            $existingAgenda->delete();
        }
    }

    public function test_cannot_assign_same_notulis_to_overlapping_meetings(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $targetNotulis = User::where('role', 'staff')->first();
        $this->assertNotNull($targetNotulis);

        // 1. Existing agenda with notulis assigned tomorrow 09:00 - 11:00
        $existingAgenda = Agenda::create([
            'created_by' => $superadmin->id,
            'notulis_id' => $targetNotulis->id,
            'judul_rapat' => 'Rapat Dicatat Notulis ' . uniqid(),
            'slug' => 'notulis-a-' . uniqid(),
            'jenis_rapat' => 'Rapat Koordinasi',
            'tipe_rapat' => 'online',
            'link_meeting' => 'https://zoom.us/j/555666',
            'waktu_mulai' => now()->addDays(5)->setHour(9)->setMinute(0)->setSecond(0),
            'waktu_selesai' => now()->addDays(5)->setHour(11)->setMinute(0)->setSecond(0),
            'is_all_units' => false,
            'status' => 'scheduled',
        ]);

        try {
            // 2. Attempt to assign same notulis on tomorrow 10:00 - 12:00
            $response = $this->actingAs($superadmin)->post('/admin/agendas', [
                'judul_rapat' => 'Rapat Bentrok Notulis ' . uniqid(),
                'notulis_id' => $targetNotulis->id,
                'jenis_rapat' => 'Rapat Koordinasi',
                'tipe_rapat' => 'online',
                'link_meeting' => 'https://zoom.us/j/777888',
                'waktu_mulai' => now()->addDays(5)->setHour(10)->setMinute(0)->format('Y-m-d H:i'),
                'waktu_selesai' => now()->addDays(5)->setHour(12)->setMinute(0)->format('Y-m-d H:i'),
                'is_all_units' => '0',
                'unit_ids' => [Unit::first()->id],
                'status' => 'scheduled',
            ]);

            $response->assertSessionHasErrors('notulis_id');
            $errors = session('errors')->get('notulis_id');
            $this->assertStringContainsString('telah ditugaskan sebagai Notulis', $errors[0]);
        } finally {
            $existingAgenda->delete();
        }
    }

    public function test_updating_agenda_does_not_conflict_with_itself(): void
    {
        $superadmin = User::where('role', 'administrator')->first();

        // 1. Existing agenda
        $agenda = Agenda::create([
            'created_by' => $superadmin->id,
            'judul_rapat' => 'Agenda Awal Untuk Diupdate ' . uniqid(),
            'slug' => 'update-self-' . uniqid(),
            'jenis_rapat' => 'Rapat Koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Sidang Pimpinan',
            'waktu_mulai' => now()->addDays(6)->setHour(9)->setMinute(0)->setSecond(0),
            'waktu_selesai' => now()->addDays(6)->setHour(11)->setMinute(0)->setSecond(0),
            'is_all_units' => true,
            'status' => 'scheduled',
        ]);

        try {
            // 2. Update without changing schedule or room (must not conflict with itself)
            $response = $this->actingAs($superadmin)->put("/admin/agendas/{$agenda->id}", [
                'judul_rapat' => 'Agenda Berhasil Diupdate ' . uniqid(),
                'jenis_rapat' => 'Rapat Pleno',
                'tipe_rapat' => 'offline',
                'lokasi_ruang' => 'Ruang Sidang Pimpinan',
                'waktu_mulai' => now()->addDays(6)->setHour(9)->setMinute(0)->format('Y-m-d H:i'),
                'waktu_selesai' => now()->addDays(6)->setHour(11)->setMinute(0)->format('Y-m-d H:i'),
                'is_all_units' => '1',
                'status' => 'scheduled',
            ]);

            $response->assertSessionHasNoErrors();
            $response->assertRedirect(route('admin.agendas.show', $agenda));
        } finally {
            $agenda->delete();
        }
    }

    public function test_cannot_schedule_overlapping_pleno_meetings(): void
    {
        $superadmin = User::where('role', 'administrator')->first();

        // 1. Existing universal pleno meeting tomorrow 09:00 - 11:00
        $existingPleno = Agenda::create([
            'created_by' => $superadmin->id,
            'judul_rapat' => 'Rapat Pleno Pertama ' . uniqid(),
            'slug' => 'pleno-1-' . uniqid(),
            'jenis_rapat' => 'Rapat Pleno',
            'tipe_rapat' => 'online',
            'link_meeting' => 'https://zoom.us/j/999111',
            'waktu_mulai' => now()->addDays(7)->setHour(9)->setMinute(0)->setSecond(0),
            'waktu_selesai' => now()->addDays(7)->setHour(11)->setMinute(0)->setSecond(0),
            'is_all_units' => true,
            'status' => 'scheduled',
        ]);

        try {
            // 2. Attempt to create another universal pleno meeting at overlapping time 10:00 - 12:00
            $response = $this->actingAs($superadmin)->post('/admin/agendas', [
                'judul_rapat' => 'Rapat Pleno Kedua Bentrok ' . uniqid(),
                'jenis_rapat' => 'Rapat Pleno',
                'tipe_rapat' => 'online',
                'link_meeting' => 'https://meet.google.com/xyz',
                'waktu_mulai' => now()->addDays(7)->setHour(10)->setMinute(0)->format('Y-m-d H:i'),
                'waktu_selesai' => now()->addDays(7)->setHour(12)->setMinute(0)->format('Y-m-d H:i'),
                'is_all_units' => '1',
                'status' => 'scheduled',
            ]);

            $response->assertSessionHasErrors('waktu_mulai');
            $errors = session('errors')->get('waktu_mulai');
            $this->assertStringContainsString('rapat pleno', $errors[0]);
        } finally {
            $existingPleno->delete();
        }
    }
}
