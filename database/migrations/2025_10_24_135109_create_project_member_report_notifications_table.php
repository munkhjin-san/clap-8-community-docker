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
        Schema::create('project_member_report_notifications', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable()->index();
            $table->integer('target_user_id')->nullable()->index();
            $table->integer('from_user_id')->nullable()->index();
            $table->integer('project_goal_id')->nullable()->index();
            $table->integer('salary_issue_id')->nullable()->index();
            $table->integer('project_id')->nullable()->index();
            $table->string('which_half', 10);
            $table->integer('year');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_member_report_notifications');
    }
};
