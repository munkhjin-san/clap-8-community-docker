<?php

namespace App\Console\Commands;

use App\Models\TimecardAuditEvent;
use App\Services\TimeSheet\Compliance\AuditHashService;
use Illuminate\Console\Command;

class BackfillTimecardAuditHashes extends Command
{
    protected $signature = 'app:backfill-timecard-audit-hashes {--force : Recalculate existing hashes}';

    protected $description = 'Backfill hash-chain columns for timecard audit events';

    public function handle(AuditHashService $auditHashService): int
    {
        $previousHash = null;
        $count = 0;
        $force = (bool) $this->option('force');

        TimecardAuditEvent::query()
            ->orderBy('id')
            ->chunkById(200, function ($events) use (&$previousHash, &$count, $force, $auditHashService) {
                foreach ($events as $event) {
                    if (!$force && $event->event_hash) {
                        $previousHash = $event->event_hash;
                        continue;
                    }

                    $event->forceFill($auditHashService->hashesForEvent($event, $previousHash))->save();
                    $previousHash = $event->event_hash;
                    $count++;
                }
            });

        $this->info("Backfilled {$count} audit event hashes.");

        return self::SUCCESS;
    }
}
