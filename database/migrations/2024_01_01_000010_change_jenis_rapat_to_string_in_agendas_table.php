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
            $table->string('jenis_rapat', 100)->default('Rapat Koordinasi')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->enum('jenis_rapat', ['koordinasi', 'pleno', 'evaluasi', 'konsinyasi', 'terbatas', 'lainnya'])->default('koordinasi')->change();
        });
    }
};
