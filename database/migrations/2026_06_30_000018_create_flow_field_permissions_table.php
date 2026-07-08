<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_field_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('flow_definition_id')->index();
            $table->unsignedBigInteger('field_id')->index();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->boolean('can_view')->default(true);
            $table->boolean('can_edit')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['flow_definition_id', 'field_id'], 'flow_field_perms_def_field_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_field_permissions');
    }
};
