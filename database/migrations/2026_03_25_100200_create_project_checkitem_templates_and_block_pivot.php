<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_checkitem_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_type_id')->constrained('project_types')->cascadeOnDelete();
            $table->foreignId('project_checkitem_category_id')->nullable();
            $table->foreign(
                'project_checkitem_category_id',
                'fk_pct_category_id'
            )->references('id')->on('project_checkitem_categories')->nullOnDelete();
            $table->string('category_label', 100)->nullable();
            $table->string('label', 255);
            $table->foreignId('parent_id')->nullable()->constrained('project_checkitem_templates')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('status')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('custom_form_block_project_checkitem_category', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('custom_form_block_id');
            $table->foreign(
                'custom_form_block_id',
                'fk_cfblock_pct_cfblock_id'
            )->references('id')->on('custom_form_blocks')->cascadeOnDelete();
            $table->foreignId('project_checkitem_category_id');
            $table->foreign(
                'project_checkitem_category_id',
                'fk_cct_category_id'
            )->references('id')->on('project_checkitem_categories')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['custom_form_block_id', 'project_checkitem_category_id'], 'cf_block_checkitem_category_unique');
        });

        $defaultProjectTypeId = DB::table('project_types')->where('key', 'default')->value('id');
        if (!$defaultProjectTypeId) {
            return;
        }

        $categoryMap = DB::table('project_checkitem_categories')->pluck('id', 'label');

        $groups = [
            '人事' => [
                ['label' => '体制確認'],
                ['label' => 'メンバー登録'],
            ],
            '法務' => [
                ['label' => 'クライアント契約'],
                ['label' => 'パートナー契約'],
                ['label' => '車両契約'],
                ['label' => '駐車場契約'],
                ['label' => 'その他契約'],
            ],
            'リスク分析' => [
                ['label' => '業務マニュアル'],
            ],
            'その他' => [
                ['label' => '貸与品の物品登録'],
                ['label' => '社用車利用手続'],
                ['label' => 'アカウント作成'],
            ],
        ];

        $sort = 1;
        $now = now();
        foreach ($groups as $categoryLabel => $items) {
            $categoryId = $categoryMap[$categoryLabel] ?? null;
            foreach ($items as $item) {
                $parentId = DB::table('project_checkitem_templates')->insertGetId([
                    'project_type_id' => $defaultProjectTypeId,
                    'project_checkitem_category_id' => $categoryId,
                    'category_label' => $categoryLabel,
                    'label' => trim($item['label']),
                    'sort_order' => $sort++,
                    'status' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                foreach ($item['children'] ?? [] as $childLabel) {
                    DB::table('project_checkitem_templates')->insert([
                        'project_type_id' => $defaultProjectTypeId,
                        'project_checkitem_category_id' => $categoryId,
                        'category_label' => $categoryLabel,
                        'label' => trim($childLabel),
                        'parent_id' => $parentId,
                        'sort_order' => $sort++,
                        'status' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        $blockCategoryRows = DB::table('custom_form_blocks')
            ->select('id', 'categories')
            ->whereNotNull('categories')
            ->get();

        foreach ($blockCategoryRows as $row) {
            $categories = json_decode($row->categories, true);
            if (!is_array($categories)) {
                continue;
            }
            foreach ($categories as $label) {
                $label = trim((string) $label);
                if ($label === '') {
                    continue;
                }
                $categoryId = $categoryMap[$label] ?? null;
                if (!$categoryId) {
                    continue;
                }
                DB::table('custom_form_block_project_checkitem_category')->updateOrInsert(
                    [
                        'custom_form_block_id' => $row->id,
                        'project_checkitem_category_id' => $categoryId,
                    ],
                    [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_form_block_project_checkitem_category');
        Schema::dropIfExists('project_checkitem_templates');
    }
};
