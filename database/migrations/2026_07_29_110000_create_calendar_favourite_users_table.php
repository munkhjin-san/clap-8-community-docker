<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 「よく一緒に予定を入れる人」＝ユーザー一覧の並び順を決めるためのスコア。
 * 表示用の正確な数値ではなく、並び替えのためだけの指標。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_favourite_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->unsignedBigInteger('member_id');
            // 並び替え用スコア。1イベントあたり 1/(参加者数-1) を加算
            $table->double('score')->default(0);
            // 内訳確認用（UIには出さない）
            $table->unsignedInteger('shared_count')->default(0);
            $table->timestamp('last_together_at')->nullable();
            $table->timestamps();

            $table->unique(['owner_id', 'member_id']);
            $table->index(['owner_id', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_favourite_users');
    }
};
