<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * プロジェクト ⇄ 取引先の中間テーブル。1プロジェクトに複数の取引先を紐付けられる。
 *
 * 既存の project_records.partners（パートナー企業のJSON配列）とは別物。あちらは
 * 文字列の配列で、顧客企業（customers）と同じく手入力のまま残す。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_partners', function (Blueprint $table) {
            $table->id();
            // project_records.id は signed int のため foreignId（bigint unsigned）は使えない。
            $table->integer('project_record_id');
            $table->foreignId('partner_record_id');
            $table->timestamps();

            $table->foreign('project_record_id')
                ->references('id')
                ->on('project_records')
                ->cascadeOnDelete();
            $table->foreign('partner_record_id')
                ->references('id')
                ->on('partner_records')
                ->cascadeOnDelete();

            // 同じ組み合わせを二重に持たせない
            $table->unique(['project_record_id', 'partner_record_id']);
            $table->index('partner_record_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_partners');
    }
};
