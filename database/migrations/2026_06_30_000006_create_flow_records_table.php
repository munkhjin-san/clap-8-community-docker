<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('flow_definition_id')->index();
            $table->unsignedBigInteger('current_status_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['flow_definition_id', 'current_status_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_records');
    }
};
