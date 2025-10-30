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
        Schema::create('project_metric_display_config', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->bigInteger('project_metric_id')->unsigned()->index();
            $t->enum('display_position', ['main', 'sub']);
            $t->bigInteger('parent_metric_id')->nullable();
            $t->enum('color_scheme', ['red', 'green', 'blue', 'yellow']);
            $t->unsignedInteger('sort_order')->default(0);
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
