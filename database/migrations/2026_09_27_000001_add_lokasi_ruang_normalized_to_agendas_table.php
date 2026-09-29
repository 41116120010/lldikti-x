<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a normalised copy of lokasi_ruang so room-conflict lookups become sargable.
 *
 * The conflict check previously matched rooms with LOWER(TRIM(lokasi_ruang)) = ?.
 * Wrapping the column in a function makes every index on it unusable, so the check
 * degraded into a full scan of `agendas` on every save. Storing the normalised
 * value at write time turns the predicate into a plain equality that the composite
 * index below can satisfy.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotent: an earlier run may have added the column before failing on
        // the backfill, and MySQL does not roll back DDL.
        if (! Schema::hasColumn('agendas', 'lokasi_ruang_normalized')) {
            Schema::table('agendas', function (Blueprint $table) {
                $table->string('lokasi_ruang_normalized', 150)->nullable()->after('lokasi_ruang');
            });
        }

        /*
         * Backfill as a single UPDATE rather than a chunked upsert.
         *
         * The first attempt used chunkById + upsert, which cannot work here:
         * MySQL compiles upsert as INSERT ... ON DUPLICATE KEY UPDATE, and that
         * statement must supply a value for every column lacking a default.
         * `created_by` is NOT NULL with no default, so the backfill aborted part-way
         * with "Field 'created_by' doesn't have a default value".
         *
         * Normalising in SQL also makes this one statement rather than 500
         * round-trips, and LEFT / LOWER / TRIM / NULLIF are portable across
         * MySQL, MariaDB and PostgreSQL. The expression mirrors
         * Agenda::normalizeRoom() on purpose — a migration must keep reproducing
         * the same result after the application code changes.
         */
        DB::table('agendas')
            ->whereNotNull('lokasi_ruang')
            ->update([
                'lokasi_ruang_normalized' => DB::raw("LEFT(LOWER(NULLIF(TRIM(lokasi_ruang), '')), 150)"),
            ]);

        // A duplicate-key error means a previous run already created it, which is
        // exactly the situation this guard exists for.
        try {
            Schema::table('agendas', function (Blueprint $table) {
                $table->index(
                    ['lokasi_ruang_normalized', 'status'],
                    'agendas_ruang_normalized_status_index'
                );
            });
        } catch (\Throwable) {
            // Index already present.
        }
    }

    public function down(): void
    {
        try {
            Schema::table('agendas', function (Blueprint $table) {
                $table->dropIndex('agendas_ruang_normalized_status_index');
            });
        } catch (\Throwable) {
            // Index was never created.
        }

        if (Schema::hasColumn('agendas', 'lokasi_ruang_normalized')) {
            Schema::table('agendas', function (Blueprint $table) {
                $table->dropColumn('lokasi_ruang_normalized');
            });
        }
    }
};
