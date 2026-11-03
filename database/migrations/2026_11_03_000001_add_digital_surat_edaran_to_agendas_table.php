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
            $table->string('surat_edaran_type', 20)->default('upload')->after('surat_edaran_path');
            $table->longText('surat_edaran_content')->nullable()->after('surat_edaran_type');
            $table->json('surat_edaran_config')->nullable()->after('surat_edaran_content');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->dropColumn(['surat_edaran_type', 'surat_edaran_content', 'surat_edaran_config']);
        });
    }
};
