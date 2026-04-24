<?php

namespace App\Console\Commands;

use App\Models\AuditDailyDigest;
use App\Models\TimecardAuditEvent;
use App\Services\TimeSheet\Compliance\AuditHashService;
use Illuminate\Console\Command;

class VerifyTimecardAuditIntegrity extends Command
{
    protected $signature = 'app:verify-timecard-audit-integrity {--date= : Verify one YYYY-MM-DD digest date}';

    protected $description = 'Verify timecard audit hash chain and optional daily digest';

    public function handle(AuditHashService $auditHashService): int
    {
        $date = $this->option('date');
        $query = TimecardAuditEvent::query()->orderBy('id');
        if ($date) {
            $query->whereDate('occurred_at', $date);
        }

        $previousHash = $date
            ? TimecardAuditEvent::query()
                ->whereDate('occurred_at', '<', $date)
                ->whereNotNull('event_hash')
                ->latest('id')
                ->value('event_hash')
            : null;
        $hashes = [];
        foreach ($query->get() as $event) {
            $expected = $auditHashService->hashesForEvent($event, $previousHash);
            if ($event->previous_event_hash !== $expected['previous_event_hash']
                || $event->payload_hash !== $expected['payload_hash']
                || $event->event_hash !== $expected['event_hash']) {
                $this->error("Audit hash mismatch at event {$event->id}.");
                return self::FAILURE;
            }

            $previousHash = $event->event_hash;
            $hashes[] = $event->event_hash;
        }

        if ($date) {
            $digest = AuditDailyDigest::whereDate('digest_date', $date)->first();
            if ($digest) {
                $digestHash = hash('sha256', implode('', $hashes));
                if ($digest->event_count !== count($hashes) || $digest->digest_hash !== $digestHash) {
                    $this->error("Daily digest mismatch for {$date}.");
                    return self::FAILURE;
                }
            }
        }

        $this->info('Timecard audit integrity verified.');

        return self::SUCCESS;
    }
}
