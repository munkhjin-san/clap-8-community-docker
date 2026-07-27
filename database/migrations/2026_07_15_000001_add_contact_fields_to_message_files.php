<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_files', function (Blueprint $table) {
            // Contact-level files (not tied to a comment): 裏面 photos + attachments.
            // kind = 'image' (裏面/写真) | 'file' (ドキュメント・添付ファイル).
            $table->unsignedBigInteger('contact_record_id')->nullable()->index()->after('comment_record_id');
            $table->string('contact_file_kind', 20)->nullable()->after('contact_record_id');
        });
    }

    public function down(): void
    {
        Schema::table('message_files', function (Blueprint $table) {
            $table->dropColumn(['contact_record_id', 'contact_file_kind']);
        });
    }
};
