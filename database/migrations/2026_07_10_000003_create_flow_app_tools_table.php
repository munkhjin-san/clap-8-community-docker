<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_app_tools', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('flow_definition_id')->index();
            $table->string('tool_type');            // 'pdf' (extensible: future tool types)
            $table->string('name');                 // display name, e.g. 「請求書PDF」
            $table->json('config')->nullable();     // tool-specific config (pdf template etc.)
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['flow_definition_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_app_tools');
    }
};
