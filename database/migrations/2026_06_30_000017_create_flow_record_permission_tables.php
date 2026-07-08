<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_record_permission_sets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('flow_definition_id')->index();
            $table->string('match_mode')->default('all');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['flow_definition_id', 'sort_order'], 'flow_rec_perm_sets_def_sort_idx');
        });

        Schema::create('flow_record_permission_conditions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('set_id')->index();
            $table->string('source');
            $table->unsignedBigInteger('field_id')->nullable();
            $table->string('operator');
            $table->json('values')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('flow_record_permission_grants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('set_id')->index();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->boolean('can_view')->default(false);
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_record_permission_grants');
        Schema::dropIfExists('flow_record_permission_conditions');
        Schema::dropIfExists('flow_record_permission_sets');
    }
};
