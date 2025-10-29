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
        Schema::create('project_metric_sub_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_metric_id')->constrained()->cascadeOnDelete();
            $table->text('expression');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_metric_sub_metrics');
    }
};
