<?php

namespace Database\Factories;

use App\Models\Agenda;
use App\Models\AgendaDocumentation;
use App\Models\Attendance;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Agenda>
 */
class AgendaFactory extends Factory
{
    protected $model = Agenda::class;

    /**
     * Topik rapat resmi kedinasan LLDIKTI Wilayah X (Anti AI Slop).
     *
     * @var array<string, list<string>>
     */
    protected static array $topics = [
        'pleno' => [
            'Rapat Pleno Evaluasi Capaian Kinerja dan Penyerapan Anggaran Triwulan',
            'Rapat Pleno Koordinasi Program Prioritas dan Sasaran Mutu Layanan PTS',
            'Rapat Pleno Penetapan Kebijakan Operasional dan Rencana Kerja Tahunan',
            'Rapat Pleno Tindak Lanjut Hasil Audit Kinerja dan Pengawasan Internal',
        ],
        'koordinasi' => [
            'Rapat Koordinasi Pemutakhiran Data PDDikti dan Sinkronisasi Kurikulum PTS',
            'Rapat Koordinasi Verifikasi Kuota Beasiswa KIP-Kuliah dan Pembinaan Ormawa',
            'Rapat Koordinasi Integrasi Infrastruktur Server, Keamanan Siber, dan Portal Satu Data',
            'Rapat Tim Penilai Beban Kerja Dosen (BKD) dan Sertifikasi Pendidik Tahap I',
            'Rapat Koordinasi Penguatan Kemitraan Dunia Usaha dan Dunia Industri (DUDI)',
            'Rapat Koordinasi Fasilitasi Peningkatan Kualifikasi Akademik Tenaga Pendidik',
        ],
        'sosialisasi' => [
            'Sosialisasi Kebijakan Nasional Penjaminan Mutu Eksternal dan Akreditasi Unggul',
            'Sosialisasi Program Magang dan Studi Independen Bersertifikat (MSIB) Mandiri',
            'Sosialisasi Regulasi Terbaru Pembukaan Program Studi dan Alih Kelola PTS',
            'Sosialisasi Pedoman Operasional Beban Kerja Dosen dan Kenaikan Jabatan Akademik',
        ],
        'bimtek' => [
            'Bimbingan Teknis Pengusulan Pendirian dan Perubahan Bentuk Perguruan Tinggi',
            'Bimbingan Teknis Tata Kelola Keuangan dan Akuntabilitas Laporan Pertanggungjawaban PTS',
            'Bimbingan Teknis Akselerasi Pemenuhan Dokumen Akreditasi Program Studi BAN-PT/LAM',
            'Bimbingan Teknis Peningkatan Kapasitas Operator SISTER dan Sistem Basis Data',
        ],
        'evaluasi' => [
            'Rapat Evaluasi Pemeliharaan Sarana Gedung, Inventarisasi BMN, dan Utilitas Kantor',
            'Rapat Tinjauan Hukum, Disiplin ASN, dan Tata Laksana Persuratan Elektronik',
            'Rapat Evaluasi Keterbukaan Informasi Publik dan Pengelolaan Pengaduan Masyarakat',
            'Rapat Evaluasi Pengelolaan Kearsipan Dinamis dan Digitalisasi Dokumen Kedinasan',
        ],
        'perencanaan' => [
            'Rapat Penyusunan Rencana Kerja dan Anggaran Kementerian/Lembaga (RKA-K/L)',
            'Rapat Perencanaan Pengadaan Barang dan Jasa Pemerintah (PBJ) Triwulan Mendatang',
            'Rapat Penyusunan Standar Operasional Prosedur (SOP) Layanan Akademik Terpadu',
        ],
        'khusus' => [
            'Rapat Konsolidasi Teknis Kunjungan Kerja Pejabat Kemdiktisaintek dan Komisi X DPR RI',
            'Rapat Mediasi Pembinaan Tata Kelola dan Penyelesaian Kendala Kelembagaan PTS',
        ],
    ];

