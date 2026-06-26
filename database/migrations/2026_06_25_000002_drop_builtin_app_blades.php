<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dashboard and Chat (board) are built-in apps available to everyone, so they
 * are no longer permission blades. This strips the deprecated `app.dashboard`
 * and `app.board` keys from every role's blade list, leaving any other
 * (possibly customized) blades untouched.
 */
return new class extends Migration
{
    private const DEPRECATED = ['app.dashboard', 'app.board'];

    public function up(): void
    {
        if (!Schema::hasTable('community_roles')) {
            return;
        }

        foreach (DB::table('community_roles')->get(['id', 'capabilities']) as $role) {
            $blades = json_decode($role->capabilities ?? '[]', true) ?: [];
            $cleaned = array_values(array_diff($blades, self::DEPRECATED));

            if ($cleaned !== $blades) {
                DB::table('community_roles')->where('id', $role->id)->update([
                    'capabilities' => json_encode($cleaned, JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // No-op: the keys are deprecated and not restored.
    }
};
