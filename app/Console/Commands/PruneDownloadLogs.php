<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DriveDownloadLog;

class PruneDownloadLogs extends Command
{
    protected $signature = 'logs:prune-downloads {--days=180}';
    protected $description = 'Delete old download logs';

    public function handle(): int
    {
        $cutoff = now()->subDays((int)$this->option('days'));
        $deleted = DriveDownloadLog::where('started_at', '<', $cutoff)->delete();
        $this->info("Deleted {$deleted} rows older than {$cutoff}");
        return self::SUCCESS;
    }
}
