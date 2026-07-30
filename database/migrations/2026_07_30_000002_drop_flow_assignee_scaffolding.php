<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the unused assignee scaffolding.
 *
 * `flow_record_assignees` was written only by FlowService::advance() / sendBack() / createRecord(),
 * none of which any route or controller ever called — the live button path is applyStatusAction(),
 * which never touched it. `flow_statuses.assignment_type` was never exposed in the builder either,
 * so every status sits on the 'creator' default. Nothing read the table for anything user-visible:
 * waitingForUserQuery() had no callers, and isAssignee() fed only the superseded canViewRecord() /
 * canActOnRecord() permission pair.
 *
 * 対応待ち is resolved live from an action's 押せる人 (hasExplicitPendingAction), which is unrelated
 * to any of this.
 *
 * The table is therefore empty on every environment by construction, not by luck. down() recreates
 * the shape; there is no data to restore.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('flow_record_assignees');

        Schema::table('flow_statuses', function (Blueprint $table) {
            $table->dropColumn(['assignment_type', 'assignment_target_id']);
        });
    }

    public function down(): void
    {
        Schema::table('flow_statuses', function (Blueprint $table) {
            $table->string('assignment_type')->nullable()->after('is_locked');
            $table->unsignedBigInteger('assignment_target_id')->nullable()->after('assignment_type');
        });

        Schema::create('flow_record_assignees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('flow_record_id')->index();
            $table->unsignedBigInteger('flow_status_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('source')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['flow_record_id', 'flow_status_id', 'user_id'], 'flow_record_assignee_unique');
        });
    }
};
