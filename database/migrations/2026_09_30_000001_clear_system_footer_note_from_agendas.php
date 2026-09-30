<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Mengosongkan catatan kaki yang masih memuat kalimat bawaan sistem.
 *
 * Catatan kaki pernah diisi otomatis dengan kalimat resmi SIPERAPAT. Nilai itu
 * tersimpan di report_config setiap agenda, jadi mengubah nilai bawaan di kode
 * saja tidak membuffer apa pun: baris lama tetap membawa kalimat itu dan
 * tetap tercetak.
 *
 * Hanya kecocokan persis dengan kalimat sistem yang dikosongkan. Catatan yang
 * benar-benar ditulis petugas tidak boleh tersentuh, dan sakelarnya
 * dimatikan sekaligus supaya tidak tertinggal garis pemisah tanpa teks.
 */
return new class extends Migration
{
    /** Kalimat yang pernah disisipkan otomatis oleh sistem. */
    private const SYSTEM_BOILERPLATE =
        'Dokumen ini diterbitkan secara resmi melalui Sistem Informasi Presensi Rapat (SIPERAPAT) LLDIKTI Wilayah X';

    public function up(): void
    {
        $cleared = 0;

        foreach (DB::table('agendas')->whereNotNull('report_config')->cursor() as $row) {
            $config = json_decode((string) $row->report_config, true);

            if (! is_array($config)) {
                continue;
            }

            if (trim((string) ($config['footer_note'] ?? '')) !== self::SYSTEM_BOILERPLATE) {
                continue;
            }

            $config['footer_note'] = '';
            $config['show_footer_note'] = false;

            DB::table('agendas')
                ->where('id', $row->id)
                ->update(['report_config' => json_encode($config)]);

            $cleared++;
        }

        if ($cleared > 0) {
            $this->info("Catatan kaki sistem dikosongkan pada {$cleared} agenda.");
        }
    }

    public function down(): void
    {
        // Dikembalikan ke kalimat semula hanya bila sakelarnya memang masih
        // dalam keadaan bawaan, yaitu tidak dinyalakan dan catatannya kosong.
        $restored = 0;

        foreach (DB::table('agendas')->whereNotNull('report_config')->cursor() as $row) {
            $config = json_decode((string) $row->report_config, true);

            if (! is_array($config)) {
                continue;
            }

            if (($config['show_footer_note'] ?? true) !== false
                || trim((string) ($config['footer_note'] ?? '')) !== '') {
                continue;
            }

            $config['footer_note'] = self::SYSTEM_BOILERPLATE;
            $config['show_footer_note'] = true;

            DB::table('agendas')
                ->where('id', $row->id)
                ->update(['report_config' => json_encode($config)]);

            $restored++;
        }

        if ($restored > 0) {
            $this->info("Catatan kaki sistem dipulihkan pada {$restored} agenda.");
        }
    }
};
