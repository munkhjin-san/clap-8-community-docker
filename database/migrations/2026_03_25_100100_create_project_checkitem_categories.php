<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_checkitem_categories', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('status')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        $labels = DB::table('project_checkitems')
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->map(fn ($label) => trim((string) $label))
            ->unique()
            ->values();

        $now = now();
        foreach ($labels as $index => $label) {
            DB::table('project_checkitem_categories')->updateOrInsert(
                ['label' => $label],
                [
                    'key' => Str::slug($label, '_') ?: 'category_' . ($index + 1),
                    'sort_order' => $index + 1,
                    'status' => 0,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_checkitem_categories');
    }
};
