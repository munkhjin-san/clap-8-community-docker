<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_portfolios', function (Blueprint $table) {
            if (! Schema::hasColumn('lesson_portfolios', 'discussion_topic')) {
                $table->text('discussion_topic')->nullable()->after('noticed');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lesson_portfolios', function (Blueprint $table) {
            if (Schema::hasColumn('lesson_portfolios', 'discussion_topic')) {
                $table->dropColumn('discussion_topic');
            }
        });
    }
};
