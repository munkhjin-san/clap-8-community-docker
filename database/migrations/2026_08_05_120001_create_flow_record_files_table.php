<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * カスタムアプリのファイル項目の保管台帳。
 *
 * これまではアップロードのたびに `message_files`（チャット用のテーブル）に行を作って
 * IDだけを借り、実体の情報は flow_record_values.value_json に丸ごと複製していた。
 * その結果 message_id が NULL の行がチャット側のテーブルに溜まり続け、ファイルから
 * 持ち主のレコードへ辿る道が無く、権限判定も掛けられなかった。ここが持ち主になる。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_record_files', function (Blueprint $table) {
            $table->id();
            // 保存先パスに使うので必須。レコード未保存（pending）でもアプリは分かっている。
            $table->unsignedBigInteger('flow_definition_id');
            // pending の間は NULL。保存時にレコードへ結び付く。
            $table->unsignedBigInteger('flow_record_id')->nullable();
            // 権限判定の単位。テーブル内の列の場合は「親のテーブル項目」のID。
            $table->unsignedBigInteger('flow_field_id')->nullable();
            // テーブル項目の中のファイル列のときだけ入る（どの列かの記録・掃除用）。
            $table->string('table_column_key')->nullable();

            $table->string('name');                      // 元のファイル名（表示用）
            $table->string('extension', 32)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();

            // 実体の場所。パス規則が将来変わっても、既存行はこの値で引き続き読める。
            $table->string('disk_path', 512);

            $table->unsignedBigInteger('uploaded_by')->nullable();

            // pending  = アップロード済みだがレコード未保存（期限切れで掃除される）
            // attached = レコードに結び付いた
            // missing  = 台帳はあるが実体が失われている（旧temp_upload運用で消えた分）
            $table->string('status', 16)->default('pending');

            // 旧 message_files の行ID。移行を何度でも安全にやり直せるようにするための鍵。
            $table->unsignedBigInteger('legacy_message_file_id')->nullable();

            $table->timestamps();

            $table->index('flow_record_id', 'frf_record_idx');
            $table->index('flow_definition_id', 'frf_definition_idx');
            $table->index(['status', 'created_at'], 'frf_status_created_idx');
            $table->index('legacy_message_file_id', 'frf_legacy_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_record_files');
    }
};
