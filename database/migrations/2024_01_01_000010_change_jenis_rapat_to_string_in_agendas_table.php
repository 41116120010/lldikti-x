<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE agendas DROP CONSTRAINT IF EXISTS agendas_jenis_rapat_check');
        }

        Schema::table('agendas', function (Blueprint $table) {
            $table->string('jenis_rapat', 100)->default('Rapat Koordinasi')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * The data is folded back onto the legacy keys first. Without that step the
     * ALTER fails: the current default is 'Rapat Koordinasi', which is not a
     * member of the ENUM being restored, so MySQL rejects the statement with
     * ER_DATA_TRUNCATED and the rollback cannot complete. Rows holding a value
     * outside the legacy set (the column is free text) are mapped to the closest
     * legacy key rather than aborting the rollback.
     */
    public function down(): void
    {
        $map = config('agenda.jenis_rapat_legacy_map', [
            'Rapat Koordinasi' => 'koordinasi',
            'Rapat Pleno' => 'pleno',
            'Rapat Evaluasi & Monev' => 'evaluasi',
            'Konsinyasi / FGD' => 'konsinyasi',
            'Rapat Terbatas / Pimpinan' => 'terbatas',
            'Sosialisasi / Bimtek' => 'lainnya',
            'Workshop / Lokakarya' => 'lainnya',
            'Pertemuan Lainnya' => 'lainnya',
        ]);

        foreach ($map as $current => $legacy) {
            DB::table('agendas')
                ->where('jenis_rapat', $current)
                ->update(['jenis_rapat' => $legacy]);
        }

        // Anything still outside the ENUM (free text) collapses to 'lainnya'.
        DB::table('agendas')
            ->whereNotIn('jenis_rapat', array_values($map))
            ->update(['jenis_rapat' => 'lainnya']);

        Schema::table('agendas', function (Blueprint $table) {
            $table->enum('jenis_rapat', ['koordinasi', 'pleno', 'evaluasi', 'konsinyasi', 'terbatas', 'lainnya'])->default('koordinasi')->change();
        });
    }
};
