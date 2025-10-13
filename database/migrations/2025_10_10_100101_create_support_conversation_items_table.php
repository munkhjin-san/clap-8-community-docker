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
        Schema::create('support_conversation_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('support_conversation_id')->constrained('support_conversations')->cascadeOnUpdate()->cascadeOnDelete();
            $t->longText('message')->nullable();
            $t->longText('source')->nullable();
            $t->longText('keywords')->nullable();
            $t->string('role', 32);
            $t->softDeletes();
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_conversation_items');
    }
};
