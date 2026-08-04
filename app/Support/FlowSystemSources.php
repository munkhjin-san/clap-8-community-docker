<?php

namespace App\Support;

use App\Models\officeRecord;
use App\Models\ProjectRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Built-in ("system") reference sources for Flow lookup fields.
 *
 * A reference field normally points at another Flow app (target_definition_id) and pulls values out
 * of its flow_records. A system source lets it point at a real master table instead (offices, …) so
 * that master stays the single source of truth — no parallel Flow app to keep in sync. The reference
 * endpoints and the field inspector branch on `validation.target_source`; everything downstream (the
 * {id, number, label} snapshot, field-copy mapping, type compatibility) is reused unchanged.
 *
 * To add a source: add one entry here. Columns are exposed to the inspector as text pseudo-fields
 * (label choices + field-copy sources); values map into real fields under the existing scalar→text rule.
 *
 * NOTE: system sources have no per-record/field permission model. Only expose masters whose rows are
 * broadly readable in the product (office name/address/tel already appear across pickers/lists).
 *
 * `pickable => false` registers a source for its *columns* without offering it as a 参照先. That is how
 * ユーザー and プロジェクト get here: they are not linked with a 参照 field (the app already has ユーザー and
 * プロジェクト field types with their own pickers) — they exist so those fields can auto-fill from the
 * master, reusing this file's column allowlist and /flow_system_record's resolver untouched.
 */
