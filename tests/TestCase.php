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
