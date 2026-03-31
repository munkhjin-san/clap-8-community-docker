<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_checkitems', function (Blueprint $table) {
            $table->foreignId('project_checkitem_template_id')->nullable()->after('project_record_id')->constrained('project_checkitem_templates')->nullOnDelete();
            $table->foreignId('project_checkitem_category_id')->nullable()->after('project_checkitem_template_id')->constrained('project_checkitem_categories')->nullOnDelete();
            $table->boolean('is_applicable')->default(true)->after('status');
        });

        $categoryMap = DB::table('project_checkitem_categories')->pluck('id', 'label');
        foreach (DB::table('project_checkitems')->select('id', 'category')->get() as $item) {
            $categoryId = $categoryMap[trim((string) $item->category)] ?? null;
            if (!$categoryId) {
                continue;
            }
            DB::table('project_checkitems')
                ->where('id', $item->id)
                ->update(['project_checkitem_category_id' => $categoryId]);
        }
    }

    public function down(): void
    {
        Schema::table('project_checkitems', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_checkitem_category_id');
            $table->dropConstrainedForeignId('project_checkitem_template_id');
            $table->dropColumn('is_applicable');
        });
    }
};
