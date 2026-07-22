<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Exams can now be scoped to a single material (nullable = legacy theme-level exam).
        if (! Schema::hasColumn('lesson_exams', 'lesson_material_id')) {
            Schema::table('lesson_exams', function (Blueprint $table) {
                $table->unsignedBigInteger('lesson_material_id')->nullable()->index()->after('lesson_theme_id');
            });
        }

        // A section material can require an exam (mutually exclusive with 理解/質問 依頼).
        if (! Schema::hasColumn('lesson_materials', 'has_exam')) {
            Schema::table('lesson_materials', function (Blueprint $table) {
                $table->boolean('has_exam')->default(false)->after('has_understand');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('lesson_exams', 'lesson_material_id')) {
            Schema::table('lesson_exams', function (Blueprint $table) {
                $table->dropColumn('lesson_material_id');
            });
        }
        if (Schema::hasColumn('lesson_materials', 'has_exam')) {
            Schema::table('lesson_materials', function (Blueprint $table) {
                $table->dropColumn('has_exam');
            });
        }
    }
};
