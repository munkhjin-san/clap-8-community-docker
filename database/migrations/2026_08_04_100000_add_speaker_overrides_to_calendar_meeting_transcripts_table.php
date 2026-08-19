<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 文字起こしの話者名の手直しを保存する。
 * Zoom から来た VTT 本体はいじらず、表示時に上書きするだけなので元に戻せる。
 *
 *   { "all": { "<VTTの元の名前>": "<直した名前>" }, "cues": { "<cue番号>": "<直した名前>" } }
 *
 * all  = この文字起こし内の同名すべて
 * cues = その行だけ（all より優先）
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_meeting_transcripts', function (Blueprint $table) {
            $table->json('speaker_overrides')->nullable()->after('storage_path');
        });
    }

    public function down(): void
    {
        Schema::table('calendar_meeting_transcripts', function (Blueprint $table) {
            $table->dropColumn('speaker_overrides');
        });
    }
};
