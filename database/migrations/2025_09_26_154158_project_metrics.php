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
        Schema::create('project_metrics', function (Blueprint $t) {
            $t->id();
            $t->string('label_ja')->unique();
            $t->enum('kind', ['input', 'derived']);
            $t->enum('value_type', ['amount', 'rate', 'currency'])->default('amount');
            $t->enum('line', ['sales', 'expense', 'profit', 'profit_rate'])->nullable();
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('sort_order')->default(0);
            $t->string('scenario_label_ja')->nullable();
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
