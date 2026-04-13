<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('asset_category_item_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_category_item_id')->constrained('asset_category_items')->cascadeOnDelete();
            $table->string('key')->nullable();
            $table->string('label')->nullable();
            $table->string('input_type'); // shorttext | longtext | password
            $table->string('placeholder')->nullable();
            $table->string('rules')->nullable();
            $table->unsignedInteger('sort_order')->nullable();
            $table->timestamps();
        });

        Schema::create('asset_record_field_values', function (Blueprint $table) {
            $table->id();
            $table->integer('asset_record_id');
            $table->foreignId('asset_category_item_field_id')->constrained('asset_category_item_fields')->cascadeOnDelete();
            $table->longText('value')->nullable();
            $table->timestamps();

            $table->unique(['asset_record_id', 'asset_category_item_field_id'], 'asset_record_field_unique');

            $table->foreign('asset_record_id')
                ->references('id')
                ->on('asset_records')
                ->cascadeOnDelete();
        });

        if (Schema::hasTable('asset_records') && ! Schema::hasColumn('asset_records', 'asset_category_item_id')) {
            Schema::table('asset_records', function (Blueprint $table) {
                $table->foreignId('asset_category_item_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('asset_category_items')
                    ->nullOnDelete();
            });
        }

        // Seed default fields for existing items based on item.type.
        $now = now();

        $items = DB::table('asset_category_items')
            ->select('id', 'type', 'title', 'required_data')
            ->orderByRaw('sort_order IS NULL, sort_order')
            ->orderBy('id')
            ->get();

        foreach ($items as $item) {            

            // Physical assets default to one short text field mapped to model_number.
            DB::table('asset_category_item_fields')->insert([
                'asset_category_item_id' => $item->id,
                'key' => 'model_number',
                'label' => '詳細',
                'input_type' => 'shorttext',
                'placeholder' => $item->required_data ?: '詳細（スペックなど）',
                'rules' => 'required',
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('asset_records') && Schema::hasColumn('asset_records', 'asset_category_item_id')) {
            Schema::table('asset_records', function (Blueprint $table) {
                $table->dropConstrainedForeignId('asset_category_item_id');
            });
        }

        Schema::dropIfExists('asset_record_field_values');
        Schema::dropIfExists('asset_category_item_fields');
    }
};
