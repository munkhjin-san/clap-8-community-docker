<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_update_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('category', 40)->index();
            $table->string('title', 200);
            $table->text('summary')->nullable();
            $table->string('status', 40)->default('published')->index();
            $table->boolean('is_published')->default(true)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('scheduled_start_at')->nullable()->index();
            $table->timestamp('scheduled_end_at')->nullable();
            $table->boolean('must_read')->default(true)->index();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['category', 'is_published', 'published_at'], 'system_update_records_visible_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_update_records');
    }
};
