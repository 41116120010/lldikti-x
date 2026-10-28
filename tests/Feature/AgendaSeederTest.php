<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\AgendaSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AgendaSeederTest extends TestCase
{
    use DatabaseTransactions;

    public function test_agenda_seeder_populates_diverse_agendas_based_on_official_units_and_employees_idempotently(): void
    {
        // Bersihkan agenda fixture default di dalam transaksi agar perhitungan akurat
        Attendance::query()->delete();
        Agenda::query()->delete();

        // Jalankan AgendaSeeder pertama kali
        $this->seed(AgendaSeeder::class);

        $agendas = Agenda::with(['creator', 'pimpinan', 'notulis', 'units', 'attendances'])->get();
        $this->assertCount(12, $agendas, 'AgendaSeeder harus menanamkan tepat 12 skenario agenda rapat.');

        // 1. Verifikasi variasi status rapat
        $statuses = $agendas->pluck('status')->unique()->sort()->values()->all();
        $this->assertEquals(['cancelled', 'completed', 'draft', 'ongoing', 'scheduled'], $statuses);

        // 2. Verifikasi variasi format rapat (offline, online, hybrid)
        $formats = $agendas->pluck('tipe_rapat')->unique()->sort()->values()->all();
        $this->assertEquals(['hybrid', 'offline', 'online'], $formats);

        // 3. Verifikasi variasi kategori / jenis_rapat
        $categories = $agendas->pluck('jenis_rapat')->unique()->all();
        $this->assertContains('pleno', $categories);
        $this->assertContains('koordinasi', $categories);
        $this->assertContains('sosialisasi', $categories);
        $this->assertContains('evaluasi', $categories);
        $this->assertContains('perencanaan', $categories);
        $this->assertContains('bimtek', $categories);
        $this->assertContains('khusus', $categories);

        // 4. Verifikasi kondisi notulensi pada rapat selesai (lengkap vs belum diisi)
        $completedAgendas = $agendas->where('status', 'completed');
        $this->assertCount(6, $completedAgendas);
        $completedWithMinutes = $completedAgendas->filter(fn (Agenda $a) => ! empty($a->notulensi) && ! empty($a->kesimpulan));
        $completedWithoutMinutes = $completedAgendas->filter(fn (Agenda $a) => empty($a->notulensi));
        $this->assertCount(4, $completedWithMinutes);
        $this->assertCount(2, $completedWithoutMinutes);

        // 5. Verifikasi seluruh penyelenggara, pemimpin, dan notulis merujuk pada pegawai resmi UserSeeder
        $officialUserIds = User::pluck('id')->all();
        foreach ($agendas as $agenda) {
            $this->assertContains($agenda->created_by, $officialUserIds);
            $this->assertContains($agenda->pimpinan_id, $officialUserIds);
            $this->assertContains($agenda->notulis_id, $officialUserIds);
            $this->assertNotEmpty($agenda->nama_pimpinan);
            $this->assertNotEmpty($agenda->nama_notulis);
        }

        // 6. Verifikasi cakupan unit kerja (seluruh unit, multi-unit, single-unit)
        $allUnitAgendas = $agendas->where('is_all_units', true);
        $scopedUnitAgendas = $agendas->where('is_all_units', false);
        $this->assertCount(3, $allUnitAgendas);
        $this->assertCount(9, $scopedUnitAgendas);

        $officialUnitIds = Unit::pluck('id')->all();
        foreach ($scopedUnitAgendas as $scopedAgenda) {
            $this->assertNotEmpty($scopedAgenda->units, "Agenda [{$scopedAgenda->slug}] wajib memiliki relasi unit kerja.");
            foreach ($scopedAgenda->units as $unit) {
                $this->assertContains($unit->id, $officialUnitIds);
            }
        }

        // 7. Verifikasi idempotency: jalankan AgendaSeeder kedua kali, jumlah agenda & presensi tidak boleh mengganda
        $attendanceCountFirstRun = Attendance::count();
        $this->assertGreaterThan(50, $attendanceCountFirstRun);

        $this->seed(AgendaSeeder::class);

        $this->assertCount(12, Agenda::all(), 'Eksekusi ulang AgendaSeeder tidak boleh menduplikasi baris agenda.');
        $this->assertSame($attendanceCountFirstRun, Attendance::count(), 'Eksekusi ulang AgendaSeeder tidak boleh menduplikasi baris presensi.');
    }
}
