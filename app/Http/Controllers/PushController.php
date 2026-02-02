<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PushSubscription;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use App\Models\User;

class PushController extends Controller
{
    public function subscribe(Request $request)
    {
        $user = $request->user();
        $sub = $request->all();

        if (empty($sub['endpoint']) || empty($sub['keys']['p256dh']) || empty($sub['keys']['auth'])) {
            return response()->json(['message' => 'Invalid subscription'], 422);
        }

        PushSubscription::updateOrCreate(
            ['endpoint' => $sub['endpoint']],
            [
                'user_id' => $user->id,
                'p256dh' => $sub['keys']['p256dh'],
                'auth' => $sub['keys']['auth'],
            ]
        );

        return response()->json(['ok' => true]);
    }

    public function test(Request $request)
    {
        $user = $request->user();

        $auth = [
            'VAPID' => [
                'subject' => config('services.VAPID.subject'),
                'publicKey' => config('services.VAPID.public_key'),
                'privateKey' => config('services.VAPID.private_key'),
            ],
        ];

        $webPush = new WebPush($auth);
        $test_user = User::find(604);

        foreach ($test_user->pushSubscriptions as $ps) {
            $subscription = Subscription::create([
                'endpoint' => $ps->endpoint,
                'publicKey' => $ps->p256dh,
                'authToken' => $ps->auth,
            ]);

            $payload = [
                'title' => 'MISO Test',
                'body' => 'If you see this, push works even when closed.',
                'data' => ['url' => '/'],
            ];

            $webPush->queueNotification($subscription, json_encode($payload));
        }

        $results = [];
        foreach ($webPush->flush() as $report) {
            $results[] = [
                'endpoint' => (string) $report->getRequest()->getUri(),
                'success' => $report->isSuccess(),
                'reason' => $report->isSuccess() ? null : $report->getReason(),
            ];

            // if (!$report->isSuccess()) {
            //     PushSubscription::where('endpoint', (string) $report->getRequest()->getUri())->delete();
            // }
        }

        return response()->json($results);
    }
}
