<?php

namespace App\Support;

use App\Models\officeRecord;
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
 */
class FlowSystemSources
{
    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        return [
            'office' => [
                'label' => 'オフィス',
                'model' => officeRecord::class,
                // columns the search box matches (LIKE %q%)
                'search' => ['name', 'address'],
                // default label column when the field hasn't chosen one
                'label_column' => 'name',
                // exposed as pseudo-fields (label choices + field-copy sources); all treated as text
                'columns' => [
                    ['key' => 'name', 'label' => 'オフィス名'],
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
        ];
    }

    public static function get(string $key): ?array
    {
        return static::all()[$key] ?? null;
    }

    /** {key,label} list for the inspector's source picker. */
    public static function options(): array
    {
        return collect(static::all())
            ->map(fn ($s, $k) => ['key' => $k, 'label' => $s['label']])
            ->values()
            ->all();
    }

    private static function joinPostCode($a, $b): string
    {
        $a = (string) ($a ?? '');
        $b = (string) ($b ?? '');

        return trim($a !== '' && $b !== '' ? "{$a}-{$b}" : $a.$b);
    }
}
