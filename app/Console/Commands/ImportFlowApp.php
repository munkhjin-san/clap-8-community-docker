<?php

namespace App\Console\Commands;

use App\Models\FlowAppTool;
use App\Models\FlowDefinition;
use App\Models\FlowField;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * flow:export-app が書いたJSONから、カスタムアプリを1つ作る。レコードは作らない。
 *
 * レコードは別で入れる（取引先なら kintone から flow:import-kintone-records）。
 * 設定とデータを分けるのは、設定は「移すもの」でデータは「その時点の最新を取ってくるもの」だから。
 *
 * 書き出しがフィールドキーで書かれているので、ここでキー→新しいIDに引き直す。
 *
 *   php artisan flow:import-app database/flow_apps/torihikisaki.json --dry-run
 *   php artisan flow:import-app database/flow_apps/torihikisaki.json
 */
class ImportFlowApp extends Command
{
    protected $signature = 'flow:import-app
        {file : flow:export-app が書き出したJSON}
        {--name= : アプリ名を変えて作る}
        {--created-by= : 作成者のユーザーID（省略時は最初の管理者）}
        {--dry-run : 作らずに、何が作られるかだけ表示する}';

    protected $description = 'JSONからカスタムアプリの設定を取り込む（レコードは含まない）';

    public function handle(): int
    {
        $file = (string) $this->argument('file');
        if (! is_file($file)) {
            $this->error("ファイルがありません: {$file}");

            return self::FAILURE;
        }

        $data = json_decode((string) file_get_contents($file), true);
        if (! is_array($data)) {
            $this->error('JSONとして読めませんでした。');

            return self::FAILURE;
        }
        if (($data['format'] ?? 0) !== ExportFlowApp::FORMAT) {
            $this->error('この形式は読めません（format='.json_encode($data['format'] ?? null).'）。書き出し側と揃えてください。');

            return self::FAILURE;
        }

        $name = $this->option('name') ?: ($data['app']['name'] ?? '取り込んだアプリ');

        $this->info('=== 取り込む内容 ===');
        $this->line("  アプリ名 : {$name}");
        $this->line(sprintf(
            '  項目 %d / ビュー %d / 権限 %d / ステータス %d / ツール %d',
            count($data['fields'] ?? []), count($data['views'] ?? []),
            count($data['permissions'] ?? []), count($data['statuses'] ?? []), count($data['tools'] ?? [])
        ));

        if (FlowDefinition::where('name', $name)->exists()) {
            $this->warn('同じ名前のアプリが既にあります。別のアプリとして作られます（--name で名前を変えられます）。');
        }

        if ($this->option('dry-run')) {
            $this->comment('--dry-run のため何も作っていません。');

            return self::SUCCESS;
        }

        $definitionId = DB::transaction(function () use ($data, $name) {
            $definition = FlowDefinition::create([
                'name' => $name,
                'description' => $data['app']['description'] ?? null,
                'color_id' => $data['app']['color_id'] ?? null,
                'icon_svg' => $data['app']['icon_svg'] ?? null,
                'icon_image' => $data['app']['icon_image'] ?? null,
                'visibility' => $data['app']['visibility'] ?? 'private',
                'is_active' => $data['app']['is_active'] ?? true,
                'use_status_flow' => $data['app']['use_status_flow'] ?? false,
                'created_by' => $this->creatorId(),
            ]);

            $idByKey = $this->createFields($definition, $data['fields'] ?? []);
            $this->createViews($definition, $data['views'] ?? [], $idByKey);
            $this->createPermissions($definition, $data['permissions'] ?? []);
            $this->createStatuses($definition, $data['statuses'] ?? []);
            $this->createTools($definition, $data['tools'] ?? []);

            return $definition->id;
        });

        $this->newLine();
        $this->info("できました: カスタムアプリ #{$definitionId}");
        $this->line('  レコードは入っていません。取引先であれば次で取り込みます:');
        $this->line("    php artisan flow:import-kintone-records 118 --definition={$definitionId} --number-field=取引先id");

        return self::SUCCESS;
    }

    /** @return array<string, int> フィールドキー => 新しいID */
    private function createFields(FlowDefinition $definition, array $fields): array
    {
        $idByKey = [];
        foreach ($fields as $row) {
            $row['flow_definition_id'] = $definition->id;
            unset($row['id']);
            $field = FlowField::create($row);
            $idByKey[$field->key] = $field->id;
        }
        $this->line('  項目を作成: '.count($idByKey));

        return $idByKey;
    }

