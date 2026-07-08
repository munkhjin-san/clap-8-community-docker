<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_shares', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('flow_definition_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('position_id')->nullable()->index();
            $table->string('access_level')->default('use');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_shares');
    }
};
