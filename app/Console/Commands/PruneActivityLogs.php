<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;

/**
 * Retention for the activity log.
 *
 * The audit trail is append-only: every login, export, agenda change and
 * attendance check-in inserts a row and nothing ever removed one, so the table
 * grew without bound. That is a storage problem and, for a government archive, a
 * retention-policy problem — records outliving the period they must be kept are a
 * liability as much as missing ones are a risk.
 *
 * Deletion is chunked by primary key so a large backlog never holds a long
 * transaction, and the command reports what it removed so the scheduler's log
 * gives an auditable record of each run.
 */
class PruneActivityLogs extends Command
{
    protected $signature = 'activity-logs:prune
                            {--days=730 : Retain logs newer than this many days}
                            {--dry-run : Report what would be removed without deleting}
                            {--chunk=1000 : Rows deleted per statement}';

    protected $description = 'Delete activity log rows older than the retention window';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $chunk = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');

        if ($days < 1) {
            $this->error('The retention window must be at least one day.');

            return self::INVALID;
        }

        $cutoff = now()->subDays($days);
        $model = (new ActivityLog)->newQuery();
        $model->where('created_at', '<', $cutoff);

        $total = (clone $model)->count();

        if ($total === 0) {
            $this->info("Nothing to prune — every log is newer than {$cutoff->toDateString()}.");

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->line("Would delete {$total} log row(s) older than {$cutoff->toDateString()}.");

            return self::SUCCESS;
        }

        $deleted = 0;

        $model->orderBy('id')
            ->chunkById($chunk, function ($rows) use (&$deleted) {
                // One DELETE per chunk rather than per row.
                $ids = $rows->modelKeys();

                if ($ids !== []) {
                    ActivityLog::whereKey($ids)->delete();
                    $deleted += count($ids);
                }
            });

        $this->info("Pruned {$deleted} activity log row(s) older than {$cutoff->toDateString()}.");

        return self::SUCCESS;
    }
}
