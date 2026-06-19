<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timecard_project_segments', function (Blueprint $table) {
            if (!Schema::hasColumn('timecard_project_segments', 'comment')) {
                $table->text('comment')->nullable()->after('details');
            }
        });
    }

    public function down(): void
    {
        Schema::table('timecard_project_segments', function (Blueprint $table) {
            if (Schema::hasColumn('timecard_project_segments', 'comment')) {
                $table->dropColumn('comment');
            }
        });
    }
};
