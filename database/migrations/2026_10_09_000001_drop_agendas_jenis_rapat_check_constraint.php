<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * In PostgreSQL, when an enum column is altered to varchar via ->change(),
     * the underlying check constraint (agendas_jenis_rapat_check) is not dropped
     * automatically by Laravel / Doctrine DBAL. This migration explicitly drops
     * the residual check constraint so values like 'Rapat Koordinasi' can be saved.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE agendas DROP CONSTRAINT IF EXISTS agendas_jenis_rapat_check');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: kolom jenis_rapat sudah menjadi string bebas (free-form VARCHAR)
    }
};
