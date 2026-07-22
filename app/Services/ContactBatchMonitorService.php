<?php

namespace App\Services;

use App\Jobs\Concerns\HandlesContactBatch;
use App\Models\ContactBatch;
use App\Models\ContactBatchItem;

class ContactBatchMonitorService
{
    use HandlesContactBatch;

    public function refresh(ContactBatch $batch): ContactBatch
    {
        $batch->loadMissing('items.contactRecord.collaborators');

        if (in_array($batch->status, [ContactBatch::STATUS_COMPLETED, ContactBatch::STATUS_FAILED], true)) {
            return $batch;
        }

        $apiKey = config('services.google.gemini_api_key');
        if (empty($apiKey)) {
            $this->markBatchFailed($batch, 'Gemini API key is not configured.');

            return $batch->fresh('items.contactRecord.collaborators');
        }

        return match ($batch->status) {
            ContactBatch::STATUS_SCANNING => $this->refreshScanStage($batch, $apiKey),
            ContactBatch::STATUS_ENRICHING => $this->refreshEnrichmentStage($batch, $apiKey),
            default => $batch->fresh('items.contactRecord.collaborators'),
        };
    }

    protected function refreshScanStage(ContactBatch $batch, string $apiKey): ContactBatch
    {
        if (!$batch->scan_operation) {
            return $batch->fresh('items.contactRecord.collaborators');
        }

        $payload = $this->pollOperationPayload(
            $batch,
            $apiKey,
            $batch->scan_operation,
            'Gemini batch status could not be retrieved anymore.',
            'Gemini batch did not complete successfully.'
        );

        if ($payload === null) {
            return $batch->fresh('items.contactRecord.collaborators');
        }

        $this->applyScanResults($batch, $payload);

        $batch->update([
            'scan_completed_at' => $batch->scan_completed_at ?? now(),
            'error' => null,
        ]);

        $batch = $batch->fresh('items.contactRecord.collaborators');
        $scannedItems = $batch->items->where('status', ContactBatchItem::STATUS_SCANNED)->values();

        if ($scannedItems->isEmpty()) {
            $this->markBatchFailed($batch, '名刺画像から連絡先情報を抽出できませんでした。');

            return $batch->fresh('items.contactRecord.collaborators');
        }

        $requests = $this->buildEnrichmentRequests($batch);
        if (empty($requests)) {
            return $this->completeWithoutEnrichment($batch);
        }

        try {
            $operation = $this->startGeminiBatch($batch, $apiKey, 'models/gemini-3.6-flash', [
                'batch' => [
                    'displayName' => 'contact-enrich-' . now()->format('YmdHis'),
                    'model' => 'models/gemini-3.6-flash',
                    'inputConfig' => [
                        'requests' => [
                            'requests' => $requests,
                        ],
                    ],
                ],
            ]);
        } catch (\Throwable $exception) {
            $this->logEntry($batch, 'enrich_submit_fallback', 'Falling back to OCR-only completion.', [
                'message' => $exception->getMessage(),
            ], 'models/gemini-3.6-flash');

            return $this->completeWithoutEnrichment($batch);
        }

        $batch->update([
            'status' => ContactBatch::STATUS_ENRICHING,
            'enrich_operation' => $operation['name'] ?? null,
            'enrich_attempts' => 0,
            'enrich_requested_at' => now(),
            'enrich_completed_at' => null,
            'error' => null,
        ]);

        return $batch->fresh('items.contactRecord.collaborators');
    }

    protected function refreshEnrichmentStage(ContactBatch $batch, string $apiKey): ContactBatch
    {
        if (!$batch->enrich_operation) {
            return $this->completeWithoutEnrichment($batch);
        }

        try {
            $payload = $this->pollGeminiOperation($apiKey, $batch->enrich_operation);
        } catch (\Throwable $exception) {
            $this->logEntry($batch, 'enrich_poll_fallback', 'Company research poll failed; using OCR-only completion.', [
                'message' => $exception->getMessage(),
            ]);

            return $this->completeWithoutEnrichment($batch);
        }

        $state = data_get($payload, 'metadata.state');

        if (in_array($state, ['BATCH_STATE_FAILED', 'BATCH_STATE_CANCELLED', 'BATCH_STATE_EXPIRED'], true)) {
            $this->logEntry($batch, 'enrich_state_fallback', 'Company research did not finish successfully; using OCR-only completion.', [
                'state' => $state,
                'error' => data_get($payload, 'error.message'),
            ]);

            return $this->completeWithoutEnrichment($batch);
        }

        if ($state !== 'BATCH_STATE_SUCCEEDED') {
            return $batch->fresh('items.contactRecord.collaborators');
        }

        $this->applyEnrichmentResults($batch, $payload);
        $batch = $batch->fresh('items.contactRecord.collaborators');

        if ($batch->items->where('status', ContactBatchItem::STATUS_COMPLETED)->count() === 0) {
            $this->markBatchFailed($batch, '会社情報の取得まで完了できませんでした。');

            return $batch->fresh('items.contactRecord.collaborators');
        }

        $batch->update([
            'status' => ContactBatch::STATUS_COMPLETED,
            'enrich_completed_at' => $batch->enrich_completed_at ?? now(),
            'error' => null,
        ]);

        $this->cleanupBatchDirectory($batch);

        return $batch->fresh('items.contactRecord.collaborators');
    }

    protected function completeWithoutEnrichment(ContactBatch $batch): ContactBatch
    {
        $items = $batch->items()->where('status', ContactBatchItem::STATUS_SCANNED)->get();

        foreach ($items as $item) {
            $scanData = data_get($item->scan_result, 'parsed.0', []);
            $contactRecord = $this->storeContactRecord($batch, $item, is_array($scanData) ? $scanData : [], '');

            $item->update([
                'contact_record_id' => $contactRecord?->id,
                'status' => ContactBatchItem::STATUS_COMPLETED,
                'error' => null,
                'enrich_result' => null,
            ]);
        }

        $batch = $batch->fresh('items.contactRecord.collaborators');

        if ($batch->items->where('status', ContactBatchItem::STATUS_COMPLETED)->count() === 0) {
            $this->markBatchFailed($batch, '名刺画像から連絡先情報を抽出できませんでした。');

            return $batch->fresh('items.contactRecord.collaborators');
        }

        $batch->update([
            'status' => ContactBatch::STATUS_COMPLETED,
            'enrich_operation' => null,
            'enrich_requested_at' => null,
            'enrich_completed_at' => null,
            'error' => null,
        ]);

        $this->cleanupBatchDirectory($batch);

        return $batch->fresh('items.contactRecord.collaborators');
    }

    protected function pollOperationPayload(
        ContactBatch $batch,
        string $apiKey,
        string $operationName,
        string $pollFailureMessage,
        string $stateFailureMessage,
    ): ?array {
        try {
            $payload = $this->pollGeminiOperation($apiKey, $operationName);
        } catch (\Throwable $exception) {
            $this->markBatchFailed($batch, $pollFailureMessage);

            return null;
        }

        $state = data_get($payload, 'metadata.state');

        if (in_array($state, ['BATCH_STATE_FAILED', 'BATCH_STATE_CANCELLED', 'BATCH_STATE_EXPIRED'], true)) {
            $this->markBatchFailed(
                $batch,
                (string) data_get($payload, 'error.message', $stateFailureMessage)
            );

            return null;
        }

        if ($state !== 'BATCH_STATE_SUCCEEDED') {
            return null;
        }

        return $payload;
    }
}
