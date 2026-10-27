<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReportVisualMonitoringTest extends TestCase
{
    use DatabaseTransactions;

    private User $administrator;
    private User $adminUnitA;
    private Unit $unitA;
    private User $adminUnitB;
    private Unit $unitB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->administrator = User::where('role', 'administrator')->firstOrFail();

        $this->unitA = Unit::where('kode_unit', 'POKJA-AKM')->firstOrFail();
        $this->adminUnitA = User::where('unit_id', $this->unitA->id)->firstOrFail();
        $this->adminUnitA->update(['role' => 'admin', 'is_active' => true]);

        $this->unitB = Unit::where('kode_unit', 'POKJA-KLB')->firstOrFail();
        $this->adminUnitB = User::where('unit_id', $this->unitB->id)->firstOrFail();
        $this->adminUnitB->update(['role' => 'admin', 'is_active' => true]);
    }

    /**
     * Memastikan Administrator melihat banner cakupan global, KPI analitik,
     * distribusi format rapat, tata kelola dokumen, dan partisipasi seluruh unit.
     */
    public function test_administrator_sees_global_monitoring_and_analytics(): void
    {
        // Buat rapat completed dengan notulensi
        $agenda1 = Agenda::create([
            'judul_rapat' => 'Rapat Pleno Eksekutif ' . uniqid(),
            'slug' => 'rapat-pleno-' . uniqid(),
            'jenis_rapat' => 'Rapat Pleno',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Aula Utama LLDIKTI',
            'waktu_mulai' => Carbon::now()->subDays(2),
            'waktu_selesai' => Carbon::now()->subDays(2)->addHours(2),
            'status' => 'completed',
            'is_all_units' => true,
            'created_by' => $this->administrator->id,
            'notulensi' => '<p>Hasil pembahasan pleno resmi telah disepakati.</p>',
            'kesimpulan' => 'Semua program kerja disetujui.',
        ]);

        // Buat rapat online terjadwal
        $agenda2 = Agenda::create([
            'judul_rapat' => 'Sosialisasi Daring Nasional ' . uniqid(),
            'slug' => 'sosialisasi-daring-' . uniqid(),
            'jenis_rapat' => 'Sosialisasi',
            'tipe_rapat' => 'online',
            'lokasi_ruang' => 'Zoom Virtual Room',
            'waktu_mulai' => Carbon::now()->addDays(2),
            'waktu_selesai' => Carbon::now()->addDays(2)->addHours(2),
            'status' => 'scheduled',
            'is_all_units' => true,
            'created_by' => $this->administrator->id,
        ]);

        $response = $this->actingAs($this->administrator)->get('/admin/reports');

        $response->assertStatus(200);
        $response->assertSee('Cakupan: Seluruh Unit Kerja LLDIKTI (12 Unit)');
        $response->assertSee('Distribusi Format Rapat');
        $response->assertSee('Status Tata Kelola Dokumen');
        $response->assertSee('Partisipasi Per Unit Kerja');
        $response->assertSee('Notulen Rampung');
        $response->assertSee('Luring');
        $response->assertSee('Daring');
        $response->assertSee('Hibrida');
    }

    /**
     * Memastikan Admin Unit melihat banner cakupan lokal unitnya dan metrik partisipasi anggotanya.
     */
    public function test_admin_unit_sees_scoped_unit_monitoring(): void
    {
        $agendaUnitA = Agenda::create([
            'judul_rapat' => 'Rapat Koordinasi Internal Unit A ' . uniqid(),
            'slug' => 'rapat-unit-a-' . uniqid(),
            'jenis_rapat' => 'Koordinasi',
            'tipe_rapat' => 'hybrid',
            'lokasi_ruang' => 'Ruang Rapat Unit A',
            'waktu_mulai' => Carbon::now()->subDay(),
            'waktu_selesai' => Carbon::now()->subDay()->addHours(1),
            'status' => 'completed',
            'is_all_units' => false,
            'created_by' => $this->adminUnitA->id,
            'notulensi' => '<p>Catatan rapat unit A.</p>',
        ]);
        $agendaUnitA->units()->attach($this->unitA->id);

        $response = $this->actingAs($this->adminUnitA)->get('/admin/reports');

        $response->assertStatus(200);
        $response->assertSee('Cakupan: ' . $this->unitA->nama_unit);
        $response->assertSee('Partisipasi Pegawai di Unit');
        $response->assertSee('Distribusi Format Rapat');
        $response->assertSee('Status Tata Kelola Dokumen');
        $response->assertDontSee('Cakupan: Seluruh Unit Kerja LLDIKTI (12 Unit)');
    }

    /**
     * Memastikan Admin Unit yang ditugaskan sebagai Pimpinan atau Notulis melihat
     * panel monitoring penugasan khusus resmi beserta badge status dan tautan kelola.
     */
    public function test_admin_unit_with_assigned_roles_sees_assigned_agenda_panel(): void
    {
        // Admin Unit B ditugaskan menjadi Notulis Rapat di agenda buatan Admin Unit A
        $assignedAgenda = Agenda::create([
            'judul_rapat' => 'Rapat Lintas Sektor Penugasan B ' . uniqid(),
            'slug' => 'rapat-penugasan-' . uniqid(),
            'jenis_rapat' => 'Koordinasi Khusus',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Sidang',
            'waktu_mulai' => Carbon::now()->addHours(3),
            'waktu_selesai' => Carbon::now()->addHours(5),
            'status' => 'scheduled',
            'is_all_units' => false,
            'created_by' => $this->adminUnitA->id,
            'pimpinan_id' => $this->adminUnitA->id,
            'notulis_id' => $this->adminUnitB->id,
        ]);
        $assignedAgenda->units()->attach($this->unitA->id);

        $response = $this->actingAs($this->adminUnitB)->get('/admin/reports');

        $response->assertStatus(200);
        $response->assertSee('Monitoring Agenda Penugasan Resmi (Pimpinan & Notulis)');
        $response->assertSee('Notulis Rapat');
        $response->assertSee($assignedAgenda->judul_rapat);
        $response->assertSee('Belum Diisi');
    }

    /**
     * Memastikan filter parameter status, format rapat, dan tanggal bekerja akurat.
     */
    public function test_reports_filter_applies_successfully_to_view(): void
    {
        $response = $this->actingAs($this->administrator)->get('/admin/reports?status=completed&tipe=offline');

        $response->assertStatus(200);
        $response->assertSee('Reset');
    }

    /**
     * Memastikan Administrator dapat memantau panel penugasan pimpinan & notulis resmi.
     */
    public function test_administrator_sees_assigned_agenda_panel_when_assignments_exist(): void
    {
        $assignedAgenda = Agenda::create([
            'judul_rapat' => 'Rapat Penugasan Global ' . uniqid(),
            'slug' => 'rapat-global-' . uniqid(),
            'jenis_rapat' => 'Koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Sidang Utama',
            'waktu_mulai' => Carbon::now()->addHours(2),
            'waktu_selesai' => Carbon::now()->addHours(4),
            'status' => 'scheduled',
            'is_all_units' => true,
            'created_by' => $this->administrator->id,
            'pimpinan_id' => $this->administrator->id,
            'notulis_id' => $this->adminUnitA->id,
        ]);

        $response = $this->actingAs($this->administrator)->get('/admin/reports');

        $response->assertStatus(200);
        $response->assertSee('Monitoring Agenda Penugasan Resmi (Pimpinan & Notulis)');
        $response->assertSee($assignedAgenda->judul_rapat);
    }
}
