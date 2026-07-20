<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The Flow (アプリ / カスタムアプリ) app became a toggleable capability `app.flow`.
 * It was previously gated by isAdmin||isBoss, so existing admin + board roles must
 * keep it — but they were seeded before the capability existed. Backfill it into
 * those roles' capabilities so switching the frontend gate to can('app.flow')
 * preserves their access. Other roles are unaffected (admins can toggle it on).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('community_roles')) {
            return;
        }

        $roles = DB::table('community_roles')
            ->whereIn('key', ['admin', 'board'])
            ->get(['id', 'capabilities']);

        foreach ($roles as $role) {
            $capabilities = json_decode($role->capabilities ?? '[]', true);
            if (!is_array($capabilities)) {
                $capabilities = [];
            }
            if (in_array('app.flow', $capabilities, true)) {
                continue;
            }
            $capabilities[] = 'app.flow';
            DB::table('community_roles')
                ->where('id', $role->id)
                ->update(['capabilities' => json_encode(array_values($capabilities))]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('community_roles')) {
            return;
        }

        $roles = DB::table('community_roles')
            ->whereIn('key', ['admin', 'board'])
            ->get(['id', 'capabilities']);

        foreach ($roles as $role) {
            $capabilities = json_decode($role->capabilities ?? '[]', true);
            if (!is_array($capabilities)) {
                continue;
            }
            $filtered = array_values(array_filter($capabilities, fn ($cap) => $cap !== 'app.flow'));
            if ($filtered === $capabilities) {
                continue;
            }
            DB::table('community_roles')
                ->where('id', $role->id)
                ->update(['capabilities' => json_encode($filtered)]);
        }
    }
};
