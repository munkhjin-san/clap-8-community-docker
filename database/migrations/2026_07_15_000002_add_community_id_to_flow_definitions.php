<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Community-scopes the Flow (アプリ) feature. FlowDefinition is the aggregate root
 * (the app); adding community_id + the BelongsToCommunity trait makes each
 * community see only its own apps, and records/fields/statuses/assignees/shares
 * isolate transitively through their app (flow_definition_id).
 *
 * Merged from origin/main, where Flow was built single-org (no community_id).
 * Existing apps are backfilled to the default (glowd) community.
 */
return new class extends Migration
{
    private const DEFAULT_COMMUNITY_SLUG = 'glowd';

    public function up(): void
    {
        if (!Schema::hasTable('flow_definitions') || Schema::hasColumn('flow_definitions', 'community_id')) {
            return;
        }

        $communityId = DB::table('communities')->where('slug', self::DEFAULT_COMMUNITY_SLUG)->value('id');

        Schema::table('flow_definitions', function (Blueprint $table) {
            $table->foreignId('community_id')->nullable()->after('id')->index();
        });

        if ($communityId) {
            DB::table('flow_definitions')->whereNull('community_id')->update(['community_id' => $communityId]);
        }

        if (Schema::hasTable('communities')) {
            Schema::table('flow_definitions', function (Blueprint $table) {
                $table->foreign('community_id')->references('id')->on('communities')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('flow_definitions') || !Schema::hasColumn('flow_definitions', 'community_id')) {
            return;
        }

        Schema::table('flow_definitions', function (Blueprint $table) {
            try {
                $table->dropForeign(['community_id']);
            } catch (\Throwable $e) {
                // no FK to drop
            }
            $table->dropColumn('community_id');
        });
    }
};
