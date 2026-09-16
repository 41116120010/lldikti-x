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
            if (!Schema::hasColumn('agendas', 'pimpinan_id')) {
                $table->foreignId('pimpinan_id')
                    ->nullable()
                    ->after('created_by')
                    ->constrained('users')
                    ->nullOnDelete();

                $table->index('pimpinan_id');
            }

            if (!Schema::hasColumn('agendas', 'notulis_id')) {
                $table->foreignId('notulis_id')
                    ->nullable()
                    ->after('pimpinan_id')
                    ->constrained('users')
                    ->nullOnDelete();

                $table->index('notulis_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            if (Schema::hasColumn('agendas', 'pimpinan_id')) {
                $table->dropForeign(['pimpinan_id']);
                $table->dropColumn('pimpinan_id');
            }

            if (Schema::hasColumn('agendas', 'notulis_id')) {
                $table->dropForeign(['notulis_id']);
                $table->dropColumn('notulis_id');
            }
        });
    }
};
