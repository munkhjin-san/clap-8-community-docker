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
        Schema::create('project_goal_incident_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_goal_id')->index();
            $table->string('incident_type');
            $table->unsignedBigInteger('responsible_user_id')->nullable()->index();
            $table->unsignedBigInteger('message_record_id')->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['project_goal_id', 'incident_type'], 'project_goal_incident_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_goal_incident_reports');
    }
};
