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

Artisan::command('mail:test {email : Alamat email tujuan pengujian Brevo}', function (string $email) {
    $this->info('=== Diagnostik Pengiriman Email SIPERAPAT (Brevo) ===');
    $this->line('Config Cached   : ' . (app()->configurationIsCached() ? 'YA (bootstrap/cache/config.php aktif)' : 'TIDAK (membaca langsung .env)'));
    $defaultMailer = (string) config('mail.default');
    $this->line('Default Mailer  : ' . $defaultMailer);
    $this->line('From Address    : ' . config('mail.from.address') . ' (' . config('mail.from.name') . ')');

    if ($defaultMailer === 'smtp') {
        $host = (string) config('mail.mailers.smtp.host');
        $port = (int) config('mail.mailers.smtp.port');
        $scheme = (string) config('mail.mailers.smtp.scheme');
        $this->line("SMTP Host       : {$host}:{$port} (scheme: {$scheme})");
        $this->line('SMTP Username   : ' . (config('mail.mailers.smtp.username') ?: '(kosong)'));

        $socketTarget = ($scheme === 'smtps' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $this->line("Socket Probe    : Menghubungi {$socketTarget} (timeout 5s)...");
        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client($socketTarget, $errno, $errstr, 5, STREAM_CLIENT_CONNECT);
        if ($socket) {
            fclose($socket);
            $this->info("✓ Pre-flight: Berhasil membuka koneksi socket ke [{$socketTarget}].");
        } else {
            $this->warn("⚠ Pre-flight: Gagal membuka koneksi socket ke [{$socketTarget}]: {$errstr} (Error {$errno})");
            if ($port === 587) {
                $this->warn('TIPS: Port 587 sering diblokir firewall provider hosting/VPS. Ubah MAIL_PORT=465 dan MAIL_ENCRYPTION=ssl di .env!');
            }
        }
    } elseif ($defaultMailer === 'brevo') {
        $apiKey = (string) config('mail.mailers.brevo.api_key');
        $maskedKey = strlen($apiKey) > 12 ? substr($apiKey, 0, 10) . '...' . substr($apiKey, -4) : '(belum diatur)';
        $this->line('Brevo API Mode  : HTTPS Port 443 (https://api.brevo.com/v3/smtp/email)');
        $this->line('Brevo API Key   : ' . $maskedKey);
    } elseif (in_array($defaultMailer, ['log', 'array'], true)) {
        $this->warn("PERINGATAN: Default mailer saat ini adalah '{$defaultMailer}'! Email hanya ditulis ke log lokal dan TIDAK dikirim ke Brevo.");
        $this->warn('Jika Anda baru saja mengubah .env, jalankan: php artisan config:clear && php artisan config:cache');
    }

    try {
        \Illuminate\Support\Facades\Mail::raw(
            "Ini adalah email uji coba diagnostik dari server SIPERAPAT LLDIKTI Wilayah X.\nWaktu Server: " . now()->toDateTimeString() . " WIB\nMailer Aktif: {$defaultMailer}",
            function ($message) use ($email) {
                $message->to($email)->subject('[SIPERAPAT] Uji Koneksi Email Kedinasan (Brevo)');
            }
        );
        $this->info("✓ BERHASIL: Perintah pengiriman email ke [{$email}] sukses dieksekusi menggunakan driver [{$defaultMailer}].");

        return 0;
    } catch (\Throwable $e) {
        $this->error('✗ GAGAL MENGIRIM EMAIL: ' . $e->getMessage());

        return 1;
    }
})->purpose('Uji coba dan diagnosa konfigurasi pengiriman email Brevo (SMTP / HTTP API)');
