<?php

namespace App\Console\Commands;

use App\Infrastructure\Kintone\KintoneClient;
use App\Models\FlowDefinition;
use App\Services\KintoneImportService;
use App\Support\FlowRichText;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * kintoneアプリのフォームを、カスタムアプリ側に「見た目どおり」に作り直す。
 *
 * 画面の「kintoneから取込」との違いは2つ：
 *  - レイアウト（行・グループ・ラベル・罫線・スペース）も移す。あちらは fields.json だけを見るので
 *    装飾が全部落ち、項目が1行ずつ縦に並ぶ。
 *  - 既存アプリに対して作り直せる（あちらは新規作成時のみ）。
 *
 * レコードがあるアプリは既定で拒否する——項目を作り直すとIDが変わり、既存の値が持ち主を失うため。
 */
class ImportKintoneAppStructure extends Command
{
    protected $signature = 'flow:import-kintone-structure
        {kintone_app : kintone側のアプリID}
        {--definition= : 作り直すカスタムアプリのID（省略時は新規作成）}
        {--dry-run : 変更を書かず、作られるフォームを表示する}
        {--force : 確認せずに実行する（レコードがあっても作り直す。手を入れた設定は失われます）}
        {--owner= : 作成者にするユーザーID（省略時は最初のユーザー）}';

    protected $description = 'kintoneアプリのフォーム構成（レイアウト込み）をカスタムアプリに取り込む';

    /**
     * 取り込まない項目（kintoneアプリID => 指定）。
     *
     * 運用で「不要」と判断したものをここに置く。取込後に手で消しても、再取込すれば戻ってきて
     * しまうため、判断は取込側に残すのが正しい置き場所になる。
     *
     * - groups  … グループのコード。中の項目・ラベル・見出しを丸ごと除外する（「全部いらない」用）
     * - fields  … 個別の項目コード
     * - columns … テーブル内の列コード（kintoneのコードはアプリ内で一意なので平たいリストでよい）
     */
    private const EXCLUDE = [
        118 => [   // 取引先
            'groups' => [
                'グループ_1',   // 請求関係（締め日・請求書発行期日・MEMO の甲乙2組）
                '削除予定',     // 資本金・従業員数・設立年月日・必要書類・支払条件ほか
            ],
            'fields' => [
                '個別ﾗﾍﾞﾙ',           // 先頭の作業用テキスト
                '文字列__1行__3',      // 企業コード(自動採番)
                '文字列__1行__4',      // 企業コード
                // 文字列__1行__1（役職）は一度除外したが必要だったため戻した
                '取引先担当者_0',      // 正式名称グループ: 取引先担当者
                'リンク_0',            // 正式名称グループ: メールアドレス
                '文字列__1行__12',     // 正式名称グループ: 決裁者
                'リンク_1',            // 正式名称グループ: 決裁者メールアドレス
                'テーブル',            // 店舗情報（サブテーブル全部）
                'チェックボックス',    // 宛名シール
            ],
            'columns' => [
                '文字列__1行__11',     // 担当者テーブル › 決裁者
                'リンク_3',            // 担当者テーブル › メアド
            ],
        ],
        138 => [   // 契約書
            'groups' => [
                'グループ',      // 転籍の場合（中身6項目ごと）
                'グループ_1',    // 【別紙3】【別紙4】【別紙5】（中身は空）
            ],
            'fields' => [
                '担当者',
                '担当者メールアドレス',
                '決裁者',
                '決裁者メールアドレス',
                '添付ファイル',        // ファイル項目だが6件のみ・不要
                '指示書その他',
                '数値',                // 請求書提出期日
                '発送日',
                '文字列__1行__2',      // 出向者氏名
                '日付',                // 出向契約期間開始日
                '日付_0',              // 出向契約期間満了日
                '日付_1',              // 出向日前日
                // サブテーブルは3つとも入れない
                '委託業務責任者_他社',
                '副委託業務責任者',
                '委託業務責任者_自社',
            ],
            'columns' => [],
        ],
    ];

