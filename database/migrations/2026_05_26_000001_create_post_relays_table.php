<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_relays', function (Blueprint $table) {
            $table->id();
            $table->string('relay_type', 30);
            $table->integer('source_post_id');
            $table->integer('accepted_post_id')->nullable();
            $table->foreignId('from_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('to_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('declined_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->tinyInteger('status')->default(0);
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('deadline_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['relay_type', 'source_post_id', 'from_user_id', 'to_user_id'], 'post_relays_unique_source_path');
            $table->index(['relay_type', 'status', 'to_user_id', 'deadline_at'], 'post_relays_recipient_index');
            $table->index(['relay_type', 'status', 'from_user_id'], 'post_relays_sender_index');
            $table->index('accepted_post_id');

            $table->foreign('source_post_id')->references('id')->on('post_records')->cascadeOnDelete();
            $table->foreign('accepted_post_id')->references('id')->on('post_records')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_relays');
    }
};
