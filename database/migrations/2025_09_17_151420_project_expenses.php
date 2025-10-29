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
        Schema::create('project_expenses', function (Blueprint $t) {
            $t->id();
            $t->integer('project_record_id')->index();
            $t->date('period')->index();
            $t->integer('salaries')->default(0);
            $t->integer('outsourcing')->default(0);
            $t->integer('internal_orders')->default(0);
            $t->integer('sga_other')->default(0);
            $t->integer('indirect')->default(0);
            $t->integer('bonus')->default(0);
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
