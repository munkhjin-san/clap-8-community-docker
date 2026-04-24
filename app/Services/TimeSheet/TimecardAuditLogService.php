<?php

namespace App\Services\TimeSheet;

use App\Models\TimecardAuditEvent;
use App\Models\TimecardAuditEventProjection;
use App\Models\TimecardCostOcrRun;
use App\Models\TimecardReceiptFile;
use App\Models\timecardCostRecord;
use App\Models\timecardRecord;
use App\Services\TimeSheet\Compliance\AuditHashService;
use App\Services\TimeSheet\Compliance\InternalControlStatusService;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class TimecardAuditLogService
{
    public function __construct(
        private readonly AuditHashService $auditHashService,
        private readonly InternalControlStatusService $internalControlStatusService
    )
    {
    }

    public function log(array $attributes): TimecardAuditEvent
    {
        $payload = array_merge([
            'actor_user_id' => auth()->id(),
            'request_id' => $this->requestId(),
            'client_ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'occurred_at' => now(),
        ], $attributes);
        $previousHash = TimecardAuditEvent::query()
            ->whereNotNull('event_hash')
            ->latest('id')
            ->value('event_hash');
        $payload = array_merge($payload, $this->auditHashService->hashesForPayload($payload, $previousHash));

        $event = TimecardAuditEvent::create($payload);
        $this->syncProjectionForEvent($event);

        return $event;
    }

    public function logTimecardEvent(string $eventType, timecardRecord $timecard, int $subjectUserId, ?array $beforeState = null, ?array $afterState = null, array $metadata = []): TimecardAuditEvent
    {
        return $this->log([
            'timecard_record_id' => $timecard->id,
            'draft_uuid' => null,
            'target_type' => 'timecard',
            'event_type' => $eventType,
            'subject_user_id' => $subjectUserId,
            'before_state' => $beforeState,
            'after_state' => $afterState,
            'metadata' => array_merge([
                'timecard_day' => $timecard->day,
                'approval_state' => $timecard->status_flag,
            ], $metadata) ?: null,
        ]);
    }

    public function logCostEvent(string $eventType, timecardRecord $timecard, ?timecardCostRecord $cost, int $subjectUserId, ?array $beforeState = null, ?array $afterState = null, array $metadata = []): TimecardAuditEvent
    {
        return $this->log([
            'timecard_record_id' => $timecard->id,
            'timecard_cost_record_id' => $cost?->id,
            'draft_uuid' => $cost?->draft_uuid ?? Arr::get($afterState, 'draft_uuid') ?? Arr::get($beforeState, 'draft_uuid'),
            'target_type' => 'cost',
            'event_type' => $eventType,
            'subject_user_id' => $subjectUserId,
            'before_state' => $beforeState,
            'after_state' => $afterState,
            'metadata' => array_merge([
                'timecard_day' => $timecard->day,
                'approval_state' => $timecard->status_flag,
            ], $metadata) ?: null,
        ]);
    }

    public function logReceiptEvent(string $eventType, int $subjectUserId, ?string $draftUuid, ?timecardRecord $timecard = null, ?timecardCostRecord $cost = null, ?array $beforeState = null, ?array $afterState = null, array $metadata = []): TimecardAuditEvent
    {
        return $this->log([
            'timecard_record_id' => $timecard?->id,
            'timecard_cost_record_id' => $cost?->id,
            'draft_uuid' => $draftUuid,
            'target_type' => 'receipt_file',
            'event_type' => $eventType,
            'subject_user_id' => $subjectUserId,
            'before_state' => $beforeState,
            'after_state' => $afterState,
            'metadata' => array_merge(array_filter([
                'timecard_day' => $timecard?->day,
                'approval_state' => $timecard?->status_flag,
            ], fn ($value) => $value !== null), $metadata) ?: null,
        ]);
    }

    public function logOcrEvent(string $eventType, TimecardCostOcrRun $ocrRun, int $subjectUserId, ?timecardRecord $timecard = null, ?timecardCostRecord $cost = null, ?array $beforeState = null, ?array $afterState = null, array $metadata = []): TimecardAuditEvent
    {
        return $this->log([
            'timecard_record_id' => $timecard?->id ?? $ocrRun->timecard_record_id,
            'timecard_cost_record_id' => $cost?->id ?? $ocrRun->timecard_cost_record_id,
            'draft_uuid' => $ocrRun->draft_uuid,
            'target_type' => 'ocr_run',
            'event_type' => $eventType,
            'subject_user_id' => $subjectUserId,
            'before_state' => $beforeState,
            'after_state' => $afterState,
            'metadata' => array_merge([
                'ocr_run_id' => $ocrRun->id,
                'provider' => $ocrRun->provider,
                'model' => $ocrRun->model,
                'status' => $ocrRun->status,
                'timecard_day' => $timecard?->day,
                'approval_state' => $timecard?->status_flag,
            ], $metadata),
        ]);
    }

    public function trackedCostState(array|timecardCostRecord|null $source): ?array
    {
        if ($source === null) {
            return null;
        }

        $data = $source instanceof timecardCostRecord ? $source->toArray() : $source;

        return [
            'id' => Arr::get($data, 'id'),
            'draft_uuid' => Arr::get($data, 'draft_uuid'),
            'type' => Arr::get($data, 'type'),
            'transport_type' => Arr::get($data, 'transport_type'),
            'departure_place' => Arr::get($data, 'departure_place'),
            'arrival_place' => Arr::get($data, 'arrival_place'),
            'department' => Arr::get($data, 'department'),
            'content' => Arr::get($data, 'content'),
            'expenses' => Arr::get($data, 'expenses'),
            'merchant_name' => Arr::get($data, 'merchant_name'),
            'receipt_date' => $this->normalizeDateOnly(Arr::get($data, 'receipt_date')),
            'currency' => Arr::get($data, 'currency'),
            'receipt_source_type' => Arr::get($data, 'receipt_source_type'),
            'receipt_file_id' => Arr::get($data, 'receipt_file_id'),
            'file_path' => Arr::get($data, 'file_path'),
            'file_sha256' => Arr::get($data, 'file_sha256'),
            'scan_dpi' => Arr::get($data, 'scan_dpi'),
            'scan_color_depth' => Arr::get($data, 'scan_color_depth'),
            'scan_color_mode' => Arr::get($data, 'scan_color_mode'),
            'document_size' => Arr::get($data, 'document_size'),
            'image_width_px' => Arr::get($data, 'image_width_px'),
            'image_height_px' => Arr::get($data, 'image_height_px'),
        ];
    }

    public function trackedTimecardState(array|timecardRecord|null $source): ?array
    {
        if ($source === null) {
            return null;
        }

        $data = $source instanceof timecardRecord ? $source->toArray() : $source;

        return [
            'id' => Arr::get($data, 'id'),
            'day' => Arr::get($data, 'day'),
            'status_flag' => Arr::get($data, 'status_flag'),
            'approved_by' => Arr::get($data, 'approved_by'),
            'work_group_id' => Arr::get($data, 'work_group_id'),
            'start_time' => Arr::get($data, 'start_time'),
            'end_time' => Arr::get($data, 'end_time'),
        ];
    }

    public function syncProjectionForEvent(TimecardAuditEvent $event): TimecardAuditEventProjection
    {
        $event->loadMissing([
            'timecard:id,day,status_flag',
            'timecardCost:id,record_id,merchant_name,receipt_date,expenses,currency,department,file_path,receipt_file_id,file_sha256',
            'timecardCost.receiptFile:id,sha256,canonical_path',
        ]);

        $projectionAttributes = $this->projectionAttributes($event);

        return TimecardAuditEventProjection::updateOrCreate(
            ['timecard_audit_event_id' => $event->id],
            $projectionAttributes
        );
    }

    private function projectionAttributes(TimecardAuditEvent $event): array
    {
        $beforeState = $event->before_state ?? [];
        $afterState = $event->after_state ?? [];
        $metadata = $event->metadata ?? [];
        $cost = $event->timecardCost;
        $timecard = $event->timecard;
        $receiptFileId = Arr::get($afterState, 'receipt_file_id')
            ?? Arr::get($beforeState, 'receipt_file_id')
            ?? Arr::get($metadata, 'receipt_file_id')
            ?? $cost?->receipt_file_id;
        $receiptFile = $receiptFileId ? TimecardReceiptFile::find($receiptFileId) : $cost?->receiptFile;
        $filePath = Arr::get($afterState, 'file_path')
            ?? Arr::get($beforeState, 'file_path')
            ?? Arr::get($metadata, 'file_path')
            ?? $cost?->file_path;
        $fileSha256 = Arr::get($afterState, 'file_sha256')
            ?? Arr::get($beforeState, 'file_sha256')
            ?? Arr::get($metadata, 'file_sha256')
            ?? $receiptFile?->sha256
            ?? $cost?->file_sha256;
        $internalControlStatus = $this->internalControlStatusService->statusForAuditRecord(
            $filePath,
            $fileSha256,
            $event->occurred_at,
        );

        return [
            'timecard_record_id' => $event->timecard_record_id,
            'timecard_cost_record_id' => $event->timecard_cost_record_id,
            'draft_uuid' => $event->draft_uuid,
            'target_type' => $event->target_type,
            'event_type' => $event->event_type,
            'actor_user_id' => $event->actor_user_id,
            'subject_user_id' => $event->subject_user_id,
            'occurred_at' => $event->occurred_at,
            'timecard_day' => Arr::get($afterState, 'day')
                ?? Arr::get($beforeState, 'day')
                ?? Arr::get($metadata, 'timecard_day')
                ?? $timecard?->day,
            'approval_state' => Arr::get($afterState, 'status_flag')
                ?? Arr::get($beforeState, 'status_flag')
                ?? Arr::get($metadata, 'approval_state')
                ?? $timecard?->status_flag,
            'merchant_name' => Arr::get($afterState, 'merchant_name')
                ?? Arr::get($beforeState, 'merchant_name')
                ?? $cost?->merchant_name,
            'receipt_date' => $this->normalizeDateOnly(
                Arr::get($afterState, 'receipt_date')
                ?? Arr::get($beforeState, 'receipt_date')
                ?? $this->normalizeDateOnly($cost?->receipt_date),
            ),
            'expenses' => Arr::get($afterState, 'expenses')
                ?? Arr::get($beforeState, 'expenses')
                ?? $cost?->expenses,
            'currency' => Arr::get($afterState, 'currency')
                ?? Arr::get($beforeState, 'currency')
                ?? $cost?->currency,
            'department' => Arr::get($afterState, 'department')
                ?? Arr::get($beforeState, 'department')
                ?? $cost?->department,
            'file_path' => $filePath,
            'receipt_file_id' => $receiptFileId,
            'file_sha256' => $fileSha256,
            'internal_control_status' => $internalControlStatus,
            'ocr_run_id' => Arr::get($metadata, 'ocr_run_id'),
        ];
    }

    private function normalizeDateOnly(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return $raw;
        }

        if (preg_match('/(\d{4}-\d{2}-\d{2})/', $raw, $matches)) {
            return $matches[1];
        }

        try {
            return Carbon::parse($raw)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function requestId(): string
    {
        $request = request();
        if (!$request) {
            return (string) Str::uuid();
        }

        $existing = $request->attributes->get('timecard_audit_request_id') ?: $request->header('X-Request-Id');
        if ($existing) {
            $request->attributes->set('timecard_audit_request_id', $existing);

            return (string) $existing;
        }

        $generated = (string) Str::uuid();
        $request->attributes->set('timecard_audit_request_id', $generated);

        return $generated;
    }
}