    /**
     * Lokasi ruang rapat resmi LLDIKTI Wilayah X.
     *
     * @var list<string>
     */
    protected static array $rooms = [
        'Aula Utama Lantai 3, Gedung LLDIKTI Wilayah X',
        'Ruang Sidang Utama Lantai 2, Gedung LLDIKTI Wilayah X',
        'Ruang Rapat Sumber Daya Perguruan Tinggi Lantai 2',
        'Ruang Command Center SIKS Lantai 2',
        'Ruang Sidang Kemahasiswaan Lantai 1',
        'Ruang Rapat Tata Usaha Lantai 1',
        'Ruang Rapat HKTL Lantai 2',
        'Ruang Rapat Pimpinan Lantai 2',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jenis = fake()->randomElement(['pleno', 'koordinasi', 'sosialisasi', 'bimtek', 'evaluasi', 'perencanaan', 'khusus']);
        $tipe = fake()->randomElement(['offline', 'online', 'hybrid']);

        $topicList = static::$topics[$jenis] ?? static::$topics['koordinasi'];
        $baseTitle = fake()->randomElement($topicList);
        $periodSuffix = fake()->randomElement(['Tahun Anggaran ' . date('Y'), 'Semester Ganjil', 'Semester Genap', 'Triwulan Berjalan']);
        $judulRapat = "{$baseTitle} {$periodSuffix}";

        $slug = Str::slug($judulRapat) . '-' . Str::lower(Str::random(6));

        $room = in_array($tipe, ['offline', 'hybrid'], true) ? fake()->randomElement(static::$rooms) : null;
        $link = in_array($tipe, ['online', 'hybrid'], true)
            ? 'https://zoom.us/j/' . fake()->numerify('##########') . '?pwd=lldikti-' . Str::lower(Str::random(6))
            : null;

        // Penjadwalan realistis (default: scheduled 1-14 hari mendatang)
        $waktuMulai = Carbon::now()->addDays(fake()->numberBetween(1, 14))->setTime(
            fake()->randomElement([8, 9, 10, 13, 14]),
            fake()->randomElement([0, 30])
        );
        $waktuSelesai = (clone $waktuMulai)->addHours(fake()->numberBetween(2, 3));

        $creator = fn () => User::query()->inRandomOrder()->first()?->id ?? User::factory();
        $pimpinan = fn () => User::query()->where('role', 'administrator')->inRandomOrder()->first()?->id
            ?? User::query()->inRandomOrder()->first()?->id
            ?? User::factory();
        $notulis = fn () => User::query()->inRandomOrder()->first()?->id ?? User::factory();

        return [
            'created_by' => $creator,
            'pimpinan_id' => $pimpinan,
            'notulis_id' => $notulis,
            'judul_rapat' => $judulRapat,
            'slug' => $slug,
            'jenis_rapat' => $jenis,
            'tipe_rapat' => $tipe,
            'lokasi_ruang' => $room,
            'link_meeting' => $link,
            'waktu_mulai' => $waktuMulai,
            'waktu_selesai' => $waktuSelesai,
            'is_all_units' => true,
            'surat_edaran_path' => null,
            'notulensi' => null,
            'kesimpulan' => null,
            'status' => 'scheduled',
            'report_config' => null,
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Agenda $agenda) {
            if ($agenda->report_config === null) {
                $agenda->report_config = $agenda->getDefaultReportConfig();
                $agenda->saveQuietly();
            }
        });
    }

    /**
     * State: Agenda Terjadwal (Scheduled - Mendatang).
     */
    public function scheduled(int $daysAhead = 3): static
    {
        return $this->state(function () use ($daysAhead) {
            $waktuMulai = Carbon::now()->addDays($daysAhead)->setTime(
                fake()->randomElement([9, 10, 13, 14]),
                fake()->randomElement([0, 30])
            );

            return [
                'status' => 'scheduled',
                'waktu_mulai' => $waktuMulai,
                'waktu_selesai' => (clone $waktuMulai)->addHours(2),
                'notulensi' => null,
                'kesimpulan' => null,
            ];
        });
    }

    /**
     * State: Agenda Sedang Berlangsung (Ongoing - Presensi Dibuka).
     */
    public function ongoing(int $minutesStartedAgo = 30): static
    {
        return $this->state(function () use ($minutesStartedAgo) {
            $waktuMulai = Carbon::now()->subMinutes($minutesStartedAgo);
            $waktuSelesai = Carbon::now()->addHours(2);

            return [
                'status' => 'ongoing',
                'waktu_mulai' => $waktuMulai,
                'waktu_selesai' => $waktuSelesai,
                'notulensi' => null,
                'kesimpulan' => null,
            ];
        });
    }

    /**
     * State: Agenda Selesai (Completed - Presensi Ditutup).
     */
    public function completed(int $daysAgo = 5): static
    {
        return $this->state(function () use ($daysAgo) {
            $waktuMulai = Carbon::now()->subDays($daysAgo)->setTime(9, 0);
            $waktuSelesai = (clone $waktuMulai)->addHours(3);

            return [
                'status' => 'completed',
                'waktu_mulai' => $waktuMulai,
                'waktu_selesai' => $waktuSelesai,
            ];
        });
    }

    /**
     * State: Agenda Konsep (Draft).
     */
    public function draft(): static
    {
        return $this->state(function () {
            $waktuMulai = Carbon::now()->addDays(7)->setTime(9, 0);

            return [
                'status' => 'draft',
                'waktu_mulai' => $waktuMulai,
                'waktu_selesai' => (clone $waktuMulai)->addHours(2),
                'notulensi' => null,
                'kesimpulan' => null,
            ];
        });
    }

    /**
     * State: Agenda Dibatalkan (Cancelled).
     */
    public function cancelled(): static
    {
        return $this->state(function () {
            $waktuMulai = Carbon::now()->subDays(2)->setTime(10, 0);

            return [
                'status' => 'cancelled',
                'waktu_mulai' => $waktuMulai,
                'waktu_selesai' => (clone $waktuMulai)->addHours(2),
                'notulensi' => null,
                'kesimpulan' => null,
            ];
        });
    }

    /**
     * State: Rapat Tanpa Batas Waktu Akhir ("s/d Selesai").
     */
    public function openEnded(): static
    {
        return $this->state(fn () => [
            'waktu_selesai' => null,
        ]);
    }

    /**
     * State: Lengkap dengan Notulensi dan Kesimpulan Kedinasan Resmi.
     */
    public function withNotulen(): static
    {
        return $this->state(fn () => [
            'notulensi' => '<h3>1. Pembukaan dan Arahan Pimpinan</h3><p>Rapat dibuka secara resmi dengan penekanan pada penguatan tata kelola, kepatuhan pelaporan akademik, dan peningkatan akuntabilitas anggaran seluruh subbagian.</p><h3>2. Poin Pokok Pembahasan</h3><ul><li>Evaluasi pencapaian target kinerja triwulan dan tindak lanjut rekomendasi inspektorat.</li><li>Sinkronisasi data layanan operasional pada sistem informasi terpadu.</li><li>Mitigasi kendala administratif dalam proses validasi usulan dan layanan perguruan tinggi swasta.</li></ul>',
            'kesimpulan' => '<ol><li>Seluruh unit kerja wajib menyelesaikan matriks tindak lanjut evaluasi selambatnya akhir pekan berjalan.</li><li>Layanan verifikasi dokumen diprioritaskan melalui kanal digital guna memangkas birokrasi dan waktu pemrosesan.</li><li>Monitoring berkala dilaksanakan setiap dua pekan oleh tim penjaminan mutu internal.</li></ol>',
        ]);
    }

    /**
     * State: Tanpa Notulensi (Notulensi Kosong).
     */
    public function withoutNotulen(): static
    {
        return $this->state(fn () => [
            'notulensi' => null,
            'kesimpulan' => null,
        ]);
    }

    /**
     * State: Format Pelaksanaan Offline.
     */
    public function offline(?string $room = null): static
    {
        return $this->state(fn () => [
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => $room ?? fake()->randomElement(static::$rooms),
            'link_meeting' => null,
        ]);
    }

    /**
     * State: Format Pelaksanaan Online.
     */
    public function online(?string $link = null): static
    {
        return $this->state(fn () => [
            'tipe_rapat' => 'online',
            'lokasi_ruang' => null,
            'link_meeting' => $link ?? ('https://zoom.us/j/' . fake()->numerify('##########') . '?pwd=lldikti-' . Str::lower(Str::random(6))),
        ]);
    }

    /**
     * State: Format Pelaksanaan Hybrid.
     */
    public function hybrid(?string $room = null, ?string $link = null): static
    {
        return $this->state(fn () => [
            'tipe_rapat' => 'hybrid',
            'lokasi_ruang' => $room ?? fake()->randomElement(static::$rooms),
            'link_meeting' => $link ?? ('https://zoom.us/j/' . fake()->numerify('##########') . '?pwd=lldikti-' . Str::lower(Str::random(6))),
        ]);
    }

    /**
     * State: Seluruh Unit Kerja Diundang (Universal).
     */
    public function universal(): static
    {
        return $this->state(fn () => [
            'is_all_units' => true,
        ])->afterCreating(function (Agenda $agenda) {
            $agenda->units()->detach();
        });
    }

    /**
     * State: Rapat Khusus 1 Unit Kerja Tertentu.
     */
    public function forUnit(Unit|string $unit): static
    {
        return $this->state(fn () => [
            'is_all_units' => false,
        ])->afterCreating(function (Agenda $agenda) use ($unit) {
            $unitModel = $unit instanceof Unit
                ? $unit
                : Unit::where('kode_unit', $unit)->orWhere('nama_unit', $unit)->first();

            if ($unitModel) {
                $agenda->units()->syncWithoutDetaching([$unitModel->id]);
            }
        });
    }

    /**
     * State: Rapat Multi-Unit (Beberapa Unit Kerja Terpilih).
     *
     * @param  array<Unit|string|int>  $units
     */
    public function forUnits(array $units): static
    {
        return $this->state(fn () => [
            'is_all_units' => false,
        ])->afterCreating(function (Agenda $agenda) use ($units) {
            $unitIds = collect($units)->map(function ($u) {
                if ($u instanceof Unit) {
                    return $u->id;
                }
                if (is_int($u)) {
                    return $u;
                }

                return Unit::where('kode_unit', $u)->orWhere('nama_unit', $u)->value('id');
            })->filter()->values()->all();

            if (! empty($unitIds)) {
                $agenda->units()->sync($unitIds);
            }
        });
    }

    /**
     * Tanamkan data presensi peserta rapat (Attendances) secara otomatis.
     */
    public function withAttendances(int $count = 10): static
    {
        return $this->afterCreating(function (Agenda $agenda) use ($count) {
            AttendanceFactory::ensureSampleAssets();

            $invitedUnits = $agenda->is_all_units
                ? Unit::all()
                : $agenda->units;

            $candidates = User::query()
                ->when(! $agenda->is_all_units && $invitedUnits->isNotEmpty(), function ($q) use ($invitedUnits) {
                    $q->whereIn('unit_id', $invitedUnits->pluck('id'));
                })
                ->get();

            if ($candidates->isEmpty()) {
                $candidates = User::all();
            }

            if ($candidates->isEmpty()) {
                $candidates = User::factory()->count($count)->create();
            }

            $selectedUsers = $candidates->shuffle()->take(min($count, $candidates->count()));
            $baseTime = $agenda->waktu_mulai ?? now()->subHour();

            foreach ($selectedUsers as $index => $user) {
                $signedAt = $baseTime->copy()->addMinutes(min(45, ($index + 1) * 2));

                Attendance::updateOrCreate(
                    [
                        'agenda_id' => $agenda->id,
                        'user_id' => $user->id,
                    ],
                    [
                        'selfie_path' => 'attendances/seeded/sample-selfie.jpg',
                        'signature_path' => 'attendances/seeded/sample-signature.png',
                        'signed_at' => $signedAt,
                        'ip_address' => '10.10.' . fake()->numberBetween(1, 10) . '.' . (10 + $index),
                        'user_agent' => 'Mozilla/5.0 (Enterprise SIPERAPAT)',
                    ]
                );
            }
        });
    }

    /**
     * Tanamkan dokumentasi foto kegiatan rapat secara otomatis.
     */
    public function withDocumentation(int $count = 2): static
    {
        return $this->afterCreating(function (Agenda $agenda) use ($count) {
            AttendanceFactory::ensureSampleAssets();

            $captions = [
                'Pembukaan dan pengarahan pimpinan rapat',
                'Pemaparan materi evaluasi program kerja dan capaian kinerja',
                'Sesi diskusi interaktif dan perumusan rekomendasi',
                'Penetapan kesimpulan dan tindak lanjut kegiatan kedinasan',
            ];

            for ($i = 0; $i < $count; $i++) {
                AgendaDocumentation::create([
                    'agenda_id' => $agenda->id,
                    'file_path' => 'attendances/seeded/sample-selfie.jpg',
                    'caption' => $captions[$i % count($captions)],
                    'sort_order' => $i + 1,
                ]);
            }
        });
    }
}
