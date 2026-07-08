<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('flow_definition_id')->index();
            $table->string('name');
            $table->string('view_mode')->default('table');
            $table->json('columns')->nullable();
            $table->json('filters')->nullable();
            $table->json('sort')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_views');
    }
};
