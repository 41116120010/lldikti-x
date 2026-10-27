<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CrossUnitNotulisAttendanceTest extends TestCase
{
    use DatabaseTransactions;

    private string $validJpegBase64 = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=';
    private string $validPngBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    private User $adminHtk;
    private Unit $unitHtk;
    private User $staffNotulisUnitB;
    private User $otherStaffUnitB;
    private Unit $unitB;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        // Gunakan unit resmi yang ada dari seeder
        $this->unitHtk = Unit::where('kode_unit', 'POKJA-AKM')->firstOrFail();
        $this->unitB = Unit::where('kode_unit', 'POKJA-KLB')->firstOrFail();

        // Admin penyelenggara dari Unit HTK / AKM
        $this->adminHtk = User::where('unit_id', $this->unitHtk->id)->firstOrFail();
        $this->adminHtk->update(['role' => 'admin', 'is_active' => true]);

        // Staf Notulis dari Unit B (KLB)
        $this->staffNotulisUnitB = User::where('unit_id', $this->unitB->id)->firstOrFail();
        $this->staffNotulisUnitB->update(['role' => 'staff', 'is_active' => true]);

        // Rekan satu unit Staf Notulis di Unit B (KLB) yang tidak ditunjuk
        $this->otherStaffUnitB = User::where('unit_id', $this->unitB->id)
            ->where('id', '!=', $this->staffNotulisUnitB->id)
            ->firstOrFail();
        $this->otherStaffUnitB->update(['role' => 'staff', 'is_active' => true]);
    }

    /**
     * Memastikan Notulis lintas unit dapat melihat agenda pada scope query visibleTo,
     * serta pada halaman Dashboard dan Portal Presensi.
     */
    public function test_cross_unit_notulis_sees_agenda_in_dashboard_and_portal(): void
    {
        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Internal HTK Notulis B ' . uniqid(),
            'slug' => 'rapat-htk-' . uniqid(),
            'jenis_rapat' => 'terbatas',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Sidang HTK',
            'waktu_mulai' => now(),
            'waktu_selesai' => now()->addHours(2),
            'status' => 'ongoing',
            'is_all_units' => false,
            'created_by' => $this->adminHtk->id,
            'pimpinan_id' => $this->adminHtk->id,
            'notulis_id' => $this->staffNotulisUnitB->id,
        ]);
        // Target undangan HANYA Unit HTK
        $agenda->units()->sync([$this->unitHtk->id]);

        // 1. Uji Scope visibleTo
        $visibleAgendas = Agenda::visibleTo($this->staffNotulisUnitB)->pluck('id')->all();
        $this->assertContains($agenda->id, $visibleAgendas, 'Agenda rapat harus muncul pada query visibleTo notulis yang ditunjuk');

        // 2. Uji Tampilan Dashboard Staf Notulis
        $responseDash = $this->actingAs($this->staffNotulisUnitB)->get('/dashboard');
        $responseDash->assertStatus(200);
        $responseDash->assertSee($agenda->judul_rapat);

        // 3. Uji Tampilan Portal Presensi
        $responsePortal = $this->actingAs($this->staffNotulisUnitB)->get('/presensi');
        $responsePortal->assertStatus(200);
        $responsePortal->assertSee($agenda->judul_rapat);
    }

    /**
     * Memastikan rekan satu unit kerja dari notulis TIDAK DAPAT melihat rapat
     * jika unit mereka tidak diundang (Prinsip Least Privilege & Isolasi Data Unit).
     */
    public function test_cross_unit_colleagues_of_notulis_cannot_see_agenda(): void
    {
        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Sangat Terbatas HTK ' . uniqid(),
            'slug' => 'rapat-htk-private-' . uniqid(),
            'jenis_rapat' => 'terbatas',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Kepala HTK',
            'waktu_mulai' => now(),
            'waktu_selesai' => now()->addHours(2),
            'status' => 'ongoing',
            'is_all_units' => false,
            'created_by' => $this->adminHtk->id,
            'pimpinan_id' => $this->adminHtk->id,
            'notulis_id' => $this->staffNotulisUnitB->id,
        ]);
        $agenda->units()->sync([$this->unitHtk->id]);

        // Rekan satu unit notulis tidak boleh melihat di query scope
        $visibleAgendas = Agenda::visibleTo($this->otherStaffUnitB)->pluck('id')->all();
        $this->assertNotContains($agenda->id, $visibleAgendas, 'Rekan seunit notulis TIDAK BOLEH melihat agenda internal unit lain');

        // Rekan seunit tidak boleh melihat di dashboard
        $responseDash = $this->actingAs($this->otherStaffUnitB)->get('/dashboard');
        $responseDash->assertStatus(200);
        $responseDash->assertDontSee($agenda->judul_rapat);

        // Rekan seunit diblokir saat mencoba akses langsung ke presensi
        $responseCheckin = $this->actingAs($this->otherStaffUnitB)->get("/agendas/{$agenda->id}/presensi");
        $responseCheckin->assertStatus(403);
    }

    /**
     * Memastikan Notulis lintas unit diakui eligible dan dapat mengisi formulir presensi.
     */
    public function test_cross_unit_notulis_is_eligible_and_can_check_in(): void
    {
        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Koordinasi Pegawai ' . uniqid(),
            'slug' => 'rapat-koord-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Rapat Utama',
            'waktu_mulai' => now(),
            'waktu_selesai' => now()->addHours(2),
            'status' => 'ongoing',
            'is_all_units' => false,
            'created_by' => $this->adminHtk->id,
            'pimpinan_id' => $this->adminHtk->id,
            'notulis_id' => $this->staffNotulisUnitB->id,
        ]);
        $agenda->units()->sync([$this->unitHtk->id]);

        $this->assertTrue(
            $agenda->isUserEligible($this->staffNotulisUnitB),
            'Notulis yang ditugaskan harus berstatus eligible untuk hadir'
        );

        // Formulir presensi terbuka (200 OK)
        $responseForm = $this->actingAs($this->staffNotulisUnitB)->get("/agendas/{$agenda->id}/presensi");
        $responseForm->assertStatus(200);
        $responseForm->assertSee('Formulir Presensi Rapat Kedinasan');

        // Eksekusi presensi
        $responsePost = $this->actingAs($this->staffNotulisUnitB)->post("/agendas/{$agenda->id}/presensi", [
            'selfie_data' => $this->validJpegBase64,
            'signature_data' => $this->validPngBase64,
        ]);

        $responsePost->assertRedirect();
        $this->assertDatabaseHas('attendances', [
            'agenda_id' => $agenda->id,
            'user_id' => $this->staffNotulisUnitB->id,
        ]);
    }

    /**
     * Memastikan Notulis yang ditunjuk berhak mengelola notulensi saat sesi rapat berlangsung (ongoing).
     */
    public function test_cross_unit_notulis_can_manage_minutes_during_ongoing_session(): void
    {
        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Notulen Test ' . uniqid(),
            'slug' => 'rapat-notulen-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang 3',
            'waktu_mulai' => now(),
            'waktu_selesai' => now()->addHours(2),
            'status' => 'ongoing',
            'is_all_units' => false,
            'created_by' => $this->adminHtk->id,
            'pimpinan_id' => $this->adminHtk->id,
            'notulis_id' => $this->staffNotulisUnitB->id,
        ]);
        $agenda->units()->sync([$this->unitHtk->id]);

        // 1. Saat Ongoing: Berhak buka halaman notulen
        $responseOngoing = $this->actingAs($this->staffNotulisUnitB)->get("/admin/agendas/{$agenda->id}/notulen");
        $responseOngoing->assertStatus(200);

        // 2. Update teks notulensi
        $responseUpdate = $this->actingAs($this->staffNotulisUnitB)->put("/admin/agendas/{$agenda->id}/notulen", [
            'notulensi' => '<p>Hasil kesimpulan rapat dinas resmi yang dicatat oleh notulis lintas unit.</p>',
            'action_items' => '<ol><li>Tindak lanjut dokumen kepegawaian</li></ol>',
        ]);
        $responseUpdate->assertRedirect();
        $responseUpdate->assertSessionHas('success');

        $agenda->refresh();
        $this->assertStringContainsString('Hasil kesimpulan rapat', $agenda->notulensi);

        // 3. Transisi ke Completed: Kendali pengelolaan notulensi kembali eksklusif ke penyelenggara
        $agenda->update(['status' => 'completed']);

        $responseCompleted = $this->actingAs($this->staffNotulisUnitB)->get("/admin/agendas/{$agenda->id}/notulen");
        $responseCompleted->assertStatus(403);
    }

    /**
     * Memastikan Pimpinan Rapat lintas unit dapat melihat agenda dan berhak presensi.
     */
    public function test_cross_unit_pimpinan_sees_agenda_and_is_eligible_to_attend(): void
    {
        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Dipimpin Pimpinan Lintas Unit ' . uniqid(),
            'slug' => 'rapat-pimpinan-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Senat',
            'waktu_mulai' => now(),
            'waktu_selesai' => now()->addHours(2),
            'status' => 'ongoing',
            'is_all_units' => false,
            'created_by' => $this->adminHtk->id,
            'pimpinan_id' => $this->staffNotulisUnitB->id, // Staf dari Unit B ditugaskan sebagai pimpinan
            'notulis_id' => $this->adminHtk->id,
        ]);
        $agenda->units()->sync([$this->unitHtk->id]);

        // 1. Muncul di query scope
        $visibleAgendas = Agenda::visibleTo($this->staffNotulisUnitB)->pluck('id')->all();
        $this->assertContains($agenda->id, $visibleAgendas);

        // 2. Berstatus eligible
        $this->assertTrue($agenda->isUserEligible($this->staffNotulisUnitB));

        // 3. Akses form presensi sukses (200 OK)
        $response = $this->actingAs($this->staffNotulisUnitB)->get("/agendas/{$agenda->id}/presensi");
        $response->assertStatus(200);

        // 4. Presensi sukses
        $responsePost = $this->actingAs($this->staffNotulisUnitB)->post("/agendas/{$agenda->id}/presensi", [
            'selfie_data' => $this->validJpegBase64,
            'signature_data' => $this->validPngBase64,
        ]);
        $responsePost->assertRedirect();
        $this->assertDatabaseHas('attendances', [
            'agenda_id' => $agenda->id,
            'user_id' => $this->staffNotulisUnitB->id,
        ]);
    }

    /**
     * Memastikan Admin Unit lain yang ditunjuk sebagai Notulis atau Pimpinan
     * DAPAT mengakses halaman detail admin /admin/agendas/{id} tanpa terkena 403 Forbidden.
     */
    public function test_cross_unit_admin_notulis_can_view_agenda_show_without_403(): void
    {
        // Temukan atau buat admin unit B
        $adminUnitB = User::where('unit_id', $this->unitB->id)->firstOrFail();
        $adminUnitB->update(['role' => 'admin', 'is_active' => true]);

        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Koordinasi Khusus Admin B ' . uniqid(),
            'slug' => 'rapat-koord-admin-b-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Akreditasi',
            'waktu_mulai' => now()->addHour(),
            'waktu_selesai' => now()->addHours(2),
            'status' => 'scheduled',
            'is_all_units' => false,
            'created_by' => $this->adminHtk->id,
            'pimpinan_id' => $this->adminHtk->id,
            'notulis_id' => $adminUnitB->id,
        ]);
        $agenda->units()->sync([$this->unitHtk->id]);

        $this->assertTrue($adminUnitB->can('view', $agenda), 'Admin Unit B harus diotorisasi melihat agenda di mana ia menjadi notulis');

        // Buka halaman detail admin
        $response = $this->actingAs($adminUnitB)->get("/admin/agendas/{$agenda->id}");
        $response->assertStatus(200);
        $response->assertSee($agenda->judul_rapat);
    }

    /**
     * Memastikan agenda terjadwal hari ini yang waktu selesainya telah lewat (karena belum sempat dimulai)
     * TETAP tampil pada Dashboard dan Portal Presensi anggota/notulis dengan penanda Terlewat.
     */
    public function test_scheduled_agenda_for_today_remains_visible_on_dashboard_even_if_passed_planned_end_time(): void
    {
        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Terjadwal Terlewat Hari Ini ' . uniqid(),
            'slug' => 'rapat-terlewat-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Rapat 2',
            'waktu_mulai' => now()->subMinutes(40),
            'waktu_selesai' => now()->subMinutes(15),
            'status' => 'scheduled',
            'is_all_units' => false,
            'created_by' => $this->adminHtk->id,
            'pimpinan_id' => $this->adminHtk->id,
            'notulis_id' => $this->staffNotulisUnitB->id,
        ]);
        $agenda->units()->sync([$this->unitHtk->id]);

        // 1. Masuk dalam query scopeUpcoming dan scopeRelevantForDashboard
        $this->assertTrue(Agenda::visibleTo($this->staffNotulisUnitB)->upcoming()->where('agendas.id', $agenda->id)->exists());
        $this->assertTrue(Agenda::visibleTo($this->staffNotulisUnitB)->relevantForDashboard()->where('agendas.id', $agenda->id)->exists());

        // 2. Tampil di Dashboard staf dengan badge Terlewat
        $responseDash = $this->actingAs($this->staffNotulisUnitB)->get('/dashboard');
        $responseDash->assertStatus(200);
        $responseDash->assertSee($agenda->judul_rapat);
        $responseDash->assertSee('Terlewat');

        // 3. Tampil di Portal Presensi
        $responsePortal = $this->actingAs($this->staffNotulisUnitB)->get('/presensi');
        $responsePortal->assertStatus(200);
        $responsePortal->assertSee($agenda->judul_rapat);
    }
}
