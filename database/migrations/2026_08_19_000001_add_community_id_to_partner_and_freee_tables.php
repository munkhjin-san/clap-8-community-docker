<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Community-scopes the 取引先 master (partner_records) and the freee connection
 * (freee_credentials) — both are community-owned aggregate roots (like offices):
 * each community manages its own partner master and its own freee integration.
 * Existing rows backfill to the default (glowd) community.
 *
 * Note: console imports (kintone partner import) run without CommunityContext, so
 * the BelongsToCommunity creating-hook cannot stamp them — those commands set
 * community_id explicitly (see ImportPartnersFromKintone).
 */
return new class extends Migration
{
    private const DEFAULT_COMMUNITY_SLUG = 'glowd';

    private const TABLES = ['partner_records', 'freee_credentials'];

    public function up(): void
    {
        $communityId = Schema::hasTable('communities')
            ? DB::table('communities')->where('slug', self::DEFAULT_COMMUNITY_SLUG)->value('id')
            : null;

        foreach (self::TABLES as $table) {
            if (!Schema::hasTable($table) || Schema::hasColumn($table, 'community_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('community_id')->nullable()->after('id')->index();
            });

            if ($communityId) {
                DB::table($table)->whereNull('community_id')->update(['community_id' => $communityId]);
            }

            if (Schema::hasTable('communities')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->foreign('community_id')->references('id')->on('communities')->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'community_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                try {
                    $t->dropForeign(['community_id']);
                } catch (\Throwable $e) {
                    // no FK to drop
                }
                $t->dropColumn('community_id');
            });
        }
    }
};
