<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 取引先マスタ。freee会計の取引先と1対1で対応する。
 *
 * freee_partner_id の有無がそのまま連携状態（部門連携と同じ約束）。
 */
class PartnerRecord extends Model
{
    use SoftDeletes;

    /**
     * freeeから取り込む項目。差分の比較にも使う。
     * ここに無い項目（corporate_number / website / note）はこちら側だけの情報。
     */
    public const FREEE_PULL_FIELDS = [
        'name',
        'name_kana',
        'long_name',
        'code',
        'invoice_registration_number',
        'postal_code',
        'prefecture_code',
        'address_1',
        'address_2',
        'phone',
        'contact_name',
        'email',
        'available',
    ];

    /**
     * freeeへ書き戻す項目。
     * `available` は入っていない——freeeの取引先の作成・更新パラメータに無く、取り込み専用。
     */
    public const FREEE_PUSH_FIELDS = [
        'name',
        'name_kana',
        'long_name',
        'code',
        'invoice_registration_number',
        'postal_code',
        'prefecture_code',
        'address_1',
        'address_2',
        'phone',
        'contact_name',
        'email',
    ];

    protected $guarded = [];

    protected $casts = [
        'available' => 'boolean',
        'prefecture_code' => 'integer',
        'freee_partner_id' => 'integer',
        'freee_synced_at' => 'datetime',
        'freee_update_date' => 'date',
        'freee_snapshot' => 'array',
        'information_security_answers' => 'array',
        'labor_contract_answers' => 'array',
    ];

    /** 区分。表示名ではなくこのキーを保存する。 */
    public const ENTITY_TYPES = ['corporate', 'individual'];

    /** 取引区分。 */
    public const TRANSACTION_CATEGORIES = [
        'client',
        'partner',
        'property_vehicle_parking',
        'payable',
        'other',
    ];

    /** ヒアリング回答のキー。設問の並びが変わっても回答がずれないよう、添字ではなくキーで持つ。 */
    public const INFO_SECURITY_KEY_PATTERN = '/^is_\d{2}$/';

    public const LABOR_CONTRACT_KEY_PATTERN = '/^lc_\d{2}$/';

    /** スナップショットは同期の内部状態。画面へは出さない。 */
    protected $hidden = ['freee_snapshot'];

    protected $appends = ['freee_linked', 'has_unsynced_changes'];

    public function projects()
    {
        return $this->belongsToMany(ProjectRecord::class, 'project_partners', 'partner_record_id', 'project_record_id')
            ->withTimestamps();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->select('id', 'name', 'icon_path', 'icon_bg');
    }

    /** freeeと紐付いているか。 */
    public function isFreeeLinked(): bool
    {
        return filled($this->freee_partner_id);
    }

    public function getFreeeLinkedAttribute(): bool
    {
        return $this->isFreeeLinked();
    }

    /**
     * freeeへ未反映の編集があるか。
     *
     * 前回同期時点のfreeeの値（freee_snapshot）と今の値を比べる。updated_at では判定できない
     * ——同期そのものが updated_at を動かすうえ、freee側の変更を取り込んだ場合も動くため。
     * 未連携の行では常に false。
     */
    public function getHasUnsyncedChangesAttribute(): bool
    {
        if (! $this->isFreeeLinked()) {
            return false;
        }

        $snapshot = $this->freee_snapshot;

        if (! is_array($snapshot)) {
            return false;
        }

        foreach (self::FREEE_PUSH_FIELDS as $field) {
            $local = $this->attributes[$field] ?? null;
            $base = $snapshot[$field] ?? null;

            if (trim((string) $local) !== trim((string) $base)) {
                return true;
            }
        }

        return false;
    }

    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        $keyword = trim((string) $keyword);

        if ($keyword === '') {
            return $query;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $keyword).'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('name', 'like', $like)
                ->orWhere('name_kana', 'like', $like)
                ->orWhere('long_name', 'like', $like)
                ->orWhere('code', 'like', $like)
                ->orWhere('corporate_number', 'like', $like)
                ->orWhere('invoice_registration_number', 'like', $like)
                ->orWhere('contact_name', 'like', $like)
                ->orWhere('contact_position', 'like', $like)
                ->orWhere('phone', 'like', $like)
                ->orWhere('email', 'like', $like);
        });
    }
}
