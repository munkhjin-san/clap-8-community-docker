<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * カレンダーの「マイグループ以外から一時的に追加した表示メンバー」を保存する。
 * マイグループの選択状態（my_groups.selected / my_group_users.selected_as_calendar_member）
 * はサーバー側で持っているので、追加ユーザーだけがリロードで消えないようにする。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_extra_users', function (Blueprint $table) {
            $table->id();
            // 追加した本人
            $table->unsignedBigInteger('user_id');
            // カレンダーに表示する相手
            $table->unsignedBigInteger('member_id');
            $table->timestamps();

            $table->unique(['user_id', 'member_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_extra_users');
    }
};
