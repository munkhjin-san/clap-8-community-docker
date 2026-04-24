<?php

namespace App\Services\TimeSheet\Compliance;

use App\Models\TimecardAuditEvent;
use Illuminate\Support\Arr;

class AuditHashService
{
    public function hashesForPayload(array $payload, ?string $previousHash): array
    {
        $canonicalPayload = $this->canonicalize([
            'previous_event_hash' => $previousHash,
            'timecard_record_id' => Arr::get($payload, 'timecard_record_id'),
            'timecard_cost_record_id' => Arr::get($payload, 'timecard_cost_record_id'),
            'draft_uuid' => Arr::get($payload, 'draft_uuid'),
            'target_type' => Arr::get($payload, 'target_type'),
            'event_type' => Arr::get($payload, 'event_type'),
            'actor_user_id' => Arr::get($payload, 'actor_user_id'),
            'subject_user_id' => Arr::get($payload, 'subject_user_id'),
            'request_id' => Arr::get($payload, 'request_id'),
            'client_ip' => Arr::get($payload, 'client_ip'),
            'user_agent' => Arr::get($payload, 'user_agent'),
            'before_state' => Arr::get($payload, 'before_state'),
            'after_state' => Arr::get($payload, 'after_state'),
            'metadata' => Arr::get($payload, 'metadata'),
            'occurred_at' => $this->dateValue(Arr::get($payload, 'occurred_at')),
        ]);

        $payloadHash = hash('sha256', $canonicalPayload);

        return [
            'previous_event_hash' => $previousHash,
            'payload_hash' => $payloadHash,
            'event_hash' => hash('sha256', ($previousHash ?? '') . $payloadHash),
        ];
    }

    public function hashesForEvent(TimecardAuditEvent $event, ?string $previousHash): array
    {
        return $this->hashesForPayload($event->only([
            'timecard_record_id',
            'timecard_cost_record_id',
            'draft_uuid',
            'target_type',
            'event_type',
            'actor_user_id',
            'subject_user_id',
            'request_id',
            'client_ip',
            'user_agent',
            'before_state',
            'after_state',
            'metadata',
            'occurred_at',
        ]), $previousHash);
    }

    public function canonicalize(mixed $value): string
    {
        return json_encode(
            $this->sortValue($value),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION
        );
    }

    private function sortValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (!is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn ($item) => $this->sortValue($item), $value);
        }

        ksort($value);

        return array_map(fn ($item) => $this->sortValue($item), $value);
    }

    private function dateValue(mixed $value): mixed
    {
        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : $value;
    }
}
