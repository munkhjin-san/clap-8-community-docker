<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds community_id to additional aggregate-root tables that must be isolated
 * per community (org masters, contacts, incidents, Q&A). See
 * App\Models\Concerns\BelongsToCommunity.
 */
return new class extends Migration
{
    private const DEFAULT_COMMUNITY_SLUG = 'glowd';

    private const TABLES = [
        'emergency_contacts',
        'contact_records',
        'incidents',
        'office_records',
        'position_records',
        'question_and_answer_records',
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
