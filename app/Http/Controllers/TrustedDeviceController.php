<?php

namespace App\Http\Controllers;

use App\Models\UserTrustedDevice;
use App\Services\Auth\TrustedDeviceManager;
use Illuminate\Http\Request;

/**
 * Self-service management of "remember this device" entries (Sanctum migration Phase 6).
 * Lets a user see and revoke the browsers that skip their 2FA challenge.
 */
class TrustedDeviceController extends Controller
{
    public function __construct(private TrustedDeviceManager $manager)
    {
    }

    public function index(Request $request)
    {
        $currentHash = $this->manager->currentTokenHash($request);

        $devices = UserTrustedDevice::where('user_id', $request->user()->getAuthIdentifier())
            ->orderByDesc('last_used_at')
            ->get(['id', 'device_name', 'ip_address', 'last_used_at', 'expires_at', 'token_hash'])
            ->map(fn (UserTrustedDevice $d) => [
                'id' => $d->id,
                'device_name' => $d->device_name,
                'ip_address' => $d->ip_address,
                'last_used_at' => $d->last_used_at,
                'expires_at' => $d->expires_at,
                'is_current' => $currentHash !== null && hash_equals($d->token_hash, $currentHash),
            ]);

        return response()->json($devices);
    }

    public function destroy(Request $request, int $id)
    {
        $this->manager->forget($request->user(), $id);

        return response()->json(['deleted' => true]);
    }

    public function destroyAll(Request $request)
    {
        $this->manager->forgetAll($request->user());

        return response()->json(['deleted' => true]);
    }
}
