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
        if (! Schema::hasIndex('agendas', 'agendas_status_waktu_mulai_index')) {
            Schema::table('agendas', function (Blueprint $table) {
                $table->index(['status', 'waktu_mulai'], 'agendas_status_waktu_mulai_index');
            });
        }

        if (! Schema::hasIndex('agendas', 'agendas_is_all_units_status_index')) {
            Schema::table('agendas', function (Blueprint $table) {
                $table->index(['is_all_units', 'status'], 'agendas_is_all_units_status_index');
            });
        }

        if (! Schema::hasIndex('users', 'users_is_active_unit_id_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index(['is_active', 'unit_id'], 'users_is_active_unit_id_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasIndex('agendas', 'agendas_status_waktu_mulai_index')) {
            Schema::table('agendas', function (Blueprint $table) {
                $table->dropIndex('agendas_status_waktu_mulai_index');
            });
        }

        if (Schema::hasIndex('agendas', 'agendas_is_all_units_status_index')) {
            Schema::table('agendas', function (Blueprint $table) {
                $table->dropIndex('agendas_is_all_units_status_index');
            });
        }

        if (Schema::hasIndex('users', 'users_is_active_unit_id_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_is_active_unit_id_index');
            });
        }
    }
};
