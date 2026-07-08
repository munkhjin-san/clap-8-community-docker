<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flow_definitions', function (Blueprint $table) {
            // Status flow is opt-in per app; most custom apps are plain data lists.
            $table->boolean('use_status_flow')->default(false)->after('is_active');
        });

        Schema::table('flow_statuses', function (Blueprint $table) {
            // Where a new record starts. Replaces the locked 作成中/完了 bookend model.
            $table->boolean('is_initial')->default(false)->after('name');
        });

        // Custom action buttons: from a status, move the record to a target status.
        Schema::create('flow_status_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('flow_definition_id')->index();
            $table->unsignedBigInteger('flow_status_id')->index();   // "from" status the button appears on
            $table->string('name')->nullable();                       // internal identifier
            $table->string('label');                                  // shown on the button
            $table->string('color')->nullable();                      // hex
            $table->unsignedBigInteger('to_status_id')->nullable();   // target status
            $table->json('eligible')->nullable();                     // [{subject_type, subject_id}] who may press
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Backfill: existing (test) apps had 作成中/完了 bookends — mark the start one initial.
        // Leave use_status_flow = false; users opt in per app.
        DB::table('flow_statuses')->where('is_locked', 'start')->update(['is_initial' => true]);
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_status_actions');
        Schema::table('flow_statuses', function (Blueprint $table) {
            $table->dropColumn('is_initial');
        });
        Schema::table('flow_definitions', function (Blueprint $table) {
            $table->dropColumn('use_status_flow');
        });
    }
};
