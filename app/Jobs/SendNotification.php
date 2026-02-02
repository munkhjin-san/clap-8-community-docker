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
       

        $auth = [
            'VAPID' => [
                'subject' => config('services.VAPID.subject'),
                'publicKey' => config('services.VAPID.public_key'),
                'privateKey' => config('services.VAPID.private_key'),
            ],
        ];
        try {
            $webPush = new WebPush($auth);
            $users = User::whereIn('id', $members)->get();
            foreach ($users as $user) {
                foreach ($user->pushSubscriptions as $ps) {
                    $subscription = Subscription::create([
                        'endpoint' => $ps->endpoint,
                        'publicKey' => $ps->p256dh,
                        'authToken' => $ps->auth,
                    ]);

                    $payload = [
                        "title" => $this->payload['title'] ?? '',
                        "body" => $this->payload['body'] ?? '',
                        "icon" => $this->payload['icon'] ?? null,
                        "hide_notification_if_site_has_focus" => true,
                        "badge" => $this->payload['badge'] ?? null,
                        "data" => [
                            "url" => $this->payload['link'] ?? '',
                        ]
                    ];

                    $webPush->queueNotification($subscription, json_encode($payload));
                }
            }
            $results = [];
            foreach ($webPush->flush() as $report) {
                $results[] = [
                    'endpoint' => (string) $report->getRequest()->getUri(),
                    'success' => $report->isSuccess(),
                    'reason' => $report->isSuccess() ? null : $report->getReason(),
                ];
            }
            Log::info('SendNotification published', [
                'members_count' => count($members),
                'members' => $members,
                'results' => $results,
            ]);
        } catch (Throwable $e) {
            Log::error('SendNotification failed', [
                'members_count' => count($members),
                'title' => $this->payload['title'] ?? null,
                'exception' => $e,
            ]);
            throw $e;
        }
        

        

        return;

    }
}