    public function handle(KintoneImportService $importer, KintoneClient $kintone): int
    {
        $appId = $this->argument('kintone_app');
        $dry = (bool) $this->option('dry-run');

        $app = $kintone->getApp($appId);
        $exclude = self::EXCLUDE[(int) $appId] ?? [];
        $plan = $importer->formPlan($appId, $exclude);
        $process = $this->processState($kintone, $appId);

        $this->info("kintone: {$app['name']} (app {$appId})");
        $this->line('  項目 '.count(array_filter($plan['fields'], fn ($f) => ! in_array($f['input_type'], ['heading', 'label', 'divider', 'spacer'], true)))
            .' / 装飾 '.count(array_filter($plan['fields'], fn ($f) => in_array($f['input_type'], ['heading', 'label', 'divider', 'spacer'], true)))
            .' / グループ '.$plan['groups']
            .' / 行 '.count(array_unique(array_column($plan['fields'], 'layout_row'))));
        $this->line('  プロセス管理: '.($process['enable'] ? '有効' : '無効').'（ステータス '.count($process['statuses']).'）');

        if ($plan['excluded'] !== []) {
            $this->newLine();
            $this->line('取り込まない指定（'.count($plan['excluded']).' 件）:');
            foreach ($plan['excluded'] as $e) {
                $kind = match ($e['kind']) {
                    'group' => 'グループ', 'column' => '列', 'spacer' => 'スペース', default => '項目'
                };
                $this->line("  - {$kind}  {$e['code']} / {$e['label']}");
            }
        }

        if ($plan['skipped'] !== []) {
            $this->newLine();
            $this->warn('取り込めない項目:');
            foreach ($plan['skipped'] as $s) {
                $this->line("  - {$s['type']}  {$s['code']} / {$s['label']} … {$s['reason']}");
            }
        }

        if ($plan['renamed'] !== []) {
            $this->newLine();
            $this->warn('ラベルが重複していたため連番を付けた項目（kintoneはラベル重複を許すがこちらは弾く）:');
            foreach ($plan['renamed'] as $r) {
                $this->line("  - {$r['key']}: 「{$r['from']}」→「{$r['to']}」");
            }
            $this->line('  区別しやすい名前に直したい場合は、取込後にフォーム画面で変更してください。');
        }

        $orphans = array_filter($plan['fields'], fn ($f) => $f['orphan'] ?? false);
        if ($orphans !== []) {
            $this->newLine();
            $this->warn('レイアウトに現れず末尾に付けた項目: '.implode('、', array_column($orphans, 'key')));
        }

        if ($dry) {
            $this->newLine();
            $this->renderForm($plan['fields']);
            $this->newLine();
            $this->info('--dry-run のため何も書き換えていません。');

            return self::SUCCESS;
        }

        $definition = $this->resolveDefinition($app, $appId);
        if (! $definition) {
            return self::FAILURE;
        }

        $records = $definition->records()->count();
        if ($records > 0 && ! $this->option('force')) {
            $this->error("カスタムアプリ #{$definition->id} には既に {$records} 件のレコードがあります。");
            $this->line('項目を作り直すとIDが変わり、既存の値が持ち主を失います。続けるなら --force を付けてください。');

            return self::FAILURE;
        }

        // 既存アプリの作り直しは、画面で手を入れた設定を丸ごと捨てる操作。レコードが0件でも
        // ラベルの文面・幅・並び・追加したラベルは戻らないので、既定では必ず確認する。
        // （非対話で走らせると confirm は false を返すので、うっかり流れることもない）
        if ($this->option('definition') && ! $this->option('force')) {
            $existing = $definition->fields()->count();
            $this->newLine();
            $this->warn("カスタムアプリ #{$definition->id}「{$definition->name}」の項目 {$existing} 件を作り直します。");
            $this->line('  画面で編集したラベル・幅・並び順、追加した項目は kintone の内容で置き換わります。');
            $this->line('  （関連レコードブロックだけは残ります）');
            if (! $this->confirm('続けますか？', false)) {
                $this->info('中止しました。');

                return self::SUCCESS;
            }
        }

        DB::transaction(function () use ($definition, $plan, $process) {
            $this->rebuild($definition, $plan, $process);
        });

        $this->newLine();
        $this->info("カスタムアプリ #{$definition->id}「{$definition->name}」のフォームを作り直しました。");
        $this->line('  /apps/builder/'.$definition->id.'/form で確認できます。');

        return self::SUCCESS;
    }

