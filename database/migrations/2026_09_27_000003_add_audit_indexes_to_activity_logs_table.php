<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index coverage for the activity log's audit and filter paths.
 *
 *  - (target_model, target_id) is how an investigator follows one entity's
 *    history, and target_id had no index at all.
 *  - (user_id, created_at) replaces the standalone created_at index: the
 *    per-user log page and the retention prune both filter on those two together,
 *    so the composite serves both while still supporting a bare created_at range
 *    scan through its leading column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index(['target_model', 'target_id'], 'activity_logs_target_index');
            $table->index(['user_id', 'created_at'], 'activity_logs_user_created_index');
            // Lets the filter dropdown's SELECT DISTINCT activity_type use a loose
            // index scan instead of scanning the whole table.
            $table->index('activity_type', 'activity_logs_type_index');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('activity_logs_target_index');
            $table->dropIndex('activity_logs_user_created_index');
            $table->dropIndex('activity_logs_type_index');
        });
    }
};
