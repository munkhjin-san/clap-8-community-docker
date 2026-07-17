<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_themes', function (Blueprint $table) {
            if (! Schema::hasColumn('lesson_themes', 'axis')) {
                $table->string('axis')->nullable()->after('salary_issue_target');
            }
        });

        // Backfill the 9 salary-issue target themes with their 軸 (previously only
        // encoded in the frontend issueThemes matrix): columns 自己 / 組織 / 社会.
        foreach ([
            '自己' => [1, 2, 3],
            '組織' => [5, 6, 7],
            '社会' => [8, 9, 10],
        ] as $axis => $ids) {
            DB::table('lesson_themes')->whereIn('id', $ids)->update(['axis' => $axis]);
        }
    }

    public function down(): void
    {
        Schema::table('lesson_themes', function (Blueprint $table) {
            if (Schema::hasColumn('lesson_themes', 'axis')) {
                $table->dropColumn('axis');
            }
        });
    }
};
