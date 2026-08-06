<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ラベル項目がHTMLを持てるようにする。
 *
 * ラベルは「説明・注意書き」を置く枠で、これまでは素のテキストだった。リッチテキストで書けるように
 * すると varchar(255) では即あふれる（kintoneから持ってきた注意書きは1つで約1,000文字ある）。
 *
 * 対象はラベルだけだが、列は全項目共通なので TEXT に広げる。データ項目の見出しは今までどおり
 * 短い文字列で、長さの上限は保存時の検証で分けている。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flow_fields', function (Blueprint $table) {
            $table->text('label')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('flow_fields', function (Blueprint $table) {
            $table->string('label', 255)->nullable()->change();
        });
    }
};
