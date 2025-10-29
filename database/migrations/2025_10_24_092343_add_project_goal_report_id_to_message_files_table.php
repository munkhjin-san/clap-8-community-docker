<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('message_files', function (Blueprint $table) {
            $table->integer('project_goal_report_id')->nullable()->index()->after('board_id');
            $table->integer('salary_issue_report_id')->nullable()->index()->after('board_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('message_files', function (Blueprint $table) {
            $table->dropIndex(['project_goal_report_id']);
            $table->dropColumn('project_goal_report_id');
            $table->dropIndex(['salary_issue_report_id']);
            $table->dropColumn('salary_issue_report_id');
        });
    }
};
