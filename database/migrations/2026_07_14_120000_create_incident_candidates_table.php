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
        Schema::create('incident_candidates', function (Blueprint $table) {
            $table->id();

            // daily_report_streak | outcome_goal_submission | outcome_goal_pm_approval
            $table->string('source_type')->index();

            // The person the candidate is about (streak misser / goal owner / non-approving PM).
            $table->foreignId('subject_user_id')->constrained('users')->cascadeOnDelete();

            // Scope target: the project whose PM (or director) should act.
            // Plain column + index (no FK) to match the incidents table — project_records.id
            // is a legacy integer type and the codebase avoids FK constraints against it.
            $table->unsignedBigInteger('project_record_id')->nullable();

            // pm | director
            $table->string('audience')->index();

            // Display payload (missed dates, goal title, pm names, occurrence ids, etc.)
            $table->json('context')->nullable();

            // pending | incident_created | dismissed
            $table->string('status')->default('pending')->index();

            // Required when a reviewer marks it "not an incident".
            $table->text('decision_reason')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();

            // Set when a reviewer turns the candidate into a formal incident.
            $table->foreignId('resulting_incident_id')->nullable()->constrained('incidents')->nullOnDelete();

            // Idempotency key so crons can firstOrCreate (streak: latest report_date; goal: project_goal_id).
            $table->string('dedup_key');

            $table->timestamps();

            $table->unique(['source_type', 'subject_user_id', 'dedup_key'], 'incident_candidate_dedup_unique');
            $table->index(['audience', 'status']);
            $table->index(['project_record_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incident_candidates');
    }
};
