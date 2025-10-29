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
            $table->integer('comment_record_id')->nullable()->index()->after('salary_issue_report_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('message_files', function (Blueprint $table) {
            $table->dropColumn('comment_record_id');
        });
    }
};
