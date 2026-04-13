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
        Schema::create('project_assign_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_assign_record_id');
            $table->integer('user_id')->nullable();
            $table->integer('actual_user_id')->nullable(); // 実際にアクションを行ったユーザーID
            $table->string('content')->nullable();
            $table->json('additional_data')->nullable(); // 追加のデータを保存するためのJSONカラム
            $table->string('action_type')->nullable(); // 例: 'comment', 'status_change', 'support_level_change'など
            $table->timestamps();

            $table->foreign('project_assign_record_id')
                ->references('id')
                ->on('project_assign_records')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_assign_actions');
    }
};
