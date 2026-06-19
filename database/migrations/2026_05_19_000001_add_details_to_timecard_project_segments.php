<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timecard_project_segments', function (Blueprint $table) {
            if (!Schema::hasColumn('timecard_project_segments', 'details')) {
                $table->json('details')->nullable()->after('minutes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('timecard_project_segments', function (Blueprint $table) {
            if (Schema::hasColumn('timecard_project_segments', 'details')) {
                $table->dropColumn('details');
            }
        });
    }
};
