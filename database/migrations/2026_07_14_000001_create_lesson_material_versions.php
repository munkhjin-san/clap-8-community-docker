<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_material_versions', function (Blueprint $table) {
            $table->id();
            $table->integer('lesson_theme_id')->index(); // lesson_themes.id is legacy int; no DB-level FK (house style)
            $table->unsignedInteger('version_no');
            $table->boolean('is_default')->default(false);
            $table->string('label')->nullable();
            $table->timestamps();

            $table->unique(['lesson_theme_id', 'version_no'], 'lesson_material_versions_theme_version_unique');
        });

        if (! Schema::hasColumn('lesson_materials', 'lesson_material_version_id')) {
            Schema::table('lesson_materials', function (Blueprint $table) {
                $table->unsignedBigInteger('lesson_material_version_id')->nullable()->index()->after('lesson_theme_id');
            });
        }

        // Backfill: every theme that already has (non-retired) materials gets a
        // version 1 marked default, and those materials are assigned to it.
        $themeIds = DB::table('lesson_materials')
            ->whereNull('retired_at')
            ->distinct()
            ->pluck('lesson_theme_id');

        foreach ($themeIds as $themeId) {
            if ($themeId === null) {
                continue;
            }

            $versionId = DB::table('lesson_material_versions')->insertGetId([
                'lesson_theme_id' => $themeId,
                'version_no' => 1,
                'is_default' => true,
                'label' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('lesson_materials')
                ->where('lesson_theme_id', $themeId)
                ->whereNull('retired_at')
                ->update(['lesson_material_version_id' => $versionId]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('lesson_materials', 'lesson_material_version_id')) {
            Schema::table('lesson_materials', function (Blueprint $table) {
                $table->dropColumn('lesson_material_version_id');
            });
        }

        Schema::dropIfExists('lesson_material_versions');
    }
};
