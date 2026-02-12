<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\PushSubscription;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use App\Models\User;
use Throwable;
class SendNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * Backoff seconds for retries.
     *
     * @var array<int>
     */
    public array $backoff = [10, 60, 300];

    public int $timeout = 30;

    protected $payload;
    /**
     * Create a new job instance.
     */
    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $members = $this->payload['members'] ?? [];
        if (empty($members)) return;

        $auth = [
            'VAPID' => [
                'subject' => config('services.VAPID.subject'),
                'publicKey' => config('services.VAPID.public_key'),
                'privateKey' => config('services.VAPID.private_key'),
            ],
        ];

        try {
            $webPush = new WebPush($auth);
            $webPush->setDefaultOptions([
                'TTL' => 120, // adjust if you want
            ]);

            $users = User::whereIn('id', $members)
                ->with('pushSubscriptions')
                ->get();

            $payload = [
                "title" => (string)($this->payload['title'] ?? ''),
                "body" => (string)($this->payload['body'] ?? ''),
                "icon" => $this->payload['icon'] ?? null,
                "hide_notification_if_site_has_focus" => true,
                "badge" => $this->payload['badge'] ?? null,
                "data" => [
                    "url" => (string)($this->payload['link'] ?? ''),
                ]
            ];
            $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);

            $queued = 0;

            foreach ($users as $user) {
                foreach ($user->pushSubscriptions as $ps) {
                    // sanity check: skip obviously broken rows
                    if (empty($ps->endpoint) || empty($ps->p256dh) || empty($ps->auth)) {
                        Log::warning('Push subscription missing fields', [
                            'user_id' => $user->id,
                            'endpoint_present' => !empty($ps->endpoint),
                            'p256dh_len' => strlen((string)($ps->p256dh ?? '')),
                            'auth_len' => strlen((string)($ps->auth ?? '')),
                        ]);
                        continue;
                    }

                    $subscription = Subscription::create([
                        'endpoint' => $ps->endpoint,
                        'publicKey' => $ps->p256dh,
                        'authToken' => $ps->auth,
                    ]);

                    $webPush->queueNotification($subscription, $payloadJson, [
                        // helps you map reports back to DB rows
                        'user_id' => $user->id,
                        'push_subscription_id' => $ps->id ?? null,
                    ]);

                    $queued++;
                }
            }

            $results = [];
            foreach ($webPush->flush() as $report) {
                $reqUri = (string) $report->getRequest()->getUri();

                $status = null;
                $response = $report->getResponse();
                if ($response) {
                    try {
                        $status = $response->getStatusCode();
                    } catch (\Throwable $ignore) {}
                }

                $success = $report->isSuccess();
                $reason = $success ? null : $report->getReason();

                $results[] = [
                    'endpoint' => $reqUri,
                    'success' => $success,
                    'status'  => $status,
                    'reason'  => $reason,
                ];

                // Delete expired/unsubscribed endpoints
                if (!$success && in_array($status, [404, 410], true)) {
                    // If you have a PushSubscription model, delete by endpoint
                    PushSubscription::where('endpoint', $reqUri)->delete();

                    // If $ps is from a package, adapt accordingly. Endpoint is the safest join key.
                }
            }

            Log::info('SendNotification published', [
                'members_count' => count($members),
                'members' => $members,
                'queued' => $queued,
                'results' => $results,
                // Useful for debugging mismatched config across servers:
                'vapid_public_preview' => substr((string)config('services.VAPID.public_key'), 0, 12)
                    . '...' .
                    substr((string)config('services.VAPID.public_key'), -12),
            ]);

        } catch (Throwable $e) {
            Log::error('SendNotification failed', [
                'members_count' => count($members),
                'title' => $this->payload['title'] ?? null,
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
            throw $e;
        }
    }
}
