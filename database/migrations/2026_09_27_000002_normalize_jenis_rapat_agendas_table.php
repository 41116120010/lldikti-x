<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Normalises legacy jenis_rapat values onto the current display convention.
 *
 * The column was an ENUM of lowercase keys until 2024_01_01_000010 widened it to
 * VARCHAR. Rows written before that still hold the lowercase keys, so one official
 * report could read "Koordinasi" for a 2023 row and "Rapat Koordinasi" for a 2026
 * one — two words for the same meeting type, which matters when the report is the
 * archival record of a government proceeding.
 *
 * The mapping lives in config/agenda.php so the seeder, the form and this
 * migration all agree on the vocabulary. Migrations intentionally read the config
 * rather than importing app code: a migration must keep reproducing the same
 * result even after the application changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        $map = config('agenda.jenis_rapat_legacy_map', []);

        foreach ($map as $legacy => $current) {
            DB::table('agendas')
                ->where('jenis_rapat', $legacy)
                ->update(['jenis_rapat' => $current]);
        }
    }

    public function down(): void
    {
        $map = config('agenda.jenis_rapat_legacy_map', []);

        // Reverse mapping is only safe where the target is unambiguous. A value
        // that was already in its current form (or was never a legacy key) is left
        // alone rather than being forced back into the ENUM.
        foreach ($map as $legacy => $current) {
            DB::table('agendas')
                ->where('jenis_rapat', $current)
                ->update(['jenis_rapat' => $legacy]);
        }
    }
};