    /**
     * ビュー。キーを新しいIDに引き直す。
     *
     * 引けない項目を含むビューは**作らない**。列が1つ欠けただけの一覧を黙って出すと、
     * 「なぜかこの列が無い」を後から誰も追えなくなる。
     *
     * @param  array<string, int>  $idByKey
     */
    private function createViews(FlowDefinition $definition, array $views, array $idByKey): void
    {
        $made = 0;
        foreach ($views as $v) {
            $missing = [];
            $toId = function ($key) use ($idByKey, &$missing) {
                if (is_string($key) && str_starts_with($key, '$')) {
                    return $key;
                }
                if (! isset($idByKey[$key])) {
                    $missing[] = $key;

                    return null;
                }

                return $idByKey[$key];
            };

            $columns = array_map($toId, (array) ($v['columns'] ?? []));
            $filters = [];
            foreach ((array) ($v['filters'] ?? []) as $f) {
                $filters[] = ['field' => $toId($f['field'] ?? null)] + array_diff_key($f, ['field' => null]);
            }
            $sort = [];
            foreach ((array) ($v['sort'] ?? []) as $s) {
                $sort[] = ['field' => $toId($s['field'] ?? null), 'direction' => $s['direction'] ?? 'asc'];
            }

            if ($missing !== []) {
                $this->warn("ビュー「{$v['name']}」は作りませんでした（項目が見つかりません: ".implode('、', array_unique($missing)).'）');

                continue;
            }

            $definition->views()->create([
                'name' => $v['name'],
                'view_mode' => $v['view_mode'] ?? 'table',
                'is_default' => (bool) ($v['is_default'] ?? false),
                'columns' => $columns,
                'filters' => $filters,
                'filter_logic' => $v['filter_logic'] ?? 'and',
                'sort' => $sort,
                'created_by' => $this->creatorId(),
            ]);
            $made++;
        }
        $this->line('  ビューを作成: '.$made);
    }

    private function createPermissions(FlowDefinition $definition, array $permissions): void
    {
        foreach ($permissions as $p) {
            unset($p['id']);
            $definition->appPermissions()->create($p);
        }
        $this->line('  権限を作成: '.count($permissions));
    }

    private function createStatuses(FlowDefinition $definition, array $statuses): void
    {
        foreach ($statuses as $s) {
            unset($s['id']);
            $definition->statuses()->create($s);
        }
        if ($statuses !== []) {
            $this->line('  ステータスを作成: '.count($statuses));
        }
    }

    /** ツール。下敷きPDFは、このアプリのIDで置き直してから設定に書く。 */
    private function createTools(FlowDefinition $definition, array $tools): void
    {
        foreach ($tools as $t) {
            $config = $t['config'] ?? [];

            if (! empty($t['background_pdf'])) {
                $bytes = base64_decode((string) $t['background_pdf'], true);
                if ($bytes === false || $bytes === '') {
                    $this->warn("ツール「{$t['name']}」の下敷きPDFを復元できませんでした。下敷き無しにします。");
                    unset($config['background']);
                } else {
                    $path = 'flow_tool_backgrounds/'.$definition->id.'/'.sha1($bytes).'.pdf';
                    Storage::disk('local')->put($path, $bytes);
                    $config['background']['path'] = $path;
                }
            } elseif (isset($config['background'])) {
                // 実体が同梱されていない＝書き出し時点で既に無かった
                unset($config['background']);
            }

            FlowAppTool::create([
                'flow_definition_id' => $definition->id,
                'tool_type' => $t['tool_type'],
                'name' => $t['name'],
                'is_active' => (bool) ($t['is_active'] ?? true),
                'sort_order' => (int) ($t['sort_order'] ?? 0),
                'config' => $config,
            ]);
        }
        if ($tools !== []) {
            $this->line('  ツールを作成: '.count($tools));
        }
    }

    private function creatorId(): ?int
    {
        static $id = null;
        if ($id !== null) {
            return $id;
        }

        return $id = (int) ($this->option('created-by') ?: (DB::table('users')->whereNull('deleted_at')->min('id') ?? 0)) ?: null;
    }
}
