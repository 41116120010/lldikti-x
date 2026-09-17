<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\User;
use Tests\TestCase;

class DashboardAgendaRelevanceTest extends TestCase
{
    public function test_dashboard_does_not_display_past_scheduled_agendas(): void
    {
        $superadmin = User::where('role', 'administrator')->first();

        // 1. Create a past scheduled agenda (e.g. 5 days ago) that was never started
        $pastAgenda = Agenda::create([
            'created_by' => $superadmin->id,
            'judul_rapat' => 'Rapat Masa Lampau Terlewat ' . uniqid(),
            'slug' => 'rapat-lampau-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Sidang',
            'waktu_mulai' => now()->subDays(5)->setHour(9),
            'waktu_selesai' => now()->subDays(5)->setHour(12),
            'is_all_units' => true,
            'status' => 'scheduled',
        ]);

        // 2. Create a future scheduled agenda
        $futureAgenda = Agenda::create([
            'created_by' => $superadmin->id,
            'judul_rapat' => 'Rapat Masa Depan Relevan ' . uniqid(),
            'slug' => 'rapat-depan-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Sidang',
            'waktu_mulai' => now()->startOfDay()->addHours(7),
            'waktu_selesai' => now()->addHours(4),
            'is_all_units' => true,
            'status' => 'scheduled',
        ]);

        try {
            // 3. Determine which page contains future agenda
            $allRelevantIds = Agenda::visibleTo($superadmin)
                ->relevantForDashboard()
                ->orderByRaw("CASE WHEN status = 'ongoing' THEN 1 ELSE 2 END ASC")
                ->orderBy('waktu_mulai', 'asc')
                ->pluck('id')
                ->all();

            $index = array_search($futureAgenda->id, $allRelevantIds);
            $page = ($index !== false) ? intval(floor($index / 5)) + 1 : 1;

            $response = $this->actingAs($superadmin)->get("/dashboard?page_agendas={$page}");
            $response->assertStatus(200);

            // Future agenda MUST be visible on its paginated page
            $response->assertSee($futureAgenda->judul_rapat);

            // Past scheduled agenda MUST NOT be visible on the dashboard nor in scope
            $this->assertFalse(Agenda::relevantForDashboard()->where('id', $pastAgenda->id)->exists());
            $response->assertDontSee($pastAgenda->judul_rapat);

            $activeAgendas = $response->viewData('activeAgendas');
            $this->assertFalse($activeAgendas->contains('id', $pastAgenda->id));
            $this->assertTrue($activeAgendas->contains('id', $futureAgenda->id));
        } finally {
            $pastAgenda->delete();
            $futureAgenda->delete();
        }
    }

    public function test_dashboard_displays_today_scheduled_agendas_until_end_time(): void
    {
        $superadmin = User::where('role', 'administrator')->first();

        // Agenda today whose end time has not passed yet
        $todayAgenda = Agenda::create([
            'created_by' => $superadmin->id,
            'judul_rapat' => 'Rapat Hari Ini ' . uniqid(),
            'slug' => 'rapat-hari-ini-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Rapat Utama',
            'waktu_mulai' => now()->subHour(),
            'waktu_selesai' => now()->addHours(2),
            'is_all_units' => true,
            'status' => 'scheduled',
        ]);

        try {
            $this->assertTrue(Agenda::relevantForDashboard()->where('id', $todayAgenda->id)->exists());

            // Determine which page contains this agenda
            $allRelevantIds = Agenda::visibleTo($superadmin)
                ->relevantForDashboard()
                ->orderByRaw("CASE WHEN status = 'ongoing' THEN 1 ELSE 2 END ASC")
                ->orderBy('waktu_mulai', 'asc')
                ->pluck('id')
                ->all();

            $index = array_search($todayAgenda->id, $allRelevantIds);
            $page = ($index !== false) ? intval(floor($index / 5)) + 1 : 1;

            $response = $this->actingAs($superadmin)->get("/dashboard?page_agendas={$page}");
            $response->assertStatus(200);
            $response->assertSee($todayAgenda->judul_rapat);

            $activeAgendas = $response->viewData('activeAgendas');
            $this->assertTrue($activeAgendas->contains('id', $todayAgenda->id));
        } finally {
            $todayAgenda->delete();
        }
    }

