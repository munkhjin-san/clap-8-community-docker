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

        if (!$batch->scan_operation) {
            return $batch;
        }

        try {
            $payload = $this->pollGeminiOperation($apiKey, $batch->scan_operation);
        } catch (\Throwable $exception) {
            $this->markBatchFailed($batch, 'Gemini batch status could not be retrieved anymore.');

            return $batch->fresh('items.contactRecord.collaborators');
        }

        $state = data_get($payload, 'metadata.state');

        if (in_array($state, ['BATCH_STATE_FAILED', 'BATCH_STATE_CANCELLED', 'BATCH_STATE_EXPIRED'], true)) {
            $this->markBatchFailed(
                $batch,
                (string) data_get($payload, 'error.message', 'Gemini batch did not complete successfully.')
            );

            return $batch->fresh('items.contactRecord.collaborators');
        }

        if ($state !== 'BATCH_STATE_SUCCEEDED') {
            return $batch->fresh('items.contactRecord.collaborators');
        }

        $items = $batch->items()->get()->keyBy('id');
        $inlineResponses = data_get($payload, 'response.inlinedResponses.inlinedResponses', []);

        foreach ($inlineResponses as $inline) {
            $itemId = data_get($inline, 'metadata.batch_item_id');
            if (!$itemId || !$items->has($itemId)) {
                continue;
            }

            /** @var ContactBatchItem $item */
            $item = $items->get($itemId);

            if (isset($inline['error'])) {
                $message = (string) data_get($inline, 'error.message', 'Unknown error during scan stage.');
                $item->update([
                    'status' => ContactBatchItem::STATUS_FAILED,
                    'error' => $message,
                ]);
                continue;
            }

            $rawText = data_get($inline, 'response.candidates.0.content.parts.0.text');

            if (!$rawText) {
                $item->update([
                    'status' => ContactBatchItem::STATUS_FAILED,
                    'error' => 'Scan response did not include text content.',
                ]);
                continue;
            }

            $parsed = $this->decodeJsonText($rawText);

            if ($parsed === null) {
                $item->update([
                    'status' => ContactBatchItem::STATUS_FAILED,
                    'error' => 'Scan response JSON could not be decoded.',
                    'scan_result' => [
                        'raw_text' => $rawText,
                    ],
                ]);
                continue;
            }

            $scanData = $this->normalizeParsedContacts($parsed)[0] ?? [];
            $contactRecord = $this->storeContactRecord($batch, $item, $scanData, $rawText);

            $item->update([
                'status' => ContactBatchItem::STATUS_COMPLETED,
                'error' => null,
                'scan_result' => [
                    'raw_text' => $rawText,
                    'parsed' => $this->normalizeParsedContacts($parsed),
                ],
                'contact_record_id' => $contactRecord?->id,
            ]);
        }

        $items->each(function (ContactBatchItem $item) {
            if ($item->status === ContactBatchItem::STATUS_SCANNING) {
                $item->update([
                    'status' => ContactBatchItem::STATUS_FAILED,
                    'error' => 'Scan result was not returned for this item.',
                ]);
            }
        });

        $batch->update([
            'status' => ContactBatch::STATUS_COMPLETED,
            'scan_completed_at' => $batch->scan_completed_at ?? now(),
            'error' => null,
        ]);

        $this->cleanupBatchDirectory($batch);

        return $batch->fresh('items.contactRecord.collaborators');
    }
}
