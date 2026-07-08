<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_statuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('flow_definition_id')->index();
            $table->string('name');
            $table->unsignedInteger('order_number')->default(0)->index();
            $table->string('is_locked')->nullable();
            $table->string('assignment_type')->default('creator');
            $table->unsignedBigInteger('assignment_target_id')->nullable();
            $table->timestamps();

            $table->index(['flow_definition_id', 'order_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_statuses');
    }
};
