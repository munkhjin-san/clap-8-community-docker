<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actual_result_departments', function (Blueprint $table) {
            $table->decimal('real_margin', 18, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('actual_result_departments', function (Blueprint $table) {
            $table->decimal('real_margin', 8, 2)->nullable()->change();
        });
    }
};
