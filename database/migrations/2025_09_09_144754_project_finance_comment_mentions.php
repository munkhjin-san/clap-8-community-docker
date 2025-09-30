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
        Schema::create('project_finance_comment_mentions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('comment_id')->index();
            $t->unsignedBigInteger('mentioned_user_id')->index();
            $t->timestamps();
            $t->unique(['comment_id', 'mentioned_user_id'], 'pfc_mention_unique');

            $t->foreign('comment_id', 'pfc_mention_comment_fk')
                ->references('id')->on('project_finance_comments')
                ->onDelete('cascade');

            $t->foreign('mentioned_user_id', 'pfc_mention_user_fk')
                ->references('id')->on('users')
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
