<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;

abstract class TestCase extends BaseTestCase
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // The LibreOffice availability probe is cached for several minutes so it
        // does not fork a shell on every request. Inside the suite that cache
        // would leak between tests, so a machine that was probed while the
        // binary was missing would keep reporting it missing for the whole run.
        Cache::forget('siperapat:libreoffice-available');

        $this->warnIfTestsShareTheApplicationDatabase();
        $this->ensureTestAgendaFixture();
    }

    /**
     * Ensure a disposable test agenda fixture exists inside the test transaction
     * if the database has no agendas, guaranteeing test suite independence without
     * requiring dummy agendas in the production/development database.
     */
    protected function ensureTestAgendaFixture(): void
    {
        if (\App\Models\Agenda::count() > 0) {
            return;
        }

        $admin = \App\Models\User::where('role', 'administrator')->first() ?? \App\Models\User::first();
        if (!$admin) {
            return;
        }

        $staff = \App\Models\User::where('role', 'staff')->first() ?? $admin;

        $agenda = \App\Models\Agenda::create([
            'created_by' => $admin->id,
            'judul_rapat' => 'Rapat Koordinasi Evaluasi Pelaporan PDDikti Semester Genap',
            'slug' => 'rakor-evaluasi-pddikti-genap-2026',
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'hybrid',
            'lokasi_ruang' => 'Ruang Sidang Utama Lantai 2, Gedung LLDIKTI',
            'link_meeting' => 'https://zoom.us/j/1234567890?pwd=lldikti-rakor',
            'waktu_mulai' => now()->subHour(),
            'waktu_selesai' => now()->addHours(2),
            'is_all_units' => true,
            'status' => 'ongoing',
            'pimpinan_id' => $admin->id,
            'notulis_id' => $staff->id,
        ]);

        $agenda->report_config = $agenda->getDefaultReportConfig();
        $agenda->save();

        $staffMembers = \App\Models\User::where('role', 'staff')->take(2)->get();
        $staff1 = $staffMembers->first() ?? $admin;
        $staff2 = $staffMembers->skip(1)->first() ?? $staff1;

        \App\Models\Attendance::create([
            'agenda_id' => $agenda->id,
            'user_id' => $admin->id,
            'selfie_path' => 'attendances/1/selfies/selfie_1_lkfmLfAzYBJ0Gtjt.jpg',
            'signature_path' => 'attendances/1/signatures/sig_1_8nvcY3uYgJ91PT85.png',
            'signed_at' => now()->subMinutes(30),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Enterprise SIPERAPAT)',
        ]);

        if ($staff1->id !== $admin->id) {
            \App\Models\Attendance::create([
                'agenda_id' => $agenda->id,
                'user_id' => $staff1->id,
                'selfie_path' => 'attendances/1/selfies/selfie_3_wsLRV1qcTkA7PBmC.jpg',
                'signature_path' => 'attendances/1/signatures/sig_3_G5j7mN0Unn14OQM8.png',
                'signed_at' => now()->subMinutes(25),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Enterprise SIPERAPAT)',
            ]);
        }

        if ($staff2->id !== $admin->id && $staff2->id !== $staff1->id) {
            \App\Models\Attendance::create([
                'agenda_id' => $agenda->id,
                'user_id' => $staff2->id,
                'selfie_path' => 'attendances/1/selfies/selfie_6_PZjFUOK4TRUXWo9X.jpg',
                'signature_path' => 'attendances/1/signatures/sig_6_ZVglWrZg1XrWtR4e.png',
                'signed_at' => now()->subMinutes(20),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Enterprise SIPERAPAT)',
            ]);
        }

        \App\Models\AgendaDocumentation::create([
            'agenda_id' => $agenda->id,
            'file_path' => 'documentations/1/jsObY4pNbzLxPC9joeLqsjn9AcJ5kAbv.jpg',
            'caption' => 'Sesi Pembahasan Evaluasi PDDikti Semester Genap',
            'sort_order' => 1,
        ]);
    }

    /**
     * Warn when the suite is pointed at what looks like a real database.
     *
     * The feature tests run real inserts, updates and deletes inside
     * DatabaseTransactions, so the target must be disposable.
     *
     * This is a WARNING by default, not a hard stop. An earlier revision threw
     * here whenever the database name lacked the substring "test", which broke
     * every Feature test on a normal `php artisan test` run — the failure message
     * was buried under dozens of ✗ lines, and the suite is deliberately
     * seed-coupled, so it needs the application's own seeded database to run at
     * all. A safety net that takes the whole suite down is worse than a loud one.
     *
     * Set SIPERAPAT_STRICT_TEST_DB=1 in .env.testing to turn it into a hard stop
     * once the suite has a dedicated test schema (see AUDIT_CODEBASE.md §2.23).
     */
    private function warnIfTestsShareTheApplicationDatabase(): void
    {
        $connection = config('database.default');

        // Non-persistent drivers (sqlite :memory:) are inherently disposable.
        if (config("database.connections.{$connection}.driver") === 'sqlite') {
            return;
        }

        $name = strtolower((string) config("database.connections.{$connection}.database"));

        if ($name === '' || str_contains($name, 'test') || in_array($name, self::SAFE_DATABASE_NAMES, true)) {
            return;
        }

        $message = sprintf(
            'The test suite is writing to database "%s", which is not named as a test database. '
            .'Any data it creates is rolled back, but a misconfigured environment could still point '
            .'this at production. Point DB_DATABASE at a disposable schema, or set '
            .'SIPERAPAT_STRICT_TEST_DB=1 to make this a hard stop instead of a warning.',
            $name,
        );

        if (filter_var(env('SIPERAPAT_STRICT_TEST_DB', false), FILTER_VALIDATE_BOOLEAN)) {
            throw new \RuntimeException($message);
        }

        fwrite(STDERR, PHP_EOL . '  ⚠ ' . $message . PHP_EOL . PHP_EOL);
    }

    /**
     * Database names that are always safe to write to.
     *
     * @var list<string>
     */
    private const SAFE_DATABASE_NAMES = [
        ':memory:',
        'sqlite',
    ];
}
