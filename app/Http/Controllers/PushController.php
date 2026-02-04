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

        // Support both:
        // 1) old format: {endpoint, keys:{p256dh,auth}}
        // 2) new format: {subscription:{...}, vapid_public_hash, origin}
        $payload = $request->all();
        $sub = $payload['subscription'] ?? $payload;

        if (
            empty($sub['endpoint']) ||
            empty($sub['keys']['p256dh']) ||
            empty($sub['keys']['auth'])
        ) {
            return response()->json(['message' => 'Invalid subscription'], 422);
        }

        $clientVapidHash = $payload['vapid_public_hash'] ?? null;
        $clientOrigin = $payload['origin'] ?? $request->getSchemeAndHttpHost();

        // Compute the server's current VAPID public hash (same hash method as client)
        // If you don’t want to compute server-side, you can store it in config/env instead.
        $serverVapidPublic = trim((string) config('services.vapid.public_key')); // adjust to your config
        $serverVapidHash = $serverVapidPublic
            ? base64_encode(hash('sha256', $serverVapidPublic, true))
            : null;

        // If client sent a hash and it doesn't match current server VAPID, tell client to resubscribe
        if ($clientVapidHash && $serverVapidHash && !hash_equals($serverVapidHash, $clientVapidHash)) {
            // Optionally also mark existing record invalid to stop sends
            PushSubscription::where('endpoint', $sub['endpoint'])
                ->update(['invalid_at' => now()]);

            return response()->json([
                'ok' => false,
                'needs_resubscribe' => true,
                'reason' => 'vapid_mismatch',
            ], 200);
        }

        PushSubscription::updateOrCreate(
            ['endpoint' => $sub['endpoint']],
            [
                'user_id' => $user->id,
                'p256dh' => $sub['keys']['p256dh'],
                'auth' => $sub['keys']['auth'],
                'vapid_public_hash' => $clientVapidHash,
                'origin' => $clientOrigin,
                'invalid_at' => null,
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