class FlowSystemSources
{
    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        return [
            'office' => [
                'label' => '営業所',
                'model' => officeRecord::class,
                // columns the search box matches (LIKE %q%)
                'search' => ['name', 'address'],
                // default label column when the field hasn't chosen one
                'label_column' => 'name',
                // exposed as pseudo-fields (label choices + field-copy sources); all treated as text
                'columns' => [
                    ['key' => 'name', 'label' => '営業所名'],
                    ['key' => 'post_code', 'label' => '郵便番号'],
                    ['key' => 'address', 'label' => '住所'],
                    ['key' => 'tel', 'label' => 'TEL'],
                    ['key' => 'fax', 'label' => 'FAX'],
                ],
                // optional base filter (exclude the legacy soft-delete flag; SoftDeletes already hides deleted_at)
                'filter' => fn (Builder $q) => $q->where('deleted_flag', 0),
                // per-column value resolver (default: the column attribute). Handles composite columns
                // like the split post code.
                'value' => fn ($m, string $key) => match ($key) {
                    'post_code' => self::joinPostCode($m->post_code_1 ?? null, $m->post_code_2 ?? null),
                    default => $m->{$key} ?? null,
                },
            ],

            /*
             * ユーザー field auto-fill. Not pickable: you do not link a person with a 参照 field, you use
             * the ユーザー field and this fills other fields from whoever is selected.
             *
             * Allowlist, deliberately narrow. The users table also holds password, google_token, email
             * (the login address), phone_number and the profile free-text — none of which belong in an
             * app a colleague can build. joined_date and general_position are left out for a duller
             * reason: they are empty or near-empty in practice.
             *
             * No retire filter. A record can legitimately still name someone who has since left, and
             * refusing to resolve them would turn an old record's 所属 into a blank — the same call
             * userNameMap() already makes.
             */
            'user' => [
                'label' => 'ユーザー',
                'pickable' => false,
                'model' => User::class,
                'search' => ['name', 'name_kana'],
                'label_column' => 'name',
                'columns' => [
                    ['key' => 'name', 'label' => '氏名'],
                    ['key' => 'name_kana', 'label' => 'フリガナ'],
                    ['key' => 'user_code', 'label' => '社員番号'],
                    ['key' => 'position', 'label' => '役職'],
                    ['key' => 'office', 'label' => '営業所'],
                    ['key' => 'work_email', 'label' => 'メールアドレス'],

                    /*
                     * 振込口座（employee_bank_accounts）。管理画面で管理者のみが登録する情報を、
                     * ユーザーを選ぶだけでフォームに転記できるようにするためのもの。
                     *
                     * 番号だけ input_type を 'password' にしている。コピー先の型チェックは非スカラーに
                     * ついて厳密同型なので、これにより番号は暗号化フィールドにしかマッピングできない。
                     * 平文の短文フィールドに落として flow_record_values に生で残ることを、設定の時点で
                     * 不可能にするのが目的（運用の注意書きではなく仕組みで防ぐ）。
                     */
                    ['key' => 'bank_holder', 'label' => '口座名義人'],
                    ['key' => 'bank_name', 'label' => '金融機関名'],
                    ['key' => 'bank_branch', 'label' => '支店名'],
                    ['key' => 'bank_holder_kana', 'label' => '口座名義人（フリガナ）'],
                    ['key' => 'bank_number_masked', 'label' => '口座番号（下4桁）'],
                    ['key' => 'bank_number', 'label' => '口座番号', 'input_type' => 'password'],
                ],
                'value' => fn ($m, string $key) => match ($key) {
                    'position' => $m->positions?->name,
                    'office' => $m->offices?->name,
                    // 口座は1人1件。未登録なら null で、コピー先はそのまま残る
                    'bank_holder' => $m->bankAccount?->account_holder,
                    'bank_name' => $m->bankAccount?->bank_name,
                    'bank_branch' => $m->bankAccount?->branch_name,
                    'bank_holder_kana' => $m->bankAccount?->account_holder_kana,
                    'bank_number_masked' => $m->bankAccount?->maskedNumber(),
                    // 復号された平文。サーバのメモリ内だけで、この直後に
                    // syncFieldValues が AccountVault で再暗号化してレコードへ書く
                    'bank_number' => $m->bankAccount?->account_number,
                    default => $m->{$key} ?? null,
                },
            ],

            /*
             * プロジェクト field auto-fill. Same deal as ユーザー — registered for its columns only.
             *
             * Allowlist again: project_records also carries private_memo, budget and the strategy
             * free-text (kgi/kpi/miso/mission/…), which are not for a form to copy around. date_start
             * and date_end are omitted because they are empty for every row, and `status` because it
             * reads 'running' for every row — both would just be dead options in the picker.
             */
            'project' => [
                'label' => 'プロジェクト',
                'pickable' => false,
                'model' => ProjectRecord::class,
                'search' => ['name'],
                'label_column' => 'name',
                'columns' => [
                    ['key' => 'name', 'label' => 'プロジェクト名'],
                    ['key' => 'customers', 'label' => '得意先'],
                    ['key' => 'partners', 'label' => '協力会社'],
                    ['key' => 'project_type', 'label' => '種別'],
                    ['key' => 'category', 'label' => 'カテゴリ'],
                    ['key' => 'industry_type', 'label' => '業種'],
                    ['key' => 'director', 'label' => '責任者'],
                    // freee会計の部門ID。名前はローカルに持っていないので ID そのまま（連携済みの
                    // プロジェクトだけ値が入る — 未連携なら空欄になる）。
                    ['key' => 'freee_section_id', 'label' => 'freee部門ID'],
                ],
                'value' => fn ($m, string $key) => match ($key) {
                    // project_types names its column `label`, not `name`
                    'project_type' => $m->projectType?->label,
                    'director' => $m->director?->name,
                    // these four are `array` casts on ProjectRecord. Handed over raw they would land in
                    // a text field as an encoded array, and an empty one is stored as [null], which
                    // stringifies to the literal "null" — join and drop the blanks instead.
                    'customers', 'partners', 'category', 'industry_type' => self::joinList($m->{$key}),
                    default => $m->{$key} ?? null,
                },
            ],
        ];
    }

    public static function get(string $key): ?array
    {
        return static::all()[$key] ?? null;
    }

    /**
     * {key,label} list for the inspector's 参照先 picker.
     *
     * Only pickable sources. The single gate that keeps ユーザー / プロジェクト out of the 参照 target list
     * while leaving their columns available to /flow_system_fields and /flow_system_record.
     */
    public static function options(): array
    {
        return collect(static::all())
            ->filter(fn ($s) => ($s['pickable'] ?? true) !== false)
            ->map(fn ($s, $k) => ['key' => $k, 'label' => $s['label']])
            ->values()
            ->all();
    }

    /**
     * An `array`-cast column as one readable string for a text destination.
     *
     * Returns null rather than '' when nothing survives, so an empty 得意先 leaves the destination alone
     * instead of writing a blank over whatever was already typed there.
     */
    private static function joinList($v): ?string
    {
        if (! is_array($v)) {
            return $v === null || $v === '' ? null : (string) $v;
        }
        $parts = array_filter(array_map(fn ($x) => trim((string) ($x ?? '')), $v), fn ($x) => $x !== '');

        return $parts ? implode('、', $parts) : null;
    }

    private static function joinPostCode($a, $b): string
    {
        $a = (string) ($a ?? '');
        $b = (string) ($b ?? '');

        return trim($a !== '' && $b !== '' ? "{$a}-{$b}" : $a.$b);
    }
}
