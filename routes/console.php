<?php

use App\Console\Commands\PruneActivityLogs;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Retention for the append-only activity log. Runs daily at 02:15 WIB, which is
 * outside the meeting and report windows so a prune never competes with the
 * morning rush of check-ins.
 */
Schedule::command(PruneActivityLogs::class, ['--days' => 730])
    ->dailyAt('02:15')
    ->withoutOverlapping()
    ->onOneServer()
    ->description('Prune activity logs beyond the two-year retention window');
