<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * カスタムアプリのファイル項目1件分。実体の場所と持ち主を知っている唯一の場所。
 *
 * value_json 側にも表示用の情報（名前・拡張子・サイズ）を複製しているが、正しいのは常にこちら。
 * URLは保存しない（保存すると保管場所を変えるたびにデータ移行が必要になる）——読み出し時に組む。
 */
class FlowRecordFile extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ATTACHED = 'attached';

    /** 台帳はあるが実体が無い。旧 temp_upload 運用で7日で消えた分がここに入る。 */
    public const STATUS_MISSING = 'missing';

    protected $guarded = [];

    protected $casts = [
        'flow_definition_id' => 'int',
        'flow_record_id' => 'int',
        'flow_field_id' => 'int',
        'size' => 'int',
        'uploaded_by' => 'int',
        'legacy_message_file_id' => 'int',
    ];

    public function record(): BelongsTo
    {
        return $this->belongsTo(FlowRecord::class, 'flow_record_id');
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(FlowDefinition::class, 'flow_definition_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(FlowField::class, 'flow_field_id');
    }

    /**
     * レコードに結び付いたファイルの保存先。
     *
     * 1000件ごとの中間フォルダを挟むのが要点。`{app}/{record}` だけだと、レコードが増えた
     * アプリの下に何十万もの兄弟フォルダが並び、ls・バックアップ・rsync がどれも重くなる。
     * レコード単位でまとまっているので、レコード削除は1回の deleteDirectory で済む。
     */
    public static function pathFor(int $definitionId, int $recordId, int $fileId, ?string $extension): string
    {
        $bucket = intdiv($recordId, 1000) * 1000;
        $ext = self::safeExtension($extension);

        return "flow_files/{$definitionId}/{$bucket}/{$recordId}/{$fileId}".($ext === '' ? '' : ".{$ext}");
    }

    /** レコード未保存の置き場。pending は期限切れで掃除されるので枝分かれは増えない。 */
    public static function pendingPathFor(int $definitionId, int $fileId, ?string $extension): string
    {
        $ext = self::safeExtension($extension);

        return "flow_files/{$definitionId}/_pending/{$fileId}".($ext === '' ? '' : ".{$ext}");
    }

    /** レコード1件分のフォルダ（削除時にまとめて消すため）。 */
    public static function recordDirFor(int $definitionId, int $recordId): string
    {
        $bucket = intdiv($recordId, 1000) * 1000;

        return "flow_files/{$definitionId}/{$bucket}/{$recordId}";
    }

    /**
     * 拡張子はパスに入る値なので、ここで必ず絞る。元のファイル名は disk には使わない
     * （日本語・記号・長さ・大文字小文字の差でパスが壊れるのを避ける）。
     */
    private static function safeExtension(?string $extension): string
    {
        $ext = strtolower(trim((string) $extension));
        $ext = preg_replace('/[^a-z0-9]/', '', $ext) ?? '';

        return substr($ext, 0, 16);
    }

    /** 配信は必ずこのルート経由（権限を見てから流す）。 */
    public function url(): string
    {
        return "/flow_file/{$this->id}";
    }

    /** value_json に入れる形。URLは含めない（読み出し時に付ける）。 */
    public function valuePayload(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'extension' => $this->extension,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'user_id' => $this->uploaded_by,
        ];
    }

    /** 画面に返す形（URLと状態つき）。 */
    public function apiPayload(): array
    {
        return $this->valuePayload() + [
            'url' => $this->url(),
            'status' => $this->status,
        ];
    }
}
