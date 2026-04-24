<?php

namespace App\Console\Commands;

use App\Models\AuditDailyDigest;
use App\Models\TimecardAuditEvent;
use App\Services\TimeSheet\Compliance\InternalControlStatusService;
use Illuminate\Console\Command;

class SealAuditDailyDigest extends Command
{
    protected $signature = 'app:seal-audit-daily-digest {date? : Digest date in YYYY-MM-DD format} {--force : Re-seal an existing date}';

    protected $description = 'Create a daily digest for timecard audit event hashes';

    public function handle(InternalControlStatusService $internalControlStatusService): int
    {
        $date = $this->argument('date') ?: now()->subDay()->toDateString();
        $existing = AuditDailyDigest::whereDate('digest_date', $date)->first();
        if ($existing && !$this->option('force')) {
            $this->warn("Digest already exists for {$date}.");
            return self::SUCCESS;
        }

        $events = TimecardAuditEvent::query()
            ->whereDate('occurred_at', $date)
            ->whereNotNull('event_hash')
            ->orderBy('id')
            ->get(['event_hash']);

        if ($events->isEmpty()) {
            $this->warn("No hashed audit events found for {$date}.");
            return self::SUCCESS;
        }

        $hashes = $events->pluck('event_hash')->all();
        $digestHash = hash('sha256', implode('', $hashes));

        AuditDailyDigest::updateOrCreate(
            ['digest_date' => $date],
            [
                'first_event_hash' => $hashes[0],
                'last_event_hash' => $hashes[count($hashes) - 1],
                'event_count' => count($hashes),
                'digest_hash' => $digestHash,
                'sealed_at' => now(),
            ]
        );

        $updatedRows = $internalControlStatusService->sealRecordedEvidenceForDate($date);

        $this->info("Sealed {$date} with ".count($hashes)." events and updated {$updatedRows} evidence rows.");

        return self::SUCCESS;
    }
}
