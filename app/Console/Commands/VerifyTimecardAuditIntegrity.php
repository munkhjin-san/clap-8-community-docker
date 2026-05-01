<?php

namespace App\Console\Commands;

use App\Models\AuditDailyDigest;
use App\Models\TimecardAuditEvent;
use App\Services\TimeSheet\Compliance\AuditHashService;
use Illuminate\Console\Command;

class VerifyTimecardAuditIntegrity extends Command
{
    protected $signature = 'app:verify-timecard-audit-integrity
        {--date= : Verify one YYYY-MM-DD digest date}
        {--repair : Recalculate the full audit hash chain before verifying}
        {--require-digest : Fail date verification when the daily digest is missing}';

    protected $description = 'Verify timecard audit hash chain and optional daily digest';

    public function handle(AuditHashService $auditHashService): int
    {
        if ($this->option('repair')) {
            $this->repairHashChain($auditHashService);
        }

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
                $message = "Audit hash mismatch at event {$event->id}.";
                $this->error($message);
                $this->appendCommandLog($message);
                return self::FAILURE;
            }

            $previousHash = $event->event_hash;
            $hashes[] = $event->event_hash;
        }

        if ($date) {
            $digest = AuditDailyDigest::whereDate('digest_date', $date)->first();
            if (!$digest && $this->option('require-digest')) {
                $totalEvents = TimecardAuditEvent::query()
                    ->whereDate('occurred_at', $date)
                    ->count();
                $hashedEvents = TimecardAuditEvent::query()
                    ->whereDate('occurred_at', $date)
                    ->whereNotNull('event_hash')
                    ->count();
                if ($totalEvents === 0) {
                    $message = "No audit events found for {$date}; daily digest is not required.";
                    $this->info($message);
                    $this->appendCommandLog($message);

                    return self::SUCCESS;
                }

                $message = "Daily digest missing for {$date}. total_events={$totalEvents}, hashed_events={$hashedEvents}.";
                $this->error($message);
                $this->appendCommandLog($message);
                return self::FAILURE;
            }

            if ($digest) {
                $digestHash = hash('sha256', implode('', $hashes));
                if ($digest->event_count !== count($hashes) || $digest->digest_hash !== $digestHash) {
                    $message = "Daily digest mismatch for {$date}. digest_event_count={$digest->event_count}, actual_event_count=".count($hashes).".";
                    $this->error($message);
                    $this->appendCommandLog($message);
                    return self::FAILURE;
                }
            }
        }

        $message = 'Timecard audit integrity verified.';
        $this->info($message);
        $this->appendCommandLog($message);

        return self::SUCCESS;
    }

    private function repairHashChain(AuditHashService $auditHashService): void
    {
        $previousHash = null;
        $count = 0;

        TimecardAuditEvent::query()
            ->orderBy('id')
            ->chunkById(200, function ($events) use (&$previousHash, &$count, $auditHashService): void {
                foreach ($events as $event) {
                    $event->forceFill($auditHashService->hashesForEvent($event, $previousHash))->save();
                    $previousHash = $event->event_hash;
                    $count++;
                }
            });

        $this->info("Repaired {$count} audit hashes before verification.");
    }

    private function appendCommandLog(string $message): void
    {
        $path = storage_path('logs/timecard-audit-integrity.log');
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, '['.now()->toDateTimeString()."] {$message}\n", FILE_APPEND);
    }
}
