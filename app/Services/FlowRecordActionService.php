<?php

namespace App\Services;

use App\Models\FlowAppTool;
use App\Models\FlowDefinition;
use App\Models\FlowRecord;
use App\Models\User;
use App\Support\FlowRecordActions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

/**
 * 「カスタムボタン」の実行系。
 *
 * ボタン1つ = flow_app_tools の1行（tool_type = 'action'）。設定に入るのは名前・色・押せる人と
 * 処理キーだけで、処理そのものは App\Support\FlowActions のクラス＝コードが持つ。
 *
 * 結果の書き戻しはここが引き受ける。ハンドラが返すのはフィールド「キー」→値で、実際の書き込みは
 * FlowService::saveFieldValue を直接呼ぶ——つまり保存エンドポイントの編集権限チェックを通らない。
 * これは意図的で、書き戻し先は「誰も編集できないフィールド」であってこそ意味がある
 * （freeeが発行したIDを利用者が書き換えられるなら、二重登録の抑止にならない）。
 */
class FlowRecordActionService
{
    public function __construct(private readonly FlowService $flow) {}

    /**
     * この利用者が押せるカスタムボタンを、状態付きで返す。
     * 押せない人には返さない（ステータスのボタンと同じで、押せないボタンは出さない）。
     */
    public function actionsFor(User $user, FlowRecord $record): array
    {
        $def = $record->definition;
        if (! $def) {
            return [];
        }

        $tools = $this->activeTools($def);
        if ($tools->isEmpty()) {
            return [];
        }

        $values = null;   // キー引きの値は必要になってから作る（ボタンが無いアプリでは作らない）
        $out = [];

        foreach ($tools as $tool) {
            $config = is_array($tool->config) ? $tool->config : [];
            if (! $this->flow->matchesAnySubject($user, $record, $config['eligible'] ?? null)) {
                continue;
            }
            $values ??= $this->valuesByKey($record, $def);
            $state = $this->state($tool, $def, $values);
            $class = FlowRecordActions::classFor($config['handler'] ?? null);

            $out[] = [
                'id' => $tool->id,
                'label' => $tool->name,
                'color' => $config['color'] ?? null,
                'status' => $state['status'],
                'reason' => $state['reason'],
                // 実行できるときだけ確認文を渡す（押せないボタンに確認は要らない）
                'confirm' => $state['status'] === 'ready' && $class ? $class::confirmMessage() : null,
            ];
        }

        return $out;
    }

    /**
     * ボタンを実行する。押せるかどうかは actionsFor と同じ規則で、ここでもう一度確かめる
     * （画面に出ていないボタンのIDを直接叩かれても通らない）。
     *
     * @return array{message: string, written: array<int, string>}
     */
    public function run(User $user, FlowRecord $record, int $toolId): array
    {
        $def = $record->definition;
        $tool = $this->activeTools($def)->firstWhere('id', $toolId);
        abort_unless($tool, 422, 'このボタンは利用できません。');

        $config = is_array($tool->config) ? $tool->config : [];
        abort_unless($this->flow->matchesAnySubject($user, $record, $config['eligible'] ?? null), 403);

        $handler = FlowRecordActions::resolve($config['handler'] ?? null);
        abort_unless($handler, 422, 'この処理は登録されていません。アプリ設定を確認してください。');

        // 二重押し・二重タブ対策。doneFieldKey の判定だけでは、同時に押された2本が両方とも
        // 「まだ未実行」を見てから外部APIを叩けてしまう。
        $lock = Cache::lock('flow_action:'.$tool->id.':'.$record->id, 60);
        abort_unless($lock->get(), 409, 'この処理は実行中です。しばらくしてから再度お試しください。');

        try {
            $values = $this->valuesByKey($record, $def);
            $state = $this->state($tool, $def, $values);
            if ($state['status'] !== 'ready') {
                throw ValidationException::withMessages([
                    'message' => $state['reason'] ?? '現在この処理は実行できません。',
                ]);
            }

            $input = [];
            foreach (array_keys($handler::inputs()) as $key) {
                $input[$key] = $values[$key] ?? null;
            }

            $result = $handler->run($record, $user, $input);
            $written = $this->writeBack($record, $def, $handler::outputs(), $result['values'] ?? []);
        } finally {
            $lock->release();
        }

        if ($written !== []) {
            $record->logs()->create([
                'user_id' => $user->id,
                'action' => 'custom_action',
                'note' => $tool->name,
                'changes' => collect($written)->mapWithKeys(fn ($v, $k) => [$k => ['new' => $v]])->all(),
            ]);
            $record->forceFill(['updated_by' => $user->id])->save();
        }

        $this->flow->logAudit($def, $user, 'record_action', $record, [
            'tool' => $tool->name,
            'handler' => $handler::key(),
            'written' => array_keys($written),
        ]);

        return [
            'message' => $result['message'] ?? '実行しました。',
            'written' => array_keys($written),
        ];
    }

