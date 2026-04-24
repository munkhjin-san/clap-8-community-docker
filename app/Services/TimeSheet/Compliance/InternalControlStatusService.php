<?php

namespace App\Services\TimeSheet\Compliance;

use App\Models\AuditDailyDigest;
use App\Models\TimecardAuditEventProjection;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class InternalControlStatusService
{
    private array $sealedDates = [];

    public function statusForAuditRecord(?string $filePath, ?string $fileSha256, CarbonInterface|string|null $occurredAt): ?string
    {
        if (!$filePath && !$fileSha256) {
            return null;
        }

        $date = $this->normalizeDate($occurredAt);
        if ($date === null) {
            return 'recorded';
        }

        return $this->hasDigestForDate($date) ? 'sealed' : 'recorded';
    }

    public function sealRecordedEvidenceForDate(string $date): int
    {
        $this->sealedDates[$date] = true;

        return TimecardAuditEventProjection::query()
            ->whereDate('occurred_at', $date)
            ->where(function ($query) {
                $query->whereNotNull('file_path')
                    ->orWhereNotNull('file_sha256');
            })
            ->update(['internal_control_status' => 'sealed']);
    }

    private function hasDigestForDate(string $date): bool
    {
        if (array_key_exists($date, $this->sealedDates)) {
            return $this->sealedDates[$date];
        }

        return $this->sealedDates[$date] = AuditDailyDigest::query()
            ->whereDate('digest_date', $date)
            ->exists();
    }

    private function normalizeDate(CarbonInterface|string|null $occurredAt): ?string
    {
        if ($occurredAt instanceof CarbonInterface) {
            return $occurredAt->toDateString();
        }

        if ($occurredAt === null || $occurredAt === '') {
            return null;
        }

        try {
            return Carbon::parse($occurredAt)->toDateString();
        } catch (\Throwable $exception) {
            return null;
        }
    }
}