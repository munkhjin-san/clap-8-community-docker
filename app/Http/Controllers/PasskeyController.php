<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Lists the authenticated user's passkeys for the settings screen (Sanctum migration Phase 8).
 * Registration / deletion / login are handled by laravel/passkeys' own controllers (wired by
 * Fortify); this only fills the gap of a "list my passkeys" endpoint.
 */
class PasskeyController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            $request->user()->passkeys()
                ->orderByDesc('last_used_at')
                ->get(['id', 'name', 'last_used_at', 'created_at'])
        );
    }
}
