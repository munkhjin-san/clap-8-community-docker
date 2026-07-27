<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-recipient event rows behind the per-app bell badge. Written at event time
        // (comment / new record / status change), cleared per the event type's read rule.
        Schema::create('flow_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');                 // recipient
            $table->unsignedBigInteger('flow_definition_id');
            $table->unsignedBigInteger('flow_record_id')->nullable();
            $table->string('type', 30);                            // comment | new_record | status_change
            $table->unsignedBigInteger('actor_id')->nullable();    // who triggered the event
            $table->json('meta')->nullable();                      // {count} for grouped imports, {from,to} for status
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->nullable();

            // badge counts + popup list
            $table->index(['user_id', 'read_at', 'flow_definition_id'], 'fn_user_unread_idx');
            // mark-read on record open / comment-tab read + cleanup on record delete
            $table->index(['flow_record_id', 'user_id', 'type'], 'fn_record_user_idx');
            $table->index('flow_definition_id', 'fn_definition_idx');
        });

        // Sparse per-user per-app opt-outs; defaults (all ON) live in code — only deviations get a row.
        Schema::create('flow_notification_prefs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('flow_definition_id');
            $table->string('pref', 40);                            // comment_own | comment_participated | new_record | status_change
            $table->boolean('enabled');
            $table->timestamps();

            $table->unique(['user_id', 'flow_definition_id', 'pref'], 'fnp_user_app_pref_unique');
            $table->index(['flow_definition_id', 'pref'], 'fnp_app_pref_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_notification_prefs');
        Schema::dropIfExists('flow_notifications');
    }
};
