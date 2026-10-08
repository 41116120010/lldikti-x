<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Enterprise PostgreSQL Sequence Synchronizer.
 *
 * Di PostgreSQL, ketika data di-seed atau di-restore dengan ID eksplisit
 * (seperti backup SQL lama, migrasi antar DBMS, atau manual insert),
 * sequence internal (BIGSERIAL) TIDAK otomatis maju ke nilai MAX(id).
 *
 * Command ini secara otomatis mengidentifikasi sequence resmi tiap tabel
 * via pg_get_serial_sequence() dan menyelaraskannya ke COALESCE(MAX(id), 1)
 * untuk mencegah error `duplicate key value violates unique constraint "..._pkey"`.
 */
class SyncPostgresSequences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pgsql:sync-sequences
                            {--table= : Sinkronkan hanya tabel tertentu}
                            {--dry-run : Tampilkan nilai sequence tanpa mengubah database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronkan nilai sequence primary key (BIGSERIAL) di PostgreSQL dengan nilai MAX(id) tabel';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $driver = DB::connection()->getDriverName();

        if ($driver !== 'pgsql') {
            $this->warn("Driver basis data saat ini adalah '{$driver}'. Sinkronisasi sequence hanya relevan untuk PostgreSQL.");

            return self::SUCCESS;
        }

        $specificTable = $this->option('table');
        $dryRun = (bool) $this->option('dry-run');

        $tables = $specificTable ? [(string) $specificTable] : [
            'users',
            'units',
            'agendas',
            'attendances',
            'agenda_units',
            'agenda_documentations',
            'activity_logs',
            'personal_access_tokens',
            'failed_jobs',
            'jobs',
        ];

        $this->info($dryRun ? '=== SIMULASI SINKRONISASI SEQUENCE POSTGRESQL (DRY-RUN) ===' : '=== SINKRONISASI SEQUENCE POSTGRESQL ===');

        $syncedCount = 0;
        $skippedCount = 0;

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                $this->line(" - Tabel <fg=yellow>{$table}</> tidak ditemukan, dilewati.");
                $skippedCount++;
                continue;
            }

            // Dapatkan nama sequence yang terasosiasi dengan kolom 'id'
            $seqRow = DB::selectOne("SELECT pg_get_serial_sequence(?, 'id') AS seq", [$table]);
            $sequenceName = $seqRow?->seq;

            if (! $sequenceName) {
                $this->line(" - Tabel <fg=yellow>{$table}</> tidak memiliki sequence terasosiasi pada kolom 'id', dilewati.");
                $skippedCount++;
                continue;
            }

            $maxId = DB::table($table)->max('id');

            // Ambil status sequence saat ini
            $currentValRow = DB::selectOne("SELECT last_value, is_called FROM {$sequenceName}");
            $currentVal = $currentValRow?->last_value ?? 0;

            if ($maxId === null) {
                $targetVal = 1;
                $targetIsCalled = 'false';
                $actionDesc = "Set ke 1 (tabel kosong, is_called = false)";
            } else {
                $targetVal = (int) $maxId;
                $targetIsCalled = 'true';
                $actionDesc = "Set ke {$targetVal} (MAX id = {$maxId}, is_called = true)";
            }

            if ($dryRun) {
                $this->line(" [DRY-RUN] Tabel <fg=cyan>{$table}</> (Sequence: <fg=gray>{$sequenceName}</>): Saat ini = {$currentVal} -> Target = {$targetVal} ({$actionDesc})");
            } else {
                DB::statement(
                    "SELECT setval(?::regclass, ?::bigint, ?::boolean)",
                    [$sequenceName, $targetVal, $targetIsCalled]
                );
                $this->info(" [OK] Tabel {$table} -> Sequence {$sequenceName} berhasil disinkronkan ke {$targetVal}.");
            }

            $syncedCount++;
        }

        $this->newLine();
        $this->info("Selesai: {$syncedCount} sequence berhasil diproses, {$skippedCount} dilewati.");

        return self::SUCCESS;
    }
}
