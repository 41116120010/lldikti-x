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
                'nama_unit' => 'Bagian Akademik dan Kemahasiswaan',
                'kode_unit' => 'BAG-AKM',
                'deskripsi' => 'Koordinasi dan pengelolaan pelayanan bidang akademik dan kemahasiswaan LLDIKTI Wilayah X.',
                'is_active' => true,
            ],
            [
                'nama_unit' => 'Bagian Sumber Daya Perguruan Tinggi',
                'kode_unit' => 'BAG-SDPT',
                'deskripsi' => 'Koordinasi dan pengelolaan sumber daya perguruan tinggi di lingkungan LLDIKTI Wilayah X.',
                'is_active' => true,
            ],
            [
                'nama_unit' => 'Pendidik dan Tenaga Kependidikan',
                'kode_unit' => 'POKJA-PTK',
                'deskripsi' => 'Pengelolaan kualifikasi, karir, sertifikasi pendidik, dan ketenagaan perguruan tinggi.',
                'is_active' => true,
            ],
            [
                'nama_unit' => 'Kelembagaan',
                'kode_unit' => 'POKJA-KLB',
                'deskripsi' => 'Pengelolaan perizinan, pendirian, perubahan bentuk prodi/PTS, dan tata kelola kelembagaan.',
                'is_active' => true,
            ],
            [
                'nama_unit' => 'Akademik',
                'kode_unit' => 'POKJA-AKM',
                'deskripsi' => 'Pelayanan kurikulum, pembelajaran, dan pemantauan pelaporan PDDikti perguruan tinggi.',
                'is_active' => true,
            ],
            [
                'nama_unit' => 'Hukum, Kepegawaian dan Tata Laksana',
                'kode_unit' => 'SUBBAG-HKTL',
                'deskripsi' => 'Pelayanan advokasi hukum, tata laksana administrasi, dan manajemen kepegawaian ASN.',
                'is_active' => true,
            ],
            [
                'nama_unit' => 'Kemahasiswaan',
                'kode_unit' => 'SUBBAG-KMH',
                'deskripsi' => 'Pelayanan kegiatan minat, bakat, penalaran, beasiswa, dan pembinaan ormawa perguruan tinggi.',
                'is_active' => true,
            ],
            [
                'nama_unit' => 'Tata Usaha dan Barang Milik Negara',
                'kode_unit' => 'BAG-TU',
                'deskripsi' => 'Pengelolaan persuratan, tata usaha pimpinan, inventarisasi, dan barang milik negara.',
                'is_active' => true,
            ],
            [
                'nama_unit' => 'Sistem Informasi dan Kerja Sama',
                'kode_unit' => 'SUBBAG-SIKS',
                'deskripsi' => 'Pengembangan infrastruktur teknologi informasi, sistem data, publikasi, dan kemitraan kerja sama.',
                'is_active' => true,
            ],
            [
                'nama_unit' => 'Perencanaan dan Penganggaran',
                'kode_unit' => 'SUBBAG-PP',
                'deskripsi' => 'Penyusunan program kerja, rencana anggaran, pemantauan, dan evaluasi kinerja instansi.',
                'is_active' => true,
            ],
            [
                'nama_unit' => 'Sarana dan Prasarana',
                'kode_unit' => 'SUBBAG-SARPRAS',
                'deskripsi' => 'Pemeliharaan fasilitas kantor, gedung, sarana prasarana fisik, dan utilitas kerja.',
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
