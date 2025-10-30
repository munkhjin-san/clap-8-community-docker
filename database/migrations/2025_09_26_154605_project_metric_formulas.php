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
        Schema::create('project_metric_formulas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_metric_id')->constrained()->cascadeOnDelete();
            $t->text('expression');
            $t->unsignedInteger('version')->default(1);
            $t->timestamps();
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
