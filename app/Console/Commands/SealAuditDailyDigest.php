<?php

namespace App\Console\Commands;

use App\Models\AuditDailyDigest;
use App\Models\TimecardAuditEvent;
use App\Services\TimeSheet\Compliance\InternalControlStatusService;
use Illuminate\Console\Command;

class SealAuditDailyDigest extends Command
{
    protected $signature = 'app:seal-audit-daily-digest
        {date? : Digest date in YYYY-MM-DD format}
        {--all : Seal every date that has hashed audit events}
        {--force : Re-seal an existing date}';

    protected $description = 'Create a daily digest for timecard audit event hashes';

    public function handle(InternalControlStatusService $internalControlStatusService): int
    {
        if ($this->option('all')) {
            $dates = TimecardAuditEvent::query()
                ->whereNotNull('event_hash')
                ->selectRaw('DATE(occurred_at) as digest_date')
                ->groupBy('digest_date')
                ->orderBy('digest_date')
                ->pluck('digest_date');

            foreach ($dates as $date) {
                $this->sealDate((string) $date, $internalControlStatusService);
            }

            return self::SUCCESS;
        }

        $date = $this->argument('date') ?: now()->subDay()->toDateString();
        return $this->sealDate($date, $internalControlStatusService);
    }

    private function sealDate(string $date, InternalControlStatusService $internalControlStatusService): int
    {
        $existing = AuditDailyDigest::whereDate('digest_date', $date)->first();
        if ($existing && !$this->option('force')) {
            $message = "Digest already exists for {$date}.";
            $this->warn($message);
            $this->appendCommandLog($message);
            return self::SUCCESS;
        }

        $events = TimecardAuditEvent::query()
            ->whereDate('occurred_at', $date)
            ->whereNotNull('event_hash')
            ->orderBy('id')
            ->get(['event_hash']);

        if ($events->isEmpty()) {
            $message = "No hashed audit events found for {$date}.";
            $this->warn($message);
            $this->appendCommandLog($message);
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

        $message = "Sealed {$date} with ".count($hashes)." events and updated {$updatedRows} evidence rows.";
        $this->info($message);
        $this->appendCommandLog($message);

        return self::SUCCESS;
    }

    private function appendCommandLog(string $message): void
    {
        $path = storage_path('logs/timecard-audit-seal.log');
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, '['.now()->toDateTimeString()."] {$message}\n", FILE_APPEND);
    }
}
