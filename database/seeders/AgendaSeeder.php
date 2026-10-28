<?php

namespace Database\Seeders;

use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AgendaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeder ini menanamkan 12 agenda rapat kedinasan dengan berbagai variasi:
     * - Judul, kategori (jenis_rapat), dan format pelaksanaan (offline, online, hybrid)
     * - Rentang waktu (masa lalu, sedang berlangsung hari ini, mendatang, dan s/d selesai)
     * - Penyelenggara (creator), pemimpin rapat, dan notulis rapat (termasuk lintas unit)
     * - Cakupan unit kerja (seluruh unit, multi-unit, dan 1 unit spesifik)
     * - Status rapat (completed dengan notulensi, completed belum diisi, ongoing, scheduled, draft, cancelled)
     * - Rekaman kehadiran peserta (Attendance) berdasarkan pegawai resmi dari UserSeeder & UnitSeeder.
     */
    public function run(): void
    {
        if (Unit::count() === 0) {
            $this->call(UnitSeeder::class);
        }

        if (User::count() === 0) {
            $this->call(UserSeeder::class);
        }

        $units = Unit::all()->keyBy('kode_unit');
        $users = User::all()->keyBy('username');
        $admin = $users->get('afdalisma') ?? User::where('role', 'administrator')->firstOrFail();

        $this->ensureSampleAttendanceAssets();

        $now = Carbon::now();

        $agendasData = [
            // 1. Pleno Offline — Seluruh Unit — Completed + Notulensi & Kesimpulan Lengkap
            [
                'slug' => 'rapat-pleno-evaluasi-kinerja-semester-ganjil-lldikti-x',
                'judul_rapat' => 'Rapat Pleno Evaluasi Kinerja Semester Ganjil dan Penyerapan Anggaran LLDIKTI Wilayah X',
                'jenis_rapat' => 'pleno',
                'tipe_rapat' => 'offline',
                'lokasi_ruang' => 'Aula Utama Lantai 3, Gedung LLDIKTI Wilayah X',
                'link_meeting' => null,
                'waktu_mulai' => $now->copy()->subDays(14)->setTime(8, 30),
                'waktu_selesai' => $now->copy()->subDays(14)->setTime(12, 0),
                'is_all_units' => true,
                'unit_codes' => [],
                'creator_username' => 'afdalisma',
                'pimpinan_username' => 'afdalisma',
                'notulis_username' => 'wilda_nurhayati',
                'status' => 'completed',
                'notulensi' => '<h3>1. Pembukaan dan Arahan Pimpinan</h3><p>Rapat dibuka secara resmi oleh Ibu Afdalisma, SH, M.Pd selaku pimpinan rapat dengan menekankan pentingnya percepatan realisasi program kerja strategis pada seluruh bagian dan kelompok kerja di lingkungan LLDIKTI Wilayah X.</p><h3>2. Evaluasi Capaian Kinerja Per Bagian</h3><ul><li><b>Bagian Akademik dan Kemahasiswaan:</b> Pelaporan PDDikti semester berjalan telah mencapai 96,4% dari total perguruan tinggi swasta binaan.</li><li><b>Bagian Sumber Daya Perguruan Tinggi:</b> Proses verifikasi usulan Jabatan Fungsional Dosen dan Sertifikasi Dosen berjalan sesuai jadwal triwulan.</li><li><b>Bagian Tata Usaha dan BMN:</b> Penertiban administrasi barang milik negara dan digitalisasi arsip persuratan telah diterapkan pada seluruh subbagian.</li></ul>',
                'kesimpulan' => '<ol><li>Seluruh Koordinator Pokja dan Kasubbag wajib menyampaikan matriks tindak lanjut evaluasi kinerja paling lambat hari Jumat pekan berjalan.</li><li>Optimalisasi penyerapan anggaran triwulan akhir diprioritaskan pada kegiatan layanan langsung bagi Perguruan Tinggi Swasta.</li><li>Monitoring kepatuhan pelaporan dilakukan secara berkala setiap dua minggu melalui portal eksekutif.</li></ol>',
                'attendee_usernames' => [
                    'afdalisma', 'wilda_nurhayati', 'ely_susanti', 'febrina_fitri',
                    'reri_anton', 'tetri_aida', 'evrinaldi', 'zamri', 'laida_prefni',
                    'rukman_wijaya', 'gusri', 'rahmi', 'febriyanti_b', 'adelia_pramita_sari',
                ],
            ],

            // 2. Koordinasi Hybrid — Multi-Unit (BAG-AKM, POKJA-AKM, SUBBAG-SIKS) — Completed + Notulensi Lengkap
            [
                'slug' => 'rakor-pemutakhiran-data-pddikti-dan-kurikulum-pts',
                'judul_rapat' => 'Rapat Koordinasi Pemutakhiran Data PDDikti dan Sinkronisasi Kurikulum Perguruan Tinggi Swasta',
                'jenis_rapat' => 'koordinasi',
                'tipe_rapat' => 'hybrid',
                'lokasi_ruang' => 'Ruang Sidang Utama Lantai 2, Gedung LLDIKTI Wilayah X',
                'link_meeting' => 'https://zoom.us/j/98234120911?pwd=lldikti10-pddikti',
                'waktu_mulai' => $now->copy()->subDays(10)->setTime(9, 0),
                'waktu_selesai' => $now->copy()->subDays(10)->setTime(11, 30),
                'is_all_units' => false,
                'unit_codes' => ['BAG-AKM', 'POKJA-AKM', 'SUBBAG-SIKS'],
                'creator_username' => 'ely_susanti',
                'pimpinan_username' => 'ely_susanti',
                'notulis_username' => 'evrinaldi',
                'status' => 'completed',
                'notulensi' => '<h3>Pembahasan Sinkronisasi Feeder PDDikti</h3><p>Tim Pokja Akademik bersama Subbagian Sistem Informasi dan Kerja Sama memaparkan hasil rekonsiliasi data mahasiswa aktif dan lulusan pada pangkalan data pendidikan tinggi.</p><ul><li>Terdapat 14 program studi baru yang memerlukan pendampingan teknis pengisian checkpoint laporan akademik.</li><li>Integrasi API layanan akademik internal telah berjalan stabil dengan waktu respons rata-rata di bawah 200ms.</li></ul>',
                'kesimpulan' => '<ol><li>Pokja Akademik menjadwalkan klinik pendampingan teknis PDDikti secara daring bagi operator PTS setiap hari Rabu.</li><li>Subbagian SIKS menyiapkan pemantauan ketersediaan layanan sinkronisasi data selama masa penutupan pelaporan semester.</li></ol>',
                'attendee_usernames' => [
                    'ely_susanti', 'evrinaldi', 'hendri_wandi', 'taufik',
                    'putra_heriyadi', 'mukhlis', 'rukman_wijaya', 'sirjon',
                    'jerry_citra', 'ori_adrian',
                ],
            ],

            // 3. Koordinasi Offline — Multi-Unit (BAG-SDPT, POKJA-PTK) — Completed + Notulensi Lengkap
            [
                'slug' => 'rapat-tim-penilai-bkd-dan-sertifikasi-pendidik-tahap-1',
                'judul_rapat' => 'Rapat Tim Penilai Beban Kerja Dosen (BKD) dan Verifikasi Sertifikasi Pendidik Tahap I',
                'jenis_rapat' => 'koordinasi',
                'tipe_rapat' => 'offline',
                'lokasi_ruang' => 'Ruang Rapat Sumber Daya Perguruan Tinggi Lantai 2',
                'link_meeting' => null,
                'waktu_mulai' => $now->copy()->subDays(7)->setTime(13, 30),
                'waktu_selesai' => $now->copy()->subDays(7)->setTime(16, 0),
                'is_all_units' => false,
                'unit_codes' => ['BAG-SDPT', 'POKJA-PTK'],
                'creator_username' => 'febrina_fitri',
                'pimpinan_username' => 'reri_anton',
                'notulis_username' => 'suhartini',
                'status' => 'completed',
                'notulensi' => '<h3>Verifikasi Dokumen Serdos dan Laporan BKD</h3><p>Rapat membahas validasi pemenuhan kewajiban khusus dosen dan kesesuaian penugasan asesor Beban Kerja Dosen pada aplikasi SISTER.</p><p>Seluruh anggota Pokja PTK melakukan pemeriksaan silang terhadap portofolio peserta sertifikasi dosen gelombang berjalan.</p>',
                'kesimpulan' => '<ul><li>Berita acara penetapan kelulusan verifikasi berkas tahap I diserahkan kepada Bagian Sumber Daya Perguruan Tinggi untuk pengesahan.</li><li>Daftar dosen yang memerlukan perbaikan dokumen diumumkan melalui kanal resmi SISTER paling lambat besok pagi.</li></ul>',
                'attendee_usernames' => [
                    'febrina_fitri', 'reri_anton', 'edi_sastra', 'suhartini',
                    'eri_suryadi', 'arifta_surya',
                ],
            ],

            // 4. Sosialisasi Online — Multi-Unit (POKJA-KLB, POKJA-MUTU, POKJA-AKM) — Completed + Notulensi Lengkap
            [
                'slug' => 'sosialisasi-kebijakan-penjaminan-mutu-eksternal-dan-akreditasi',
                'judul_rapat' => 'Sosialisasi Kebijakan Nasional Penjaminan Mutu Eksternal dan Akselerasi Akreditasi Unggul',
                'jenis_rapat' => 'sosialisasi',
                'tipe_rapat' => 'online',
                'lokasi_ruang' => null,
                'link_meeting' => 'https://zoom.us/j/94511203844?pwd=mutu-lldikti10',
                'waktu_mulai' => $now->copy()->subDays(5)->setTime(9, 0),
                'waktu_selesai' => $now->copy()->subDays(5)->setTime(12, 0),
                'is_all_units' => false,
                'unit_codes' => ['POKJA-KLB', 'POKJA-MUTU', 'POKJA-AKM'],
                'creator_username' => 'afdalisma',
                'pimpinan_username' => 'tetri_aida',
                'notulis_username' => 'anita',
                'status' => 'completed',
                'notulensi' => '<h3>Pemaparan Instrumen Pemantauan dan Evaluasi Mutu</h3><p>Kegiatan sosialisasi diikuti oleh jajaran Pokja Kelembagaan, Pokja Penjaminan Mutu, dan Pokja Akademik guna menyelaraskan instrumen pendampingan akreditasi program studi pada perguruan tinggi swasta.</p>',
                'kesimpulan' => '<ol><li>Pemetaan program studi yang masa berlaku akreditasinya berakhir dalam 6 bulan ke depan diprioritaskan untuk pendampingan intensif.</li><li>Materi sosialisasi dan panduan instrumen mutu didistribusikan kepada seluruh peserta rapat.</li></ol>',
                'attendee_usernames' => [
                    'tetri_aida', 'fikri_rusli', 'adek_irma', 'anita',
                    'rina_wahyuni', 'syahril', 'ari_mega_suryani', 'afrizal',
                    'evrinaldi', 'hendri_wandi',
                ],
            ],

            // 5. Evaluasi Offline — Multi-Unit (BAG-TU, SUBBAG-SARPRAS) — Completed namun Notulensi BELUM DIISI
            [
                'slug' => 'rapat-evaluasi-pemeliharaan-gedung-dan-inventarisasi-bmn',
                'judul_rapat' => 'Rapat Evaluasi Pemeliharaan Gedung, Inventarisasi BMN, dan Utilitas Kantor',
                'jenis_rapat' => 'evaluasi',
                'tipe_rapat' => 'offline',
                'lokasi_ruang' => 'Ruang Rapat Tata Usaha Lantai 1',
                'link_meeting' => null,
                'waktu_mulai' => $now->copy()->subDays(3)->setTime(14, 0),
                'waktu_selesai' => $now->copy()->subDays(3)->setTime(16, 0),
                'is_all_units' => false,
                'unit_codes' => ['BAG-TU', 'SUBBAG-SARPRAS'],
                'creator_username' => 'laida_prefni',
                'pimpinan_username' => 'rahmi',
                'notulis_username' => 'ezi_diana_fitri',
                'status' => 'completed',
                'notulensi' => null,
                'kesimpulan' => null,
                'attendee_usernames' => [
                    'laida_prefni', 'wilda_nurhayati', 'adelia_pramita_sari',
                    'haris_roberto', 'meiti_utami', 'rahmi', 'ezi_diana_fitri',
                    'dodi', 'gusnimar', 'rismayenti',
                ],
            ],

            // 6. Evaluasi Offline — Single Unit (SUBBAG-HKTL) — Completed namun Notulensi BELUM DIISI
            [
                'slug' => 'rapat-tinjauan-hukum-disiplin-asn-dan-persuratan-elektronik',
                'judul_rapat' => 'Rapat Tinjauan Hukum, Disiplin ASN, dan Tata Laksana Persuratan Elektronik',
                'jenis_rapat' => 'evaluasi',
                'tipe_rapat' => 'offline',
                'lokasi_ruang' => 'Ruang Rapat HKTL Lantai 2',
                'link_meeting' => null,
                'waktu_mulai' => $now->copy()->subDays(2)->setTime(9, 30),
                'waktu_selesai' => $now->copy()->subDays(2)->setTime(11, 30),
                'is_all_units' => false,
                'unit_codes' => ['SUBBAG-HKTL'],
                'creator_username' => 'zamri',
                'pimpinan_username' => 'zamri',
                'notulis_username' => 'fitri_astuti',
                'status' => 'completed',
                'notulensi' => null,
                'kesimpulan' => null,
                'attendee_usernames' => [
                    'zamri', 'fitri_astuti', 'rahman', 'ernita',
                    'hamdani', 'rida_erizal', 'andika_persia', 'apandi',
                ],
            ],

            // 7. Koordinasi Hybrid — Hari Ini (ONGOING) — Multi-Unit (SUBBAG-SIKS, SUBBAG-PP, BAG-TU)
            [
                'slug' => 'rakor-integrasi-infrastruktur-server-dan-portal-satu-data',
                'judul_rapat' => 'Rapat Koordinasi Integrasi Infrastruktur Server, Keamanan Siber, dan Portal Satu Data',
                'jenis_rapat' => 'koordinasi',
                'tipe_rapat' => 'hybrid',
                'lokasi_ruang' => 'Ruang Command Center SIKS Lantai 2',
                'link_meeting' => 'https://meet.google.com/lld-siks-data',
                'waktu_mulai' => $now->copy()->subMinutes(45),
                'waktu_selesai' => $now->copy()->addMinutes(90),
                'is_all_units' => false,
                'unit_codes' => ['SUBBAG-SIKS', 'SUBBAG-PP', 'BAG-TU'],
                'creator_username' => 'rukman_wijaya',
                'pimpinan_username' => 'rukman_wijaya',
                'notulis_username' => 'jerry_citra',
                'status' => 'ongoing',
                'notulensi' => null,
                'kesimpulan' => null,
                'attendee_usernames' => [
                    'rukman_wijaya', 'sirjon', 'yulmaizar', 'jerry_citra',
                    'ori_adrian', 'henri_ilham_caniago', 'nurhayati', 'ridwan_fadlil',
                    'gusri', 'yofan_hamaska', 'laida_prefni',
                ],
            ],

            // 8. Koordinasi Offline — Hari Ini (ONGOING, Waktu Selesai Terbuka / s/d Selesai) — Multi-Unit (BAG-AKM, SUBBAG-KMH)
            [
                'slug' => 'rakor-verifikasi-beasiswa-kip-kuliah-dan-pembinaan-ormawa',
                'judul_rapat' => 'Rapat Koordinasi Verifikasi Kuota Beasiswa KIP-Kuliah dan Pembinaan Ormawa PTS',
                'jenis_rapat' => 'koordinasi',
                'tipe_rapat' => 'offline',
                'lokasi_ruang' => 'Ruang Sidang Kemahasiswaan Lantai 1',
                'link_meeting' => null,
                'waktu_mulai' => $now->copy()->subMinutes(30),
                'waktu_selesai' => null,
                'is_all_units' => false,
                'unit_codes' => ['BAG-AKM', 'SUBBAG-KMH'],
                'creator_username' => 'febriyanti_b',
                'pimpinan_username' => 'ely_susanti',
                'notulis_username' => 'resa_amelia',
                'status' => 'ongoing',
                'notulensi' => null,
                'kesimpulan' => null,
                'attendee_usernames' => [
                    'ely_susanti', 'febriyanti_b', 'budhi_nk', 'fauzia_husna',
                    'irna_humairah', 'resa_amelia', 'kasreno_ofet_mei', 'putri_prima_yulda',
                ],
            ],

            // 9. Perencanaan Offline — Mendatang (SCHEDULED) — Seluruh Unit
            [
                'slug' => 'rapat-penyusunan-rka-kl-dan-pagu-indikatif-lldikti-x',
                'judul_rapat' => 'Rapat Penyusunan Rencana Kerja dan Anggaran Kementerian/Lembaga (RKA-K/L) Tahun Anggaran Berikutnya',
                'jenis_rapat' => 'perencanaan',
                'tipe_rapat' => 'offline',
                'lokasi_ruang' => 'Ruang Sidang Utama Lantai 2, Gedung LLDIKTI Wilayah X',
                'link_meeting' => null,
                'waktu_mulai' => $now->copy()->addDays(2)->setTime(9, 0),
                'waktu_selesai' => $now->copy()->addDays(2)->setTime(12, 0),
                'is_all_units' => true,
                'unit_codes' => [],
                'creator_username' => 'gusri',
                'pimpinan_username' => 'afdalisma',
                'notulis_username' => 'yofan_hamaska',
                'status' => 'scheduled',
                'notulensi' => null,
                'kesimpulan' => null,
                'attendee_usernames' => [],
            ],

            // 10. Bimtek Online — Mendatang (SCHEDULED) — Unit Spesifik (POKJA-KLB) + Notulis Lintas Unit
            [
                'slug' => 'bimtek-pengusulan-pendirian-dan-perubahan-bentuk-pts',
                'judul_rapat' => 'Bimbingan Teknis Pengusulan Pendirian dan Perubahan Bentuk Perguruan Tinggi Swasta',
                'jenis_rapat' => 'bimtek',
                'tipe_rapat' => 'online',
                'lokasi_ruang' => null,
                'link_meeting' => 'https://zoom.us/j/96102938475?pwd=klb-lldikti10',
                'waktu_mulai' => $now->copy()->addDays(4)->setTime(13, 30),
                'waktu_selesai' => $now->copy()->addDays(4)->setTime(16, 0),
                'is_all_units' => false,
                'unit_codes' => ['POKJA-KLB'],
                'creator_username' => 'fikri_rusli',
                'pimpinan_username' => 'tetri_aida',
                'notulis_username' => 'rina_wahyuni',
                'status' => 'scheduled',
                'notulensi' => null,
                'kesimpulan' => null,
                'attendee_usernames' => [],
            ],

            // 11. Koordinasi Hybrid — Mendatang (DRAFT / Konsep) — Multi-Unit + Lintas Unit Notulis
            [
                'slug' => 'rapat-persiapan-audit-eksternal-smki-iso-27001',
                'judul_rapat' => 'Rapat Persiapan Audit Eksternal Sistem Manajemen Keamanan Informasi (SMKI) ISO 27001',
                'jenis_rapat' => 'koordinasi',
                'tipe_rapat' => 'hybrid',
                'lokasi_ruang' => 'Ruang Rapat Pimpinan Lantai 2',
                'link_meeting' => 'https://teams.microsoft.com/l/meetup-join/smki-lldikti10',
                'waktu_mulai' => $now->copy()->addDays(6)->setTime(10, 0),
                'waktu_selesai' => $now->copy()->addDays(6)->setTime(12, 0),
                'is_all_units' => false,
                'unit_codes' => ['SUBBAG-SIKS', 'SUBBAG-HKTL', 'BAG-TU'],
                'creator_username' => 'afdalisma',
                'pimpinan_username' => 'rukman_wijaya',
                'notulis_username' => 'andika_persia',
                'status' => 'draft',
                'notulensi' => null,
                'kesimpulan' => null,
                'attendee_usernames' => [],
            ],

            // 12. Khusus Offline — Dibatalkan (CANCELLED) — Seluruh Unit
            [
                'slug' => 'rapat-konsolidasi-teknis-kunjungan-kerja-komisi-x-dpr-ri',
                'judul_rapat' => 'Rapat Konsolidasi Teknis Kunjungan Kerja Komisi X DPR RI (Dijadwalkan Ulang)',
                'jenis_rapat' => 'khusus',
                'tipe_rapat' => 'offline',
                'lokasi_ruang' => 'Aula Utama Lantai 3, Gedung LLDIKTI Wilayah X',
                'link_meeting' => null,
                'waktu_mulai' => $now->copy()->subDay()->setTime(14, 0),
                'waktu_selesai' => $now->copy()->subDay()->setTime(16, 0),
                'is_all_units' => true,
                'unit_codes' => [],
                'creator_username' => 'afdalisma',
                'pimpinan_username' => 'afdalisma',
                'notulis_username' => 'haris_roberto',
                'status' => 'cancelled',
                'notulensi' => null,
                'kesimpulan' => null,
                'attendee_usernames' => [],
            ],
        ];

        DB::transaction(function () use ($agendasData, $units, $users, $admin) {
            foreach ($agendasData as $item) {
                $creator = $users->get($item['creator_username']) ?? $admin;
                $pimpinan = $users->get($item['pimpinan_username']) ?? $creator;
                $notulis = $users->get($item['notulis_username']) ?? $creator;

                $agenda = Agenda::updateOrCreate(
                    ['slug' => $item['slug']],
                    [
                        'created_by' => $creator->id,
                        'pimpinan_id' => $pimpinan->id,
                        'notulis_id' => $notulis->id,
                        'judul_rapat' => $item['judul_rapat'],
                        'jenis_rapat' => $item['jenis_rapat'],
                        'tipe_rapat' => $item['tipe_rapat'],
                        'lokasi_ruang' => $item['lokasi_ruang'],
                        'link_meeting' => $item['link_meeting'],
                        'waktu_mulai' => $item['waktu_mulai'],
                        'waktu_selesai' => $item['waktu_selesai'],
                        'is_all_units' => $item['is_all_units'],
                        'notulensi' => $item['notulensi'],
                        'kesimpulan' => $item['kesimpulan'],
                        'status' => $item['status'],
                    ]
                );

                // Sinkronisasi konfigurasi berita acara resmi (nama & NIP pimpinan dan notulis)
                $agenda->report_config = $agenda->getDefaultReportConfig();
                $agenda->save();

                // Sinkronisasi relasi unit kerja yang diundang
                if ($item['is_all_units']) {
                    $agenda->units()->detach();
                } else {
                    $unitIds = collect($item['unit_codes'])
                        ->map(fn (string $code) => $units->get($code)?->id)
                        ->filter()
                        ->values()
                        ->all();
                    $agenda->units()->sync($unitIds);
                }

                // Tanamkan data presensi peserta (Attendance) secara idempotent
                foreach ($item['attendee_usernames'] as $idx => $username) {
                    $attendee = $users->get($username);
                    if (! $attendee) {
                        continue;
                    }

                    $signedAt = $item['waktu_mulai']->copy()->addMinutes(min(30, ($idx + 1) * 2));

                    Attendance::updateOrCreate(
                        [
                            'agenda_id' => $agenda->id,
                            'user_id' => $attendee->id,
                        ],
                        [
                            'selfie_path' => 'attendances/seeded/sample-selfie.jpg',
                            'signature_path' => 'attendances/seeded/sample-signature.png',
                            'signed_at' => $signedAt,
                            'ip_address' => '10.10.1.' . (10 + $idx),
                            'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                        ]
                    );
                }
            }
        });
    }

    /**
     * Pastikan berkas sampel selfie (JPEG) dan tanda tangan (PNG transparan) tersedia di storage/app/public
     * agar pratinjau maupun ekspor PDF/Word tidak mengalami missing file.
     */
    private function ensureSampleAttendanceAssets(): void
    {
        $disk = Storage::disk('public');

        if (! $disk->exists('attendances/seeded/sample-selfie.jpg')) {
            // Minimal valid 1x1 JPEG binary
            $minimalJpeg = base64_decode(
                '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA='
            );
            $disk->put('attendances/seeded/sample-selfie.jpg', $minimalJpeg);
        }

        if (! $disk->exists('attendances/seeded/sample-signature.png')) {
            // Minimal valid 1x1 transparent PNG binary
            $minimalPng = base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
            );
            $disk->put('attendances/seeded/sample-signature.png', $minimalPng);
        }
    }
}
