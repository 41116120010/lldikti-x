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
        Schema::table('agendas', function (Blueprint $table) {
            $table->dateTime('waktu_selesai')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * Rows for an open-ended meeting ("hingga selesai") hold NULL here, and
     * restoring NOT NULL over them fails with ER_BAD_NULL_ERROR. Each one is
     * first given an end time derived from its start so the rollback can finish
     * instead of leaving the schema half-reverted.
     */
    public function down(): void
    {
        /*
         * Mirroring the start time keeps this driver-agnostic — the equivalent
         * "+ INTERVAL 1 HOUR" is spelled differently in MySQL and PostgreSQL, and
         * a zero-length window is an acceptable degraded state for a rollback
         * that is already destructive.
         */
        DB::table('agendas')
            ->whereNull('waktu_selesai')
            ->update(['waktu_selesai' => DB::raw('waktu_mulai')]);

        Schema::table('agendas', function (Blueprint $table) {
            $table->dateTime('waktu_selesai')->nullable(false)->change();
        });
    }
};
