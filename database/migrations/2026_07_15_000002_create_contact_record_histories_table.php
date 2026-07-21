<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_record_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_record_id')->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('event', 20);            // 'created' | 'updated'
            $table->string('field', 60)->nullable(); // changed field key (null for 'created')
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_record_histories');
    }
};
