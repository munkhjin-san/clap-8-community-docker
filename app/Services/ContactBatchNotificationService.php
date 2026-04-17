<?php

namespace App\Services;

use App\Jobs\SendNotification;
use App\Models\ContactBatch;
use App\Models\ContactBatchNotification;
use Illuminate\Support\Str;

class ContactBatchNotificationService
{
    public function notifyIfNeeded(ContactBatch $batch): bool
    {
        if (!$batch->user_id) {
            return false;
        }

        if (!in_array($batch->status, [ContactBatch::STATUS_COMPLETED, ContactBatch::STATUS_FAILED], true)) {
            return false;
        }

        $notification = ContactBatchNotification::firstOrCreate(
            [
                'user_id' => $batch->user_id,
                'contact_batch_id' => $batch->id,
                'status' => $batch->status,
            ],
            [
                'title' => $batch->status === ContactBatch::STATUS_COMPLETED
                    ? '名刺の取り込みが完了しました'
                    : '名刺の取り込みを完了できませんでした',
                'message' => $batch->status === ContactBatch::STATUS_COMPLETED
                    ? 'コンタクト画面で取り込み結果をご確認ください。'
                    : Str::limit((string) ($batch->error ?: 'コンタクト画面でエラー内容をご確認ください。'), 200),
                'url' => url('/contact'),
            ]
        );

        if ($notification->wasRecentlyCreated && !$notification->pushed_at) {
            SendNotification::dispatch([
                'title' => $notification->title,
                'body' => $notification->message,
                'link' => $notification->url ?: url('/contact'),
                'members' => [(string) $batch->user_id],
                'icon' => url('/notification-favicon.png'),
                'badge' => url('/notification-favicon.png'),
            ]);

            $notification->forceFill([
                'pushed_at' => now(),
            ])->save();
        }

        return $notification->wasRecentlyCreated;
    }
}
