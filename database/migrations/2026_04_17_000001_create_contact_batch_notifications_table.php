<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_batch_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_batch_id')->constrained()->cascadeOnDelete();
            $table->string('status', 32);
            $table->string('title');
            $table->text('message');
            $table->string('url')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('pushed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'contact_batch_id', 'status'], 'contact_batch_notifications_unique');
            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_batch_notifications');
    }
};
