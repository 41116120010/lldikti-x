<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AgendaConfidentialityTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $staffAttended;
    protected User $staffAbsent;
    protected User $pimpinan;
    protected User $notulis;
    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', 'administrator')->firstOrFail();
        $this->unit = Unit::firstOrFail();

        $this->staffAttended = User::create([
            'nip' => '19950101' . rand(10000000, 99999999),
            'name' => 'Staf Hadir ' . uniqid(),
            'username' => 'staf.hadir.' . uniqid(),
            'email' => 'staf.hadir.' . uniqid() . '@lldikti.test',
            'password' => bcrypt('Password123!'),
            'role' => 'staff',
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);

        $this->staffAbsent = User::create([
            'nip' => '19950102' . rand(10000000, 99999999),
            'name' => 'Staf Mangkir ' . uniqid(),
            'username' => 'staf.mangkir.' . uniqid(),
            'email' => 'staf.mangkir.' . uniqid() . '@lldikti.test',
            'password' => bcrypt('Password123!'),
            'role' => 'staff',
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);

        $this->pimpinan = User::create([
            'nip' => '19950103' . rand(10000000, 99999999),
            'name' => 'Pimpinan Ditunjuk ' . uniqid(),
            'username' => 'pimpinan.' . uniqid(),
            'email' => 'pimpinan.' . uniqid() . '@lldikti.test',
            'password' => bcrypt('Password123!'),
            'role' => 'staff',
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);

        $this->notulis = User::create([
            'nip' => '19950104' . rand(10000000, 99999999),
            'name' => 'Notulis Ditunjuk ' . uniqid(),
            'username' => 'notulis.' . uniqid(),
            'email' => 'notulis.' . uniqid() . '@lldikti.test',
            'password' => bcrypt('Password123!'),
            'role' => 'staff',
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);
    }

    public function test_staff_who_attended_can_view_completed_agenda(): void
    {
        $agenda = Agenda::create([
            'created_by' => $this->admin->id,
            'judul_rapat' => 'Rapat Pleno Rahasia ' . uniqid(),
            'slug' => 'rapat-pleno-rahasia-' . uniqid(),
            'jenis_rapat' => 'pleno',
            'tipe_rapat' => 'luring',
            'waktu_mulai' => now()->subHours(4),
            'waktu_selesai' => now()->subHours(2),
            'lokasi_ruang' => 'Ruang Sidang Utama',
            'status' => 'completed',
            'target_type' => 'all_units',
            'is_all_units' => true,
            'notulensi' => '<p>Poin pembahasan rahasia sidang pleno.</p>',
            'kesimpulan' => '<p>Kesimpulan dan RTL rahasia.</p>',
        ]);

        // Rekam presensi staf hadir
        Attendance::create([
            'agenda_id' => $agenda->id,
            'user_id' => $this->staffAttended->id,
            'signed_at' => now()->subHours(3),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Browser',
        ]);

        $response = $this->actingAs($this->staffAttended)
            ->get(route('agendas.show', $agenda));

        $response->assertStatus(200);
        $response->assertSee('Poin pembahasan rahasia sidang pleno.', false);
    }

    public function test_staff_who_did_not_attend_is_forbidden_403_from_completed_agenda(): void
    {
        $agenda = Agenda::create([
            'created_by' => $this->admin->id,
            'judul_rapat' => 'Rapat Koordinasi Rahasia ' . uniqid(),
            'slug' => 'rapat-koordinasi-rahasia-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'luring',
            'waktu_mulai' => now()->subHours(4),
            'waktu_selesai' => now()->subHours(2),
            'lokasi_ruang' => 'Ruang Sidang Utama',
            'status' => 'completed',
            'target_type' => 'all_units',
            'is_all_units' => true,
            'notulensi' => '<p>Rahasia Negara Tidak Boleh Bocor.</p>',
        ]);

        // Staf mangkir tidak memiliki record presensi
        $response = $this->actingAs($this->staffAbsent)
            ->get(route('agendas.show', $agenda));

        $response->assertStatus(403);
        $response->assertSee('bersifat rahasia');
    }

    public function test_staff_can_view_ongoing_agenda_to_perform_presensi(): void
    {
        $agenda = Agenda::create([
            'created_by' => $this->admin->id,
            'judul_rapat' => 'Rapat Sedang Berlangsung ' . uniqid(),
            'slug' => 'rapat-sedang-berlangsung-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'luring',
            'waktu_mulai' => now()->subMinutes(30),
            'waktu_selesai' => now()->addHours(2),
            'lokasi_ruang' => 'Ruang Rapat A',
            'status' => 'ongoing',
            'target_type' => 'all_units',
            'is_all_units' => true,
        ]);

        // Belum presensi, tapi sesi sedang berjalan -> HARUS bisa buka detail rapat
        $response = $this->actingAs($this->staffAbsent)
            ->get(route('agendas.show', $agenda));

        $response->assertStatus(200);
        $response->assertSee('Isi Presensi Sekarang');
    }

    public function test_superadmin_and_creator_can_always_view_completed_agenda(): void
    {
        $agenda = Agenda::create([
            'created_by' => $this->admin->id,
            'judul_rapat' => 'Rapat Selesai Admin ' . uniqid(),
            'slug' => 'rapat-selesai-admin-' . uniqid(),
            'jenis_rapat' => 'pleno',
            'tipe_rapat' => 'luring',
            'waktu_mulai' => now()->subHours(5),
            'waktu_selesai' => now()->subHours(3),
            'status' => 'completed',
            'target_type' => 'all_units',
            'is_all_units' => true,
        ]);

        // Superadmin
        $response = $this->actingAs($this->admin)
            ->get(route('agendas.show', $agenda));
        $response->assertStatus(200);
    }

    public function test_appointed_pimpinan_and_notulis_can_view_completed_agenda(): void
    {
        $agenda = Agenda::create([
            'created_by' => $this->admin->id,
            'pimpinan_id' => $this->pimpinan->id,
            'notulis_id' => $this->notulis->id,
            'judul_rapat' => 'Rapat Petugas Ditunjuk ' . uniqid(),
            'slug' => 'rapat-petugas-ditunjuk-' . uniqid(),
            'jenis_rapat' => 'pleno',
            'tipe_rapat' => 'luring',
            'waktu_mulai' => now()->subHours(5),
            'waktu_selesai' => now()->subHours(3),
            'status' => 'completed',
            'target_type' => 'all_units',
            'is_all_units' => true,
        ]);

        // Pimpinan tanpa presensi
        $responsePimpinan = $this->actingAs($this->pimpinan)
            ->get(route('agendas.show', $agenda));
        $responsePimpinan->assertStatus(200);

        // Notulis tanpa presensi
        $responseNotulis = $this->actingAs($this->notulis)
            ->get(route('agendas.show', $agenda));
        $responseNotulis->assertStatus(200);
    }
}