    public function test_dashboard_prioritizes_ongoing_agendas_over_scheduled(): void
    {
        $superadmin = User::where('role', 'administrator')->first();

        // Future scheduled agenda starting in 1 hour
        $scheduledAgenda = Agenda::create([
            'created_by' => $superadmin->id,
            'judul_rapat' => 'Rapat Terjadwal Lebih Awal ' . uniqid(),
            'slug' => 'rapat-terjadwal-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang 1',
            'waktu_mulai' => now()->addHour(),
            'waktu_selesai' => now()->addHours(3),
            'is_all_units' => true,
            'status' => 'scheduled',
        ]);

        // Ongoing agenda
        $ongoingAgenda = Agenda::create([
            'created_by' => $superadmin->id,
            'judul_rapat' => 'Rapat Sedang Berlangsung ' . uniqid(),
            'slug' => 'rapat-ongoing-' . uniqid(),
            'jenis_rapat' => 'pleno',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Aula Utama',
            'waktu_mulai' => now()->subMinutes(30),
            'waktu_selesai' => now()->addHours(2),
            'is_all_units' => true,
            'status' => 'ongoing',
        ]);

        try {
            $response = $this->actingAs($superadmin)->get('/dashboard');
            $response->assertStatus(200);

            $activeAgendas = $response->viewData('activeAgendas');
            // The very first agenda displayed on the dashboard must have status 'ongoing'
            $firstItem = $activeAgendas->first();
            $this->assertEquals('ongoing', $firstItem->status);
        } finally {
            $scheduledAgenda->delete();
            $ongoingAgenda->delete();
        }
    }

    public function test_dashboard_stats_upcoming_count_excludes_past_scheduled_agendas(): void
    {
        $superadmin = User::where('role', 'administrator')->first();

        // Get initial upcoming count
        $initialResponse = $this->actingAs($superadmin)->get('/dashboard');
        $initialUpcoming = $initialResponse->viewData('stats')['upcoming_agendas'];

        // Create 2 past scheduled agendas
        $past1 = Agenda::create([
            'created_by' => $superadmin->id,
            'judul_rapat' => 'Past Stale 1 ' . uniqid(),
            'slug' => 'past-stale-1-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang 1',
            'waktu_mulai' => now()->subDays(10),
            'waktu_selesai' => now()->subDays(10)->addHours(2),
            'is_all_units' => true,
            'status' => 'scheduled',
        ]);

        // Create 1 valid future scheduled agenda
        $future1 = Agenda::create([
            'created_by' => $superadmin->id,
            'judul_rapat' => 'Valid Future 1 ' . uniqid(),
            'slug' => 'valid-future-1-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang 1',
            'waktu_mulai' => now()->addDays(3),
            'waktu_selesai' => now()->addDays(3)->addHours(2),
            'is_all_units' => true,
            'status' => 'scheduled',
        ]);

        try {
            $afterResponse = $this->actingAs($superadmin)->get('/dashboard');
            $afterUpcoming = $afterResponse->viewData('stats')['upcoming_agendas'];

            // The upcoming count must increase by exactly 1 (the future agenda), ignoring the past agenda
            $this->assertEquals($initialUpcoming + 1, $afterUpcoming);
        } finally {
            $past1->delete();
            $future1->delete();
        }
    }

    public function test_attendance_portal_excludes_past_scheduled_agendas(): void
    {
        $staff = User::where('role', 'staff')->first();
        $superadmin = User::where('role', 'administrator')->first();

        $pastAgenda = Agenda::create([
            'created_by' => $superadmin->id,
            'judul_rapat' => 'Rapat Usang di Portal ' . uniqid(),
            'slug' => 'rapat-usang-portal-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang 1',
            'waktu_mulai' => now()->subWeeks(2),
            'waktu_selesai' => now()->subWeeks(2)->addHours(2),
            'is_all_units' => true,
            'status' => 'scheduled',
        ]);

        try {
            $response = $this->actingAs($staff)->get('/presensi');
            $response->assertStatus(200);
            $response->assertDontSee($pastAgenda->judul_rapat);

            $scheduledAgendas = $response->viewData('scheduledAgendas');
            $this->assertFalse($scheduledAgendas->contains('id', $pastAgenda->id));
        } finally {
            $pastAgenda->delete();
        }
    }

    public function test_dashboard_header_is_clean_without_unwanted_subtitle_text(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $staff = User::where('role', 'staff')->first();

        // Check for superadmin
        $adminResponse = $this->actingAs($superadmin)->get('/dashboard');
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('Agenda Rapat Terkini');
        $adminResponse->assertDontSee('Rapat aktif dan terjadwal terdekat');

        // Check for staff
        $staffResponse = $this->actingAs($staff)->get('/dashboard');
        $staffResponse->assertStatus(200);
        $staffResponse->assertSee('Agenda Rapat Terkini');
        $staffResponse->assertDontSee('Rapat aktif dan terjadwal terdekat');
    }
}
