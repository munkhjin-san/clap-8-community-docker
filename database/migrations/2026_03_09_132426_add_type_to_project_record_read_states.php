<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_record_read_states', function (Blueprint $table) {
            $table->string('type', 50)->after('project_record_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_record_read_states', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
