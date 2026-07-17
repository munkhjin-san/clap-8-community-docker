<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_themes', function (Blueprint $table) {
            $table->integer('previous_version')->nullable()->after('archive')->index();
        });
    }

    public function down(): void
    {
        Schema::table('lesson_themes', function (Blueprint $table) {
            $table->dropIndex(['previous_version']);
            $table->dropColumn('previous_version');
        });
    }
};