    /** 既存アプリを指定されたらそれを、無ければ新規作成する。 */
    private function resolveDefinition(array $app, int|string $appId): ?FlowDefinition
    {
        $id = $this->option('definition');
        if ($id) {
            $definition = FlowDefinition::find($id);
            if (! $definition) {
                $this->error("カスタムアプリ #{$id} が見つかりません。");

                return null;
            }
            $this->line("対象: カスタムアプリ #{$definition->id}「{$definition->name}」（作り直し）");

            return $definition;
        }

        // 作成者と「作成者は全権」の行を入れておく。これが無いと、作った直後のアプリは
        // 誰にも見えない（画面では「レコードが存在しませんまたはアクセス権限がありません」になる）。
        $owner = (int) ($this->option('owner') ?: (DB::table('users')->whereNull('deleted_at')->min('id') ?? 0)) ?: null;

        $definition = FlowDefinition::create([
            'name' => $app['name'] ?? ('kintone '.$appId),
            'description' => null,
            'visibility' => 'limited',
            'is_active' => true,
            'use_status_flow' => false,
            'record_seq' => 0,
            'created_by' => $owner,
        ]);
        $definition->appPermissions()->create([
            'subject_type' => 'creator',
            'subject_id' => null,
            'can_view' => true, 'can_add' => true, 'can_edit' => true, 'can_delete' => true,
            'can_manage' => true, 'can_import' => true, 'can_export' => true, 'can_bulk' => true,
            'sort_order' => 0,
        ]);
        $this->line("対象: カスタムアプリ #{$definition->id}（新規作成 / 作成者 #{$owner}）");

        return $definition;
    }

    /**
     * 項目を作り直す。
     *
     * 項目IDを参照しているもの（項目ごとの権限・ステータスの項目ルール・ビューの列や絞り込み）は
     * 一緒に片付ける。放っておくと消えたIDを指したままになる。
     */
    private function rebuild(FlowDefinition $definition, array $plan, array $process): void
    {
        // 「関連レコード」ブロックはkintone由来ではありえない（あちらの関連レコード一覧は取り込めない）。
        // こちらで足したものなので、作り直しで消さずに末尾へ残す。そうでないと再取込のたびに
        // 設定し直しになる。
        $keep = $definition->fields()->where('input_type', 'related')->get()
            ->map(fn ($f) => $f->only(['key', 'label', 'input_type', 'is_required', 'options', 'validation', 'width']))
            ->all();

        $oldIds = $definition->fields()->pluck('id');

        DB::table('flow_field_permissions')->where('flow_definition_id', $definition->id)->delete();
        if ($oldIds->isNotEmpty()) {
            DB::table('flow_status_field_rules')->whereIn('flow_field_id', $oldIds)->delete();
            // --force で既存レコードごと作り直した場合、古い項目IDを指す値が宙に浮く。
            // 消しておかないとどの項目にも属さない行がEAVに残り続ける。
            DB::table('flow_record_values')->whereIn('flow_field_id', $oldIds)->delete();
        }
        $definition->fields()->delete();

        $created = [];
        foreach ($plan['fields'] as $spec) {
            $created[] = $definition->fields()->create([
                'key' => $spec['key'],
                'label' => $spec['label'],
                'input_type' => $spec['input_type'],
                'is_required' => $spec['is_required'],
                'hidden' => 0,
                'options' => $spec['options'],
                'validation' => $spec['validation'],
                'width' => $spec['width'],
                'layout_row' => $spec['layout_row'],
                'order_number' => $spec['order_number'],
            ]);
        }

        // 残しておいた関連レコードブロックを末尾に戻す（行はkintone側の続き）
        $row = collect($plan['fields'])->max('layout_row') ?? -1;
        $order = count($plan['fields']);
        foreach ($keep as $spec) {
            $created[] = $definition->fields()->create($spec + [
                'hidden' => 0,
                'layout_row' => ++$row,
                'order_number' => $order++,
            ]);
        }
        if ($keep !== []) {
            $this->line('  関連レコードブロック '.count($keep).' 件はそのまま残しました（kintone由来ではないため）。');
        }

        $this->rebuildStatusFlow($definition, $process);

        // 列は項目IDで持っているので、作り直したら貼り替える（値を持つ項目だけ、先頭10列）
        $dataIds = collect($created)
            ->reject(fn ($f) => in_array($f->input_type, ['heading', 'label', 'divider', 'spacer'], true))
            ->pluck('id')
            ->take(10)
            ->values()
            ->all();

        foreach ($definition->views as $view) {
            $view->update(['columns' => $dataIds, 'filters' => [], 'sort' => []]);
        }
    }

