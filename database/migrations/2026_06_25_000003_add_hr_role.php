<?php

use App\Services\Community\CommunityCapabilityCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the hardcoded HR manager (user 631) with the `hr.approve` blade.
 * Creates an 人事 role holding that blade per community, and moves the legacy
 * HR user into it so their HR powers (monthly-goal confirm/view, member
 * assignment, change applications) are preserved.
 */
return new class extends Migration
{
    private const LEGACY_HR_USER_ID = 631;

    public function up(): void
    {
        if (!Schema::hasTable('communities') || !Schema::hasTable('community_roles')) {
            return;
        }

        $hrBlades = CommunityCapabilityCatalog::roleDefaults()['hr'];

        foreach (DB::table('communities')->pluck('id') as $communityId) {
            $hrRoleId = DB::table('community_roles')
                ->where('community_id', $communityId)
                ->where('key', 'hr')
                ->value('id');

            if (!$hrRoleId) {
                $sortOrder = ((int) DB::table('community_roles')->where('community_id', $communityId)->max('sort_order')) + 10;
                $hrRoleId = DB::table('community_roles')->insertGetId([
                    'community_id' => $communityId,
                    'key' => 'hr',
                    'name' => '人事',
                    'sort_order' => $sortOrder,
                    'capabilities' => json_encode($hrBlades, JSON_UNESCAPED_UNICODE),
                    'scopes' => json_encode([], JSON_UNESCAPED_UNICODE),
                    'is_system' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if (Schema::hasTable('community_user')) {
                DB::table('community_user')
                    ->where('community_id', $communityId)
                    ->where('user_id', self::LEGACY_HR_USER_ID)
                    ->update(['community_role_id' => $hrRoleId, 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        // Non-reversible: the hr role + reassignment are kept.
    }
};
