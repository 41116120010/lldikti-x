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

        $adminTu    = User::where('username', 'admin_tu')->first();
        $staffAhmad = User::where('username', 'staff_ahmad')->first();

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
                'pimpinan_id' => $superadmin->id,
                'notulis_id' => $adminKlb?->id ?? $superadmin->id,
            ]
        );
        $agenda1->report_config = $agenda1->getDefaultReportConfig();
        $agenda1->save();

        // Seed 3 attendances (including pimpinan and notulis) with existing sample images
        $attendees = [
            [
                'user' => $superadmin,
                'selfie' => 'attendances/1/selfies/selfie_1_lkfmLfAzYBJ0Gtjt.jpg',
                'signature' => 'attendances/1/signatures/sig_1_8nvcY3uYgJ91PT85.png',
            ],
            [
                'user' => $adminKlb,
                'selfie' => 'attendances/1/selfies/selfie_3_wsLRV1qcTkA7PBmC.jpg',
                'signature' => 'attendances/1/signatures/sig_3_G5j7mN0Unn14OQM8.png',
            ],
            [
                'user' => $staffAhmad,
                'selfie' => 'attendances/1/selfies/selfie_6_PZjFUOK4TRUXWo9X.jpg',
                'signature' => 'attendances/1/signatures/sig_6_ZVglWrZg1XrWtR4e.png',
            ],
        ];

        foreach ($attendees as $att) {
            if ($att['user']) {
                \App\Models\Attendance::updateOrCreate(
                    [
                        'agenda_id' => $agenda1->id,
                        'user_id' => $att['user']->id,
                    ],
                    [
                        'selfie_path' => $att['selfie'],
                        'signature_path' => $att['signature'],
                        'signed_at' => now()->subMinutes(30),
                        'ip_address' => '127.0.0.1',
                        'user_agent' => 'Mozilla/5.0 (Enterprise SIPERAPAT)',
                    ]
                );
            }
        }

        // Seed documentation for agenda1 to satisfy annex parity
        \App\Models\AgendaDocumentation::updateOrCreate(
            [
                'agenda_id' => $agenda1->id,
                'file_path' => 'documentations/1/jsObY4pNbzLxPC9joeLqsjn9AcJ5kAbv.jpg',
            ],
            [
                'caption' => 'Sesi Pembahasan Evaluasi PDDikti Semester Genap',
                'sort_order' => 1,
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