    /** 有効なカスタムボタン（設定順）。 */
    private function activeTools(?FlowDefinition $def)
    {
        if (! $def) {
            return collect();
        }
        $tools = $def->relationLoaded('tools') ? $def->tools : $def->tools()->get();

        return $tools->where('tool_type', 'action')->where('is_active', true)->sortBy('sort_order')->values();
    }

    private function state(FlowAppTool $tool, FlowDefinition $def, array $valuesByKey): array
    {
        $config = is_array($tool->config) ? $tool->config : [];

        return $this->stateFor($config['handler'] ?? null, $def->fields, $valuesByKey);
    }

    /**
     * ボタンの状態。押せる人の判定は含まない（それは呼び出し側）。
     *
     * blocked はほぼ「アプリの設定が足りない」で、理由に足りないキーを出す——ボタンが黙って
     * 失敗する代わりに、作成者が直せる形で言う。
     *
     * @param  iterable  $fields  アプリのフィールド（key を持つもの）
     * @param  array<string, mixed>  $valuesByKey
     * @return array{status: 'ready'|'done'|'blocked', reason: ?string}
     */
    public function stateFor(?string $handlerKey, $fields, array $valuesByKey): array
    {
        $class = FlowRecordActions::classFor($handlerKey);
        if (! $class) {
            return ['status' => 'blocked', 'reason' => 'この処理は登録されていません。アプリ設定を確認してください。'];
        }

        $keys = collect($fields)->pluck('key')->all();
        $missing = [];
        foreach ($class::inputs() as $key => $meta) {
            if (($meta['required'] ?? false) && ! in_array($key, $keys, true)) {
                $missing[] = ($meta['label'] ?? $key).'（'.$key.'）';
            }
        }
        foreach ($class::outputs() as $key => $meta) {
            if (! in_array($key, $keys, true)) {
                $missing[] = ($meta['label'] ?? $key).'（'.$key.'）';
            }
        }
        if ($missing !== []) {
            return [
                'status' => 'blocked',
                'reason' => '設定不足：フィールド '.implode('、', $missing).' がこのアプリにありません。',
            ];
        }

        $done = $class::doneFieldKey();
        if ($done !== null && filled($valuesByKey[$done] ?? null)) {
            return ['status' => 'done', 'reason' => '実行済み'];
        }

        // 必須の入力が空 = 押しても失敗する。押させてから謝るより、理由を出して止める。
        $empty = [];
        foreach ($class::inputs() as $key => $meta) {
            if (($meta['required'] ?? false) && ! filled($valuesByKey[$key] ?? null)) {
                $empty[] = $meta['label'] ?? $key;
            }
        }
        if ($empty !== []) {
            return ['status' => 'blocked', 'reason' => implode('、', $empty).' を入力してください。'];
        }

        return ['status' => 'ready', 'reason' => null];
    }

    /**
     * 結果の書き戻し。outputs() に宣言されたキーだけを、そのアプリの同名キーのフィールドへ。
     *
     * @return array<string, mixed> 実際に書いたキー => 値
     */
    private function writeBack(FlowRecord $record, FlowDefinition $def, array $outputs, array $values): array
    {
        if ($values === []) {
            return [];
        }
        $byKey = collect($def->fields)->keyBy('key');
        $written = [];

        foreach ($values as $key => $value) {
            if (! array_key_exists($key, $outputs)) {
                continue;   // 宣言外のキーは書かない
            }
            $field = $byKey->get($key);
            if (! $field) {
                continue;
            }
            $this->flow->saveFieldValue($record, $field, $value);
            $written[$key] = $value;
        }

        if ($written !== []) {
            $record->load('values');
        }

        return $written;
    }

    /** レコードの値をフィールドキー引きにする（ハンドラはIDを知らない）。 */
    private function valuesByKey(FlowRecord $record, FlowDefinition $def): array
    {
        $byId = $this->flow->recordValues($record, $def->fields);
        $out = [];

        foreach ($def->fields as $field) {
            if (array_key_exists((string) $field->id, $byId)) {
                $out[$field->key] = $byId[(string) $field->id];
            }
        }

        return $out;
    }
}
