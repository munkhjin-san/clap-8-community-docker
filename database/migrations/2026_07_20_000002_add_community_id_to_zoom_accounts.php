<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Community-scopes Zoom accounts (Web会議 — the 施設 › Web会議 panel).
 * ZoomAccount is community-owned config (like calendar facilities), so adding
 * community_id + the BelongsToCommunity trait lets each community manage and book
 * only its own Web会議 accounts. Existing rows backfill to the default (glowd)
 * community; a fresh community starts with none.
 *
 * Note: the inbound Zoom webhook (AutoJobController::zoom_event) is context-less
 * and matches by slot only — unchanged by this migration.
 */
return new class extends Migration
{
    private const DEFAULT_COMMUNITY_SLUG = 'glowd';

    public function up(): void
    {
        if (!Schema::hasTable('zoom_accounts') || Schema::hasColumn('zoom_accounts', 'community_id')) {
            return;
        }

        $communityId = DB::table('communities')->where('slug', self::DEFAULT_COMMUNITY_SLUG)->value('id');

        Schema::table('zoom_accounts', function (Blueprint $table) {
            $table->foreignId('community_id')->nullable()->after('id')->index();
        });

        if ($communityId) {
            DB::table('zoom_accounts')->whereNull('community_id')->update(['community_id' => $communityId]);
        }

        if (Schema::hasTable('communities')) {
            Schema::table('zoom_accounts', function (Blueprint $table) {
                $table->foreign('community_id')->references('id')->on('communities')->nullOnDelete();
            });
        }

        // Slots are numbered per-community, so the uniqueness of slot must be
        // scoped by community — otherwise a new community can't reuse slot 0.
        Schema::table('zoom_accounts', function (Blueprint $table) {
            try {
                $table->dropUnique(['slot']);
            } catch (\Throwable $e) {
                // index name may differ / already dropped
            }
            $table->unique(['community_id', 'slot']);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('zoom_accounts') || !Schema::hasColumn('zoom_accounts', 'community_id')) {
            return;
        }

        Schema::table('zoom_accounts', function (Blueprint $table) {
            try {
                $table->dropUnique(['community_id', 'slot']);
            } catch (\Throwable $e) {
                // no index to drop
            }
            $table->unique(['slot']);
            try {
                $table->dropForeign(['community_id']);
            } catch (\Throwable $e) {
                // no FK to drop
            }
            $table->dropColumn('community_id');
        });
    }
};
