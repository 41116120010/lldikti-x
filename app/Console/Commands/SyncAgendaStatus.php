<?php

namespace App\Console\Commands;

use App\Models\Agenda;
use App\Services\ActivityLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Otomatisasi penutupan sesi presensi rapat kedinasan yang telah melewati batas akhir waktu pelaksanaan.
 *
 * Sesuai regulasi operasional SIPERAPAT:
 * - Agenda 'ongoing' yang telah melewati waktu selesai efektif (termasuk batas toleransi 15 menit)
 *   ditutup otomatis menjadi 'completed' demi integritas arsip kehadiran dan Berita Acara.
 * - Agenda 'scheduled' yang terlewat TIDAK dibatalkan oleh scheduler; tetap terjadwal dan
 *   ditandai secara dinamis sebagai 'Terlewat' (is_overdue).
 */
class SyncAgendaStatus extends Command
{
    protected $signature = 'agendas:sync-status
                            {--dry-run : Laporkan agenda kedaluwarsa yang akan ditutup tanpa melakukan perubahan data}';

    protected $description = 'Otomatis menutup sesi presensi agenda rapat (ongoing -> completed) yang telah melewati waktu pelaksanaan';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $now = now();

        $ongoingAgendas = Agenda::query()
            ->where('status', 'ongoing')
            ->get();

        $expiredAgendas = $ongoingAgendas->filter(function (Agenda $agenda) use ($now) {
            return $now->gt($agenda->effective_end_at);
        });

        $count = $expiredAgendas->count();

        if ($count === 0) {
            $this->info("Tidak ada agenda rapat 'ongoing' yang kedaluwarsa per {$now->translatedFormat('d F Y, H:i:s')} WIB.");

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->line("Ditemukan {$count} agenda rapat kedaluwarsa yang akan ditutup:");
            foreach ($expiredAgendas as $agenda) {
                $this->line("- [ID: {$agenda->id}] {$agenda->judul_rapat} (Batas Waktu: {$agenda->effective_end_at->format('Y-m-d H:i:s')} WIB)");
            }

            return self::SUCCESS;
        }

        $closedCount = 0;

        foreach ($expiredAgendas as $agenda) {
            DB::transaction(function () use ($agenda, &$closedCount) {
                $effectiveEnd = $agenda->effective_end_at;

                $agenda->update(['status' => 'completed']);

                ActivityLogger::log(
                    type: 'AUTO_CLOSE_AGENDA',
                    description: "Status agenda rapat '{$agenda->judul_rapat}' otomatis ditutup (Selesai) oleh sistem scheduler karena telah melewati batas akhir pelaksanaan.",
                    targetModel: Agenda::class,
                    targetId: $agenda->id,
                    properties: [
                        'previous_status' => 'ongoing',
                        'new_status' => 'completed',
                        'effective_end_at' => $effectiveEnd->toIso8601String(),
                        'closed_at' => now()->toIso8601String(),
                    ]
                );

                $closedCount++;
            });
        }

        $this->info("Berhasil menutup {$closedCount} sesi agenda rapat kedaluwarsa menjadi 'completed'.");

        return self::SUCCESS;
    }
}
