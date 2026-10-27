<?php

use App\Console\Commands\PruneActivityLogs;
use App\Console\Commands\SyncAgendaStatus;
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
    ->withoutOverlapping(60)
    ->description('Prune activity logs beyond the two-year retention window');

/*
 * Otomatisasi penutupan sesi presensi rapat kedinasan yang telah melewati batas akhir waktu pelaksanaan.
 * Berjalan setiap 1 menit dengan locking pencegah tumpang tindih (timeout 10 menit anti-stale lock).
 */
Schedule::command(SyncAgendaStatus::class)
    ->everyMinute()
    ->withoutOverlapping(10)
    ->description('Otomatisasi penutupan sesi presensi rapat kedinasan yang telah melewati batas waktu pelaksanaan');


