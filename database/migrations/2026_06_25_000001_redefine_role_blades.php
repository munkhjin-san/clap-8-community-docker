<?php

use App\Services\Community\CommunityBladeCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Switches every community role onto the new blade model (single source of
 * truth) and turns the former "partner" membership scope into a real role.
 *
 * - Existing roles are KEPT; only their blade list (capabilities column) is
 *   rewritten to the behavior-preserving defaults.
 * - A `partner` role is created per community if missing, and memberships that
 *   were flagged scope = partner are reassigned to it.
 * - The legacy `scopes` column is cleared (no longer used by gates).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('communities') || !Schema::hasTable('community_roles')) {
            return;
        }

        $defaults = CommunityBladeCatalog::roleDefaults();

        foreach (DB::table('communities')->pluck('id') as $communityId) {
            $this->ensurePartnerRole((int) $communityId);

            foreach ($defaults as $key => $blades) {
                DB::table('community_roles')
                    ->where('community_id', $communityId)
                    ->where('key', $key)
                    ->update([
                        'capabilities' => json_encode($blades, JSON_UNESCAPED_UNICODE),
                        'scopes' => json_encode([], JSON_UNESCAPED_UNICODE),
                        'updated_at' => now(),
                    ]);
            }

            $this->reassignPartnerMemberships((int) $communityId);
        }
    }

    public function down(): void
    {
        // Non-reversible data remap; intentionally a no-op. Roles are preserved.
    }

    private function ensurePartnerRole(int $communityId): void
    {
        $exists = DB::table('community_roles')
            ->where('community_id', $communityId)
            ->where('key', 'partner')
            ->exists();

        if ($exists) {
            return;
        }

        $sortOrder = ((int) DB::table('community_roles')->where('community_id', $communityId)->max('sort_order')) + 10;

        DB::table('community_roles')->insert([
            'community_id' => $communityId,
            'key' => 'partner',
            'name' => 'パートナー',
            'sort_order' => $sortOrder,
            'capabilities' => json_encode(CommunityBladeCatalog::roleDefaults()['partner'], JSON_UNESCAPED_UNICODE),
            'scopes' => json_encode([], JSON_UNESCAPED_UNICODE),
            'is_system' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function reassignPartnerMemberships(int $communityId): void
    {
        if (!Schema::hasTable('community_user') || !Schema::hasColumn('community_user', 'scope')) {
            return;
        }

        $partnerRoleId = DB::table('community_roles')
            ->where('community_id', $communityId)
            ->where('key', 'partner')
            ->value('id');

        if (!$partnerRoleId) {
            return;
        }

        DB::table('community_user')
            ->where('community_id', $communityId)
            ->where('scope', 'partner')
            ->update([
                'community_role_id' => $partnerRoleId,
                'updated_at' => now(),
            ]);
    }
};
