<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drive_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('item_id')->nullable()->index();
            $table->string('item_type', 10);
            $table->string('item_name', 255)->nullable();
            $table->unsignedBigInteger('project_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('action', 32);
            $table->string('from_path', 1024)->nullable();
            $table->string('to_path', 1024)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('client_ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('referer', 1024)->nullable();
            $table->json('context')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->index(['project_id', 'occurred_at']);
            $table->index(['item_id', 'occurred_at']);
            $table->index(['action', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drive_activity_logs');
    }
};
