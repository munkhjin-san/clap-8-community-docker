<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Second wave of community_id scoping, from the full DB schema audit (2026-06-26).
 * Adds community_id to independent root entities, the high-volume custom-field
 * store, per-community lookup/master tables, and user-personal tables that the
 * user confirmed should be isolated. Global tables (e.g. public_holidays) and
 * dead tables (offices/positions) are intentionally excluded.
 *
 * petition_types / lesson_theme_categories have no Eloquent model yet — the
 * column is added for data tagging + FK integrity; they become runtime-scoped
 * once a model with BelongsToCommunity is introduced.
 */
return new class extends Migration
{
    private const DEFAULT_COMMUNITY_SLUG = 'glowd';

    private const TABLES = [
        // Tier 1 — independent business roots
        'knowledge_records',
        'nice_records',
        'welcome_messages',
        'evaluation_records',
        'user_albums',
        'challenge_records',
        'my_groups',
        'work_groups',
        'employee_change_applications',
        'paid_leave_accounts',
        'refresh_accounts',
        // Tier 2 — high-volume polymorphic custom-field store
        'custom_field_data_records',
        // Tier 3 — per-community lookup / master vocabularies
        'tag_records',
        'asset_types',
        'shift_types',
        'incident_categories',
        'incident_punishments',
        'incident_statuses',
        'contact_types',
        'project_types',
        'petition_types',
        'lesson_theme_categories',
        // Tier 4 — user-personal (confirmed: isolate)
        'search_history_records',
        'user_details',
        'app_remember_records',
    ];

    public function up(): void
    {
        $communityId = DB::table('communities')
            ->where('slug', self::DEFAULT_COMMUNITY_SLUG)
            ->value('id');

        foreach (self::TABLES as $tableName) {
            if (!Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'community_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('community_id')->nullable()->after('id')->index();
            });

            if ($communityId) {
                DB::table($tableName)->whereNull('community_id')->update(['community_id' => $communityId]);
            }

            if (DB::connection()->getDriverName() !== 'sqlite') {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreign('community_id')->references('id')->on('communities')->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::TABLES) as $tableName) {
            if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'community_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                if (DB::connection()->getDriverName() !== 'sqlite') {
                    try {
                        $table->dropForeign(['community_id']);
                    } catch (Throwable) {
                    }
                }
                $table->dropColumn('community_id');
            });
        }
    }
};
