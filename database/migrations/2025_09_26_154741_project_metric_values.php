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
        Schema::create('project_metric_values', function (Blueprint $t) {
            $t->id();
            $t->integer('project_record_id')->index();
            $t->date('period');
            $t->foreignId('project_metric_id')->constrained()->cascadeOnDelete();
            $t->decimal('value', 18, 2)->nullable();
            $t->enum('source', ['manual', 'calc'])->default('manual');
            $t->unsignedInteger('calc_version')->nullable();
            $t->timestamps();
            $t->unique(['project_record_id', 'period', 'project_metric_id'], 'uniq_project_period_metric');
            $t->index(['period']);
            $t->foreign('project_record_id')
                ->references('id')->on('project_records')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
