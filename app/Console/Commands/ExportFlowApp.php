<?php

namespace App\Console\Commands;

use App\Models\FlowDefinition;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * カスタムアプリの「設定」を1つのJSONに書き出す。レコードは入らない。
 *
 * kintoneからの取り込みコマンドでは足りないから要る：取り込みが作るのは7月時点の素の形で、
 * その後に手で直したもの——ラベルの文言、列幅、見出しの並べ替え、テーブル列の型変更——は
 * 再現されない。取り込み直すと、その手作業が消える。
 *
 * **IDではなくフィールドキーで書く。** ビューの列も絞り込みも並び順もDB上は項目IDで持っているが、
 * IDは環境ごとに違う。キーに直して書き出し、取り込み側で引き直す。
 *
 *   php artisan flow:export-app 49 --out=database/flow_apps/torihikisaki.json
 */
class ExportFlowApp extends Command
{
    protected $signature = 'flow:export-app
        {definition : 書き出すカスタムアプリのID}
        {--out= : 出力先（省略時は storage/app/flow_exports/ に置く）}';

    protected $description = 'カスタムアプリの設定（項目・ビュー・権限・ツール）をJSONに書き出す';

    /** この形式のバージョン。取り込み側が読めるかを判断する。 */
    public const FORMAT = 1;

    public function handle(): int
    {
        $definition = FlowDefinition::with(['fields', 'views', 'appPermissions', 'tools', 'statuses'])
            ->find($this->argument('definition'));

        if (! $definition) {
            $this->error('そのアプリがありません。');

            return self::FAILURE;
        }

        $keyById = $definition->fields->pluck('key', 'id')->all();

        $payload = [
            'format' => self::FORMAT,
            'source' => ['definition_id' => $definition->id, 'name' => $definition->name],
            'app' => [
                'name' => $definition->name,
                'description' => $definition->description,
                'color_id' => $definition->color_id,
                'icon_svg' => $definition->icon_svg,
                'icon_image' => $definition->icon_image,
                'visibility' => $definition->visibility,
                'is_active' => (bool) $definition->is_active,
                'use_status_flow' => (bool) $definition->use_status_flow,
            ],
            'fields' => $this->fields($definition),
            'views' => $this->views($definition, $keyById),
            'permissions' => $this->permissions($definition),
            'statuses' => $this->statuses($definition),
            'tools' => $this->tools($definition),
        ];

        $out = $this->option('out') ?: storage_path(
            'app/flow_exports/'.preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $definition->name).'.json'
        );
        if (! is_dir(dirname($out))) {
            mkdir(dirname($out), 0755, true);
        }
        file_put_contents($out, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        $this->info('書き出しました: '.$out.'（'.number_format(filesize($out) / 1024, 1).' KB）');
        $this->line(sprintf(
            '  項目 %d / ビュー %d / 権限 %d / ステータス %d / ツール %d',
            count($payload['fields']), count($payload['views']),
            count($payload['permissions']), count($payload['statuses']), count($payload['tools'])
        ));

        return self::SUCCESS;
    }

    /**
     * 項目。IDと所属アプリだけ落として、あとはそのまま。
     *
     * 数式は `[ラベル]` で書かれているのでそのまま持ち運べる（IDを含まない）。
     *
     * 関連レコードは**外す**：子アプリをIDで指しているので、同じ子アプリが向こうに無ければ
     * 意味を持てない。黙って別のアプリを指すより、無い状態で運ぶ方が安全。
     */
    private function fields(FlowDefinition $definition): array
    {
        $out = [];
        foreach ($definition->fields as $f) {
            if ($f->input_type === 'related') {
                $this->warn("関連レコード「{$f->label}」は書き出しません（子アプリIDは環境をまたげません）。移行先で作り直してください。");

                continue;
            }
            $out[] = $this->plain($f);
        }

        return $out;
    }

