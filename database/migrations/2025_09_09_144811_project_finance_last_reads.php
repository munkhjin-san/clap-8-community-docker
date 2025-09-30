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
        Schema::create('project_finance_last_reads', function (Blueprint $t) {
            $t->id();
            $t->integer('project_record_id')->index();
            $t->unsignedBigInteger('user_id')->index();
            $t->timestamp('last_read_at')->nullable();

            $t->unique(['project_record_id', 'user_id']);
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
