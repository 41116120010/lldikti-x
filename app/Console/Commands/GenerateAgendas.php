<?php

namespace App\Console\Commands;

use App\Models\Agenda;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\UnitSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateAgendas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agenda:generate
                            {count=10 : Jumlah data agenda rapat yang ingin dibuat}
                            {--status= : Filter status tertentu (scheduled, ongoing, completed, draft, cancelled)}
                            {--type= : Filter format rapat (offline, online, hybrid)}
                            {--attendees=auto : Jumlah peserta presensi per agenda (angka atau "auto")}
                            {--clean : Hapus data agenda sebelumnya sebelum menanamkan data baru}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menghasilkan data palsu (faker) agenda rapat kedinasan LLDIKTI dalam jumlah banyak dengan parameter realistis untuk pengujian sistem';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = (int) $this->argument('count');
        if ($count <= 0) {
            $this->error('Jumlah agenda harus berupa angka positif minimal 1.');

            return 1;
        }

        $statusFilter = $this->option('status');
        $validStatuses = ['draft', 'scheduled', 'ongoing', 'completed', 'cancelled'];
        if ($statusFilter && ! in_array($statusFilter, $validStatuses, true)) {
            $this->error('Status tidak valid. Pilihan: ' . implode(', ', $validStatuses));

            return 1;
        }

        $typeFilter = $this->option('type');
        $validTypes = ['offline', 'online', 'hybrid'];
        if ($typeFilter && ! in_array($typeFilter, $validTypes, true)) {
            $this->error('Tipe rapat tidak valid. Pilihan: ' . implode(', ', $validTypes));

            return 1;
        }

        // Pastikan master data unit dan pegawai ASN telah tersedia
        if (Unit::count() === 0) {
            $this->info('Master Unit kosong. Menjalankan UnitSeeder...');
            $this->call(UnitSeeder::class);
        }

        if (User::count() === 0) {
            $this->info('Master Pegawai kosong. Menjalankan UserSeeder...');
            $this->call(UserSeeder::class);
        }

        if ($this->option('clean')) {
            $this->warn('Membersihkan data agenda lama...');
            DB::table('attendances')->delete();
            DB::table('agenda_units')->delete();
            DB::table('agenda_documentations')->delete();
            DB::table('agendas')->delete();
            $this->info('Data agenda dan presensi berhasil dibersihkan.');
        }

        $this->info("=== Memulai Pembuatan {$count} Agenda Rapat Kedinasan SIPERAPAT ===");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $attendeesOpt = $this->option('attendees');
        $generated = [
            'completed' => 0,
            'ongoing' => 0,
            'scheduled' => 0,
            'draft' => 0,
            'cancelled' => 0,
        ];
        $totalAttendances = 0;

        for ($i = 0; $i < $count; $i++) {
            // Tentukan status jika tidak dibatasi oleh --status
            if ($statusFilter) {
                $status = $statusFilter;
            } else {
                // Distribusi realistis: 50% completed, 15% ongoing, 25% scheduled, 5% draft, 5% cancelled
                $rand = mt_rand(1, 100);
                $status = match (true) {
                    $rand <= 50 => 'completed',
                    $rand <= 65 => 'ongoing',
                    $rand <= 90 => 'scheduled',
                    $rand <= 95 => 'draft',
                    default => 'cancelled',
                };
            }

            $factory = Agenda::factory();

            // Terapkan status
            $factory = match ($status) {
                'completed' => $factory->completed(mt_rand(1, 30))->withNotulen(),
                'ongoing' => $factory->ongoing(mt_rand(15, 60)),
                'scheduled' => $factory->scheduled(mt_rand(1, 14)),
                'draft' => $factory->draft(),
                'cancelled' => $factory->cancelled(),
            };

            // Terapkan tipe jika dispesifikasikan
            if ($typeFilter) {
                $factory = match ($typeFilter) {
                    'offline' => $factory->offline(),
                    'online' => $factory->online(),
                    'hybrid' => $factory->hybrid(),
                };
            }

            // Tentukan cakupan unit: 40% seluruh unit, 60% unit spesifik
            if (mt_rand(1, 100) <= 40) {
                $factory = $factory->universal();
            } else {
                $randomUnits = Unit::query()->inRandomOrder()->take(mt_rand(1, 3))->get();
                $factory = $factory->forUnits($randomUnits->all());
            }

            // Tentukan jumlah presensi
            $numAttendees = 0;
            if (in_array($status, ['completed', 'ongoing'], true)) {
                if (is_numeric($attendeesOpt)) {
                    $numAttendees = max(0, (int) $attendeesOpt);
                } elseif ($attendeesOpt === 'auto') {
                    $numAttendees = mt_rand(5, 20);
                }
            }

            if ($numAttendees > 0) {
                $factory = $factory->withAttendances($numAttendees)->withDocumentation(mt_rand(1, 3));
            }

            $agenda = $factory->create();
            $generated[$status]++;
            $totalAttendances += $agenda->attendances()->count();

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✓ Sukses: Berhasil membuat {$count} data agenda rapat kedinasan.");
        $this->table(
            ['Status Agenda', 'Jumlah'],
            collect($generated)->map(fn ($val, $key) => [strtoupper($key), $val])->values()->all()
        );
        $this->line("Total Rekaman Presensi yang Ditanamkan: <comment>{$totalAttendances}</comment> presensi.");

        return 0;
    }
}
