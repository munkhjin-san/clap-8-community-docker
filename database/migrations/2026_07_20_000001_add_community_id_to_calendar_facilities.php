<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Community-scopes calendar facilities (会議室 / 車 — the 施設 admin screen).
 * CalendarFacility is reference data owned by a community (like offices/positions),
 * so adding community_id + the BelongsToCommunity trait makes each community manage
 * and book only its own facilities. Existing rows backfill to the default (glowd)
 * community; a fresh community starts with none.
 */
return new class extends Migration
{
    private const DEFAULT_COMMUNITY_SLUG = 'glowd';

    public function up(): void
    {
        if (!Schema::hasTable('calendar_facilities') || Schema::hasColumn('calendar_facilities', 'community_id')) {
            return;
        }

        $communityId = DB::table('communities')->where('slug', self::DEFAULT_COMMUNITY_SLUG)->value('id');

        Schema::table('calendar_facilities', function (Blueprint $table) {
            $table->foreignId('community_id')->nullable()->after('id')->index();
        });

        if ($communityId) {
            DB::table('calendar_facilities')->whereNull('community_id')->update(['community_id' => $communityId]);
        }

        if (Schema::hasTable('communities')) {
            Schema::table('calendar_facilities', function (Blueprint $table) {
                $table->foreign('community_id')->references('id')->on('communities')->nullOnDelete();
            });
        }

        // Slots are numbered per-community, so the uniqueness of (type, slot) must
        // be scoped by community — otherwise a new community can't reuse slot 0.
        Schema::table('calendar_facilities', function (Blueprint $table) {
            try {
                $table->dropUnique(['type', 'slot']);
            } catch (\Throwable $e) {
                // index name may differ / already dropped
            }
            $table->unique(['community_id', 'type', 'slot']);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('calendar_facilities') || !Schema::hasColumn('calendar_facilities', 'community_id')) {
            return;
        }

        Schema::table('calendar_facilities', function (Blueprint $table) {
            try {
                $table->dropUnique(['community_id', 'type', 'slot']);
            } catch (\Throwable $e) {
                // no index to drop
            }
            $table->unique(['type', 'slot']);
            try {
                $table->dropForeign(['community_id']);
            } catch (\Throwable $e) {
                // no FK to drop
            }
            $table->dropColumn('community_id');
        });
    }
};
