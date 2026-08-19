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
        // 収支コメント・要員コメント共用のリマインドテーブル。
        // コメント種別が増えてもテーブルは増やさない。
        Schema::create('project_comment_reminds', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->morphs('comment');
            // チャットの message_remind_users と同じく、外した時も行は残して 0 に倒す
            $t->boolean('reminded')->default(1);
            $t->timestamps();

            $t->unique(['comment_type', 'comment_id', 'user_id'], 'project_comment_reminds_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_comment_reminds');
    }
};
