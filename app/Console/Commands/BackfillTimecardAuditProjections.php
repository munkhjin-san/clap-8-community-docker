<?php

namespace App\Console\Commands;

use App\Models\TimecardAuditEvent;
use App\Services\TimeSheet\TimecardAuditLogService;
use Illuminate\Console\Command;

class BackfillTimecardAuditProjections extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backfill-timecard-audit-projections';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill flattened projection rows for existing timecard audit events';

    /**
     * Execute the console command.
     */
    public function handle(TimecardAuditLogService $timecardAuditLogService)
    {
        $count = 0;

        TimecardAuditEvent::query()
            ->with([
                'timecard:id,day,status_flag',
                'timecardCost:id,record_id,merchant_name,receipt_date,expenses,currency,department,file_path',
            ])
            ->orderBy('id')
            ->chunkById(200, function ($events) use ($timecardAuditLogService, &$count) {
                foreach ($events as $event) {
                    $timecardAuditLogService->syncProjectionForEvent($event);
                    $count++;
                }
            });

        $this->info("Backfilled {$count} timecard audit projections.");

        return self::SUCCESS;
    }
}
