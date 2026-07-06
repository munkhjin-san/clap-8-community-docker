<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Realigns community_id columns with the aggregate-root scoping policy
 * (see App\Models\Concerns\BelongsToCommunity).
 *
 *  - ADD to real support roots that were missing from the original list
 *    (the original migration referenced non-existent `support_records`).
 *  - DROP from child / pivot tables that should inherit community through
 *    their parent root instead of being scoped directly.
 */
return new class extends Migration
{
    private const DEFAULT_COMMUNITY_SLUG = 'glowd';

    /** Roots that must gain community_id + scoping. */
    private const ADD_TABLES = [
        'support_conversations',
        'support_mail_form_records',
    ];

    /** Child / pivot tables whose community_id is redundant. */
    private const DROP_TABLES = [
        'project_members',
        'project_member_roles',
        'lesson_materials',
    ];

    public function up(): void
    {
        $communityId = DB::table('communities')
            ->where('slug', self::DEFAULT_COMMUNITY_SLUG)
            ->value('id');

        foreach (self::ADD_TABLES as $tableName) {
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

        foreach (self::DROP_TABLES as $tableName) {
            $this->dropCommunityColumn($tableName);
        }
    }

    public function down(): void
    {
        $communityId = DB::table('communities')
            ->where('slug', self::DEFAULT_COMMUNITY_SLUG)
            ->value('id');

        // Restore community_id on the child / pivot tables.
        foreach (self::DROP_TABLES as $tableName) {
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

        foreach (self::ADD_TABLES as $tableName) {
            $this->dropCommunityColumn($tableName);
        }
    }

    private function dropCommunityColumn(string $tableName): void
    {
        if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'community_id')) {
            return;
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
};
