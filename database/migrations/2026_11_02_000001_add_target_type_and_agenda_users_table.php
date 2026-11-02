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
        // 1. Add target_type column to agendas table
        Schema::table('agendas', function (Blueprint $table) {
            $table->string('target_type', 30)->default('all_units')->after('is_all_units')->index();
        });

        // 2. Synchronize existing agenda records
        DB::table('agendas')->where('is_all_units', true)->update(['target_type' => 'all_units']);
        DB::table('agendas')->where('is_all_units', false)->update(['target_type' => 'selected_units']);

        // 3. Create pivot table agenda_users for specific ASN participants
        Schema::create('agenda_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_id')->constrained('agendas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['agenda_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agenda_users');

        Schema::table('agendas', function (Blueprint $table) {
            $table->dropColumn('target_type');
        });
    }
};
