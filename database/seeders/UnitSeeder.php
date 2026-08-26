<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            [
                'nama_unit' => 'Bagian Tata Usaha & Umum',
                'kode_unit' => 'BAG-TU',
                'deskripsi' => 'Pengelolaan ketatausahaan, kepegawaian, keuangan, dan rumah tangga kantor.',
                'is_active' => true,
            ],
            [
                'nama_unit' => 'Pokja Akademik & Kemahasiswaan',
                'kode_unit' => 'POKJA-AKM',
                'deskripsi' => 'Pelayanan dan pembinaan bidang kurikulum, pembelajaran, dan kegiatan mahasiswa.',
                'is_active' => true,
            ],
            [
                'nama_unit' => 'Pokja Kelembagaan & Kerjasama',
                'kode_unit' => 'POKJA-KLB',
                'deskripsi' => 'Pengelolaan perizinan, pembentukan prodi/PTS, dan kemitraan kelembagaan.',
                'is_active' => true,
            ],
            [
                'nama_unit' => 'Pokja Pendidik & Tenaga Kependidikan',
                'kode_unit' => 'POKJA-PTK',
                'deskripsi' => 'Pengurusan jenjang jabatan akademik dosen, sertifikasi dosen, dan ketenagaan.',
                'is_active' => true,
            ],
            [
                'nama_unit' => 'Pokja Penjaminan Mutu & Akreditasi',
                'kode_unit' => 'POKJA-MUTU',
                'deskripsi' => 'Fasilitasi dan pengawasan sistem penjaminan mutu internal serta akreditasi perguruan tinggi.',
                'is_active' => true,
            ],
        ];

        foreach ($units as $unit) {
            Unit::updateOrCreate(
                ['kode_unit' => $unit['kode_unit']],
                $unit
            );
        }
    }
}
