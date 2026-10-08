<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PostgresSequenceSyncTest extends TestCase
{
    /**
     * Test pgsql:sync-sequences command executes without crashing.
     */
    public function test_pgsql_sync_sequences_command_runs_safely(): void
    {
        $exitCode = Artisan::call('pgsql:sync-sequences', ['--dry-run' => true]);
        $this->assertSame(0, $exitCode);

        $output = Artisan::output();
        $this->assertNotEmpty($output);
    }

    /**
     * Test pgsql:sync-sequences with specific table option.
     */
    public function test_pgsql_sync_sequences_with_specific_table(): void
    {
        $exitCode = Artisan::call('pgsql:sync-sequences', [
            '--table' => 'users',
            '--dry-run' => true,
        ]);
        $this->assertSame(0, $exitCode);
    }
}
