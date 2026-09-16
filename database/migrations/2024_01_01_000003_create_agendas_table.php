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
        Schema::create('agendas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('judul_rapat', 255);
            $table->string('slug', 255)->unique();
            $table->enum('jenis_rapat', ['koordinasi', 'pleno', 'evaluasi', 'konsinyasi', 'terbatas', 'lainnya'])->default('koordinasi');
            $table->enum('tipe_rapat', ['offline', 'online', 'hybrid'])->default('offline');
            $table->string('lokasi_ruang', 150)->nullable();
            $table->text('link_meeting')->nullable();
            $table->dateTime('waktu_mulai');
            $table->dateTime('waktu_selesai');
            $table->boolean('is_all_units')->default(true);
            $table->string('surat_edaran_path')->nullable();
            $table->longText('notulensi')->nullable();
            $table->longText('kesimpulan')->nullable();
            $table->enum('status', ['draft', 'scheduled', 'ongoing', 'completed', 'cancelled'])->default('scheduled');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agendas');
    }
};
