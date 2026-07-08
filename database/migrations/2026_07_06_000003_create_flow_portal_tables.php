<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('flow_app_pins')) {
            Schema::create('flow_app_pins', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('flow_definition_id')->index();
                $table->timestamps();
                $table->unique(['user_id', 'flow_definition_id']);
            });
        }

        if (!Schema::hasTable('flow_portal_prefs')) {
            Schema::create('flow_portal_prefs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->unique();
                $table->string('density', 20)->default('normal');
                $table->string('sort', 30)->default('created_desc');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_app_pins');
        Schema::dropIfExists('flow_portal_prefs');
    }
};
