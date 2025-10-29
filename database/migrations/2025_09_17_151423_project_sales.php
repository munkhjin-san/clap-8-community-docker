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
        Schema::create('project_sales', function (Blueprint $t) {
            $t->id();
            $t->integer('project_record_id')->index();
            $t->date('period')->index();
            $t->unsignedInteger('sales')->default(0);
            $t->unsignedInteger('internal_sales')->default(0);
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
