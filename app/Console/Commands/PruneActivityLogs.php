<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DriveActivityLog;
use App\Models\DriveDownloadLog; 
class PruneActivityLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:prune-activity-logs {--days=90}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete old activity logs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cutoff = now()->subDays((int)$this->option('days'));
        $deleted = DriveDownloadLog::where('created_at', '<', $cutoff)->delete();
        $this->info("Deleted {$deleted} rows older than {$cutoff}");
        return self::SUCCESS;
    }
}
