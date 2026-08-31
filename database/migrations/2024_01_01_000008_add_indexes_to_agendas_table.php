<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->index('status', 'agendas_status_index');
            $table->index('waktu_mulai', 'agendas_waktu_mulai_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->dropIndex('agendas_status_index');
            $table->dropIndex('agendas_waktu_mulai_index');
        });
    }
};
