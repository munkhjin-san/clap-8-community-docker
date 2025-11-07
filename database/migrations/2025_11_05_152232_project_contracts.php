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
        Schema::create('project_contracts', function (Blueprint $t) {
            $t->id();
            $t->integer('project_record_id');
            $t->enum('review_type', ['quick','deep']);
            $t->string('overall_risk')->index();
            $t->unsignedInteger('findings_count')->default(0);
            $t->json('result_json');
            $t->string('response_hash', 64)->nullable();

            
            $t->foreign('project_record_id')
                ->references('id')
                ->on('project_records')
                ->cascadeOnDelete();

            $t->index(['overall_risk', 'review_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_contracts');
    }
};
