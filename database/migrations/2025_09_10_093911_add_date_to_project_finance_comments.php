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
        Schema::table('project_finance_comments', function (Blueprint $table) {
            $table->date('period')->nullable()->index()->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_finance_comments', function (Blueprint $table) {
           $table->dropColumn('period');
        });
    }
};
