<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Referential integrity is enforced at the application layer, matching the
    // existing convention for link columns in this codebase (no DB-level FKs).
    public function up(): void
    {
        // The salary-issue (昇給課題) target theme. Replaces the loose `theme`
        // title-string coupling; the string column is kept for display/back-compat.
        Schema::table('salary_issues', function (Blueprint $table) {
            if (! Schema::hasColumn('salary_issues', 'lesson_theme_id')) {
                $table->unsignedBigInteger('lesson_theme_id')->nullable()->index()->after('project_goal_id');
            }
        });

        // A challenge-scoped portfolio. Null = ordinary first-learner / repeater
        // portfolio; non-null = the portfolio produced for a specific salary challenge.
        Schema::table('lesson_portfolios', function (Blueprint $table) {
            if (! Schema::hasColumn('lesson_portfolios', 'salary_issue_id')) {
                $table->unsignedBigInteger('salary_issue_id')->nullable()->index()->after('lesson_theme_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lesson_portfolios', function (Blueprint $table) {
            if (Schema::hasColumn('lesson_portfolios', 'salary_issue_id')) {
                $table->dropColumn('salary_issue_id');
            }
        });

        Schema::table('salary_issues', function (Blueprint $table) {
            if (Schema::hasColumn('salary_issues', 'lesson_theme_id')) {
                $table->dropColumn('lesson_theme_id');
            }
        });
    }
};