    /**
     * ビュー。列・絞り込み・並び順の項目IDをキーに直す。
     *
     * `$record_number` のようなシステム列は文字列のまま（IDではないので触らない）。
     */
    private function views(FlowDefinition $definition, array $keyById): array
    {
        $toKey = function ($ref) use ($keyById) {
            if (is_string($ref) && str_starts_with($ref, '$')) {
                return $ref;
            }

            return $keyById[(int) $ref] ?? null;
        };

        $out = [];
        foreach ($definition->views as $v) {
            $columns = array_values(array_filter(array_map($toKey, (array) $v->columns), fn ($k) => $k !== null));

            $filters = [];
            foreach ((array) $v->filters as $f) {
                $key = $toKey($f['field'] ?? null);
                if ($key === null) {
                    $this->warn("ビュー「{$v->name}」の絞り込みが、既に無い項目を指しています。その条件は外しました。");

                    continue;
                }
                $filters[] = ['field' => $key] + array_diff_key((array) $f, ['field' => null]);
            }

            $sort = [];
            foreach ((array) $v->sort as $s) {
                $key = $toKey($s['field'] ?? null);
                if ($key !== null) {
                    $sort[] = ['field' => $key, 'direction' => $s['direction'] ?? 'asc'];
                }
            }

            $out[] = [
                'name' => $v->name,
                'view_mode' => $v->view_mode,
                'is_default' => (bool) $v->is_default,
                'columns' => $columns,
                'filters' => $filters,
                'filter_logic' => $v->filter_logic,
                'sort' => $sort,
            ];
        }

        return $out;
    }

    /**
     * 権限。ユーザーIDや役職IDは環境をまたげないので、そのまま運ぶ場合は必ず知らせる。
     * `creator`（作成者）のように相手を持たない種類はそのまま使える。
     */
    private function permissions(FlowDefinition $definition): array
    {
        $out = [];
        foreach ($definition->appPermissions as $p) {
            if ($p->subject_id !== null) {
                $this->warn("権限「{$p->subject_type} #{$p->subject_id}」は相手をIDで指しています。移行先で付け直してください。");

                continue;
            }
            $out[] = $this->plain($p);
        }

        return $out;
    }

    /**
     * 1行分を、そのまま書き戻せる形にする。
     *
     * getAttributes() は**DBの生の値**を返すので、JSONにキャストしている列（validation など）は
     * 文字列のまま出てくる。それを取り込み側に渡すと、キャストがもう一度JSON化して
     * `"[]"` のような二重の値になる。キャストを通した値を使うことでそれを避ける。
     */
    private function plain($model): array
    {
        $row = $model->getAttributes();
        unset($row['id'], $row['flow_definition_id'], $row['created_at'], $row['updated_at']);

        $casts = $model->getCasts();
        foreach ($row as $k => $v) {
            if (in_array($casts[$k] ?? '', ['array', 'json', 'object', 'collection'], true)) {
                $row[$k] = $model->getAttribute($k);
            }
        }

        return $row;
    }

    private function statuses(FlowDefinition $definition): array
    {
        return $definition->statuses->map(fn ($s) => $this->plain($s))->all();
    }

    /**
     * ツール。PDF帳票の中身は項目キーで書かれているのでそのまま運べるが、
     * 下敷きのPDFだけは実体なので、JSONに同梱する（1ファイルで持ち運べる方が事故が少ない）。
     */
    private function tools(FlowDefinition $definition): array
    {
        $out = [];
        foreach ($definition->tools as $t) {
            $row = [
                'tool_type' => $t->tool_type,
                'name' => $t->name,
                'is_active' => (bool) $t->is_active,
                'sort_order' => (int) $t->sort_order,
                'config' => $t->config ?? [],
            ];

            $bgPath = $row['config']['background']['path'] ?? null;
            if ($bgPath && Storage::disk('local')->exists($bgPath)) {
                $row['background_pdf'] = base64_encode(Storage::disk('local')->get($bgPath));
            } elseif ($bgPath) {
                $this->warn("ツール「{$t->name}」の下敷きPDFが見つかりません（{$bgPath}）。下敷き無しで書き出します。");
                unset($row['config']['background']);
            }

            $out[] = $row;
        }

        return $out;
    }
}
