<?php

namespace Database\Seeders;

use App\Models\Agenda;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;

class AgendaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $adminAkm   = User::where('username', 'admin_akademik')->first();
        $adminKlb   = User::where('username', 'admin_kelembagaan')->first();

        $unitAkm = Unit::where('kode_unit', 'POKJA-AKM')->first();
        $unitKlb = Unit::where('kode_unit', 'POKJA-KLB')->first();
        $unitMutu = Unit::where('kode_unit', 'POKJA-MUTU')->first();

        if (!$superadmin) {
            return;
        }

        // 1. Ongoing Universal Meeting
        $agenda1 = Agenda::updateOrCreate(
            ['judul_rapat' => 'Rapat Koordinasi Evaluasi Pelaporan PDDikti Semester Genap'],
            [
                'created_by' => $superadmin->id,
                'slug' => 'rakor-evaluasi-pddikti-genap-2026',
                'jenis_rapat' => 'koordinasi',
                'tipe_rapat' => 'hybrid',
                'lokasi_ruang' => 'Ruang Sidang Utama Lantai 2, Gedung LLDIKTI',
                'link_meeting' => 'https://zoom.us/j/1234567890?pwd=lldikti-rakor',
                'waktu_mulai' => now()->subHour(),
                'waktu_selesai' => now()->addHours(2),
                'is_all_units' => true,
                'status' => 'ongoing',
            ]
        );

        // 2. Upcoming Multi-Unit Meeting (Pokja AKM & Mutu)
        $agenda2 = Agenda::updateOrCreate(
            ['judul_rapat' => 'Konsinyasi Penjaminan Mutu & Akreditasi Program Studi Baru'],
            [
                'created_by' => $adminAkm?->id ?? $superadmin->id,
                'slug' => 'konsinyasi-penjaminan-mutu-prodi-baru',
                'jenis_rapat' => 'konsinyasi',
                'tipe_rapat' => 'offline',
                'lokasi_ruang' => 'Ruang Rapat Pokja Akademik Lantai 1',
                'waktu_mulai' => now()->addDay()->setHour(9)->setMinute(0),
                'waktu_selesai' => now()->addDay()->setHour(12)->setMinute(0),
                'is_all_units' => false,
                'status' => 'scheduled',
            ]
        );
        if ($unitAkm && $unitMutu) {
            $agenda2->units()->syncWithoutDetaching([$unitAkm->id, $unitMutu->id]);
        }

        // 3. Upcoming Limited Meeting (Pokja Kelembagaan)
        $agenda3 = Agenda::updateOrCreate(
            ['judul_rapat' => 'Rapat Terbatas Evaluasi Usulan Pembukaan PTS Baru'],
            [
                'created_by' => $adminKlb?->id ?? $superadmin->id,
                'slug' => 'rapat-terbatas-evaluasi-pts-baru',
                'jenis_rapat' => 'terbatas',
                'tipe_rapat' => 'offline',
                'lokasi_ruang' => 'Ruang Rapat Pimpinan Kelembagaan',
                'waktu_mulai' => now()->addDays(2)->setHour(13)->setMinute(30),
                'waktu_selesai' => now()->addDays(2)->setHour(16)->setMinute(0),
                'is_all_units' => false,
                'status' => 'scheduled',
            ]
        );
        if ($unitKlb) {
            $agenda3->units()->syncWithoutDetaching([$unitKlb->id]);
        }
    }
}
