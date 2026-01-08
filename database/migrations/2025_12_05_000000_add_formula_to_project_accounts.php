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
        Schema::table('project_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('project_accounts', 'is_formula')) {
                $table->boolean('is_formula')->default(false)->after('is_postable');
            }
            if (!Schema::hasColumn('project_accounts', 'formula')) {
                $table->text('formula')->nullable()->after('is_formula');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('project_accounts', 'is_formula')) {
                $table->dropColumn('is_formula');
            }
            if (Schema::hasColumn('project_accounts', 'formula')) {
                $table->dropColumn('formula');
            }
        });
    }
};
