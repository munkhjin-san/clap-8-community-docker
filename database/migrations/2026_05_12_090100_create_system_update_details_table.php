<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_update_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_update_record_id')
                ->constrained('system_update_records')
                ->cascadeOnDelete();
            $table->string('type', 40)->index();
            $table->string('title', 200);
            $table->text('content')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_update_details');
    }
};
