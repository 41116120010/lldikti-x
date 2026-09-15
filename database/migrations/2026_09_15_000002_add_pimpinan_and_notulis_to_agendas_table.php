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
            $table->foreignId('pimpinan_id')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('notulis_id')
                ->nullable()
                ->after('pimpinan_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index('pimpinan_id');
            $table->index('notulis_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->dropForeign(['pimpinan_id']);
            $table->dropForeign(['notulis_id']);
            $table->dropColumn(['pimpinan_id', 'notulis_id']);
        });
    }
};
