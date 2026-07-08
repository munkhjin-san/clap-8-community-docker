<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('flow_definition_id')->index();
            $table->string('key');
            $table->string('label');
            $table->string('input_type');
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('order_number')->default(0)->index();
            $table->unsignedInteger('layout_row')->default(0);
            $table->unsignedInteger('layout_span')->default(12);
            $table->json('depends_on')->nullable();
            $table->timestamps();

            $table->index(['flow_definition_id', 'order_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_fields');
    }
};