    /** プロセス管理の状態。取れなければ「無効」として扱う。 */
    private function processState(KintoneClient $kintone, int|string $appId): array
    {
        try {
            $status = $kintone->getProcessManagement($appId);
        } catch (\Throwable $e) {
            return ['enable' => false, 'statuses' => [], 'actions' => []];
        }

        return [
            'enable' => (bool) ($status['enable'] ?? false),
            'statuses' => $status['states'] ?? [],
            'actions' => $status['actions'] ?? [],
        ];
    }

    /**
     * kintoneのプロセス管理を、こちらのステータスと遷移ボタンに写す。
     *
     * 状態の並びは kintone の index に従う（連想配列で返るので順序は当てにならない）。
     * index 0 を初期状態にする——kintoneでは新規レコードがそこから始まる。
     *
     * **作業者（assignee）は持ち越さない。** kintoneのアカウントを指しており、こちらの
     * ユーザーとは一致しない。eligible は空＝「編集できる人が押せる」にしておき、
     * 誰が押せるかは移行後に画面から決める。
     *
     * どの状態からも行けない状態（どのアクションの from にも to にも出てこない）も消さずに残す。
     * 使われていないように見えても、その状態のレコードが実在しうる。
     */
    private function rebuildStatusFlow(FlowDefinition $definition, array $process): void
    {
        $definition->statusActions()->delete();
        $definition->statuses()->delete();

        if (! $process['enable']) {
            // 画面からの取込はステータスが定義されていれば enable を見ずに有効化してしまうので、明示的に落とす
            $definition->update(['use_status_flow' => false]);

            return;
        }

        $states = collect($process['statuses'])
            ->map(fn ($s, $name) => ['name' => (string) ($s['name'] ?? $name), 'index' => (int) ($s['index'] ?? 0)])
            ->sortBy('index')
            ->values();

        $idByName = [];
        foreach ($states as $i => $st) {
            $row = $definition->statuses()->create([
                'name' => $st['name'],
                'is_initial' => $i === 0,
                'order_number' => $i,
            ]);
            $idByName[$st['name']] = $row->id;
        }

        $order = 0;
        $skipped = [];
        foreach ($process['actions'] as $a) {
            $from = $idByName[$a['from'] ?? ''] ?? null;
            $to = $idByName[$a['to'] ?? ''] ?? null;
            if ($from === null || $to === null) {
                $skipped[] = (string) ($a['name'] ?? '(名前なし)');

                continue;
            }
            $definition->statusActions()->create([
                'flow_status_id' => $from,
                'to_status_id' => $to,
                'name' => (string) ($a['name'] ?? ''),
                'label' => (string) ($a['name'] ?? ''),
                'eligible' => [],
                'notify' => true,
                'sort_order' => $order++,
            ]);
        }

        $definition->update(['use_status_flow' => true]);

        $this->line('  ステータス '.count($idByName).' 件 / 遷移ボタン '.$order.' 件を作りました。');
        if ($skipped !== []) {
            $this->warn('  遷移先が見つからないアクションは飛ばしました: '.implode('、', $skipped));
        }
        $this->line('  押せる人は未設定です（kintoneの作業者はこちらのユーザーと一致しないため）。');
    }

    /** 取り込まれるフォームを行ごとに表示する（--dry-run の中身）。 */
    private function renderForm(array $fields): void
    {
        $rows = [];
        foreach ($fields as $f) {
            $rows[$f['layout_row']][] = $f;
        }
        ksort($rows);

        $this->line('取り込まれるフォーム:');
        foreach ($rows as $cells) {
            $parts = [];
            foreach ($cells as $c) {
                $label = match ($c['input_type']) {
                    'heading' => '【'.$c['label'].'】',
                    'divider' => '────',
                    'spacer' => '(空白)',
                    // ラベルはHTMLを持つので、確認表示では素のテキストに直す
                    'label' => '“'.mb_strimwidth(str_replace("\n", ' ', FlowRichText::toPlainText($c['label'])), 0, 40, '…').'”',
                    'table' => '▦ '.$c['label'].'（'.count($c['validation']['columns'] ?? []).'列）',
                    default => $c['label'].' <'.$c['input_type'].'>',
                };
                $parts[] = $label;
            }
            $this->line('  '.implode('  |  ', $parts));
        }
    }
}
