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
        Schema::create('project_finance_comments', function (Blueprint $t) {
            $t->id(); // this is BIGINT for *this* table, which is fine (it’s the PK here)
            $t->integer('project_record_id')->index();   // ← match INT
            $t->unsignedBigInteger('user_id')->index();             // match whatever users.id is
            $t->text('comment')->nullable();
            $t->string('type')->nullable();
            $t->timestamps();
            $t->softDeletes();

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
