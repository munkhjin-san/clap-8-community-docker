<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_status_field_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('flow_status_id')->index();
            $table->unsignedBigInteger('flow_field_id')->index();
            $table->string('rule')->default('edit');
            $table->timestamps();

            $table->unique(['flow_status_id', 'flow_field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_status_field_rules');
    }
};
