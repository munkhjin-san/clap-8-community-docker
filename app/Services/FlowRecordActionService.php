<?php

namespace App\Services;

use App\Models\FlowDefinition;
use App\Models\FlowRecord;
use App\Models\User;
use App\Support\FlowRecordActions;
use Illuminate\Support\Facades\Cache;

/**
 * 「カスタムボタン」の実行系。
 *
 * ボタン1つ = flow_app_tools の1行（tool_type = 'action'）。設定に入るのは名前・色・押せる人と、
 * 呼ぶメソッド名だけ。何をするかは App\Support\FlowRecordActions のメソッド＝コードが決める。
 *
 * ここの仕事は3つだけ：押せる人かどうかを確かめる／二重実行を止める／結果の書き戻しと記録。
 * 「この状態なら実行してよいか」の判断は処理側に任せる（ValidationException を投げれば、その
 * メッセージが画面に出る）——アプリごとに事情が違うものを、この層で先回りして決めない。
 *
 * 書き戻しは FlowService::saveFieldValue を直接呼ぶ。つまり保存エンドポイントの編集権限チェックを
 * 通らない。これは意図的で、書き戻し先は「誰も編集できないフィールド」であってこそ意味がある
 * （外部が発行したIDを利用者が書き換えられるなら、記録として当てにならない）。
 */
class FlowRecordActionService
{
    public function __construct(private readonly FlowService $flow) {}

    /**
     * この利用者が押せるカスタムボタン。
     * 押せない人には返さない（ステータスのボタンと同じで、押せないボタンは出さない）。
     */
    public function actionsFor(User $user, FlowRecord $record): array
    {
        $def = $record->definition;
        if (! $def) {
            return [];
        }

        $out = [];
        foreach ($this->activeTools($def) as $tool) {
            $config = is_array($tool->config) ? $tool->config : [];
            $method = $config['handler'] ?? null;

            if (! FlowRecordActions::isCallable($method)) {
                continue;   // 設定が古い／メソッドが消えた → ボタンを出さない
            }
            if (! $this->flow->matchesAnySubject($user, $record, $config['eligible'] ?? null)) {
                continue;
            }

            $out[] = [
                'id' => $tool->id,
                'label' => $tool->name,
                'color' => $config['color'] ?? null,
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
        $method = $config['handler'] ?? null;
        abort_unless(FlowRecordActions::isCallable($method), 422, 'この処理は登録されていません。アプリ設定を確認してください。');
        abort_unless($this->flow->matchesAnySubject($user, $record, $config['eligible'] ?? null), 403);

        // 二重押し・二重タブ対策。外部に出る処理を2回走らせない。
        $lock = Cache::lock('flow_action:'.$tool->id.':'.$record->id, 60);
        abort_unless($lock->get(), 409, 'この処理は実行中です。しばらくしてから再度お試しください。');

        try {
            $result = app(FlowRecordActions::class)->run($method, $record, $user);
            $written = $this->writeBack($record, $def, $result['values'] ?? []);
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
            'handler' => $method,
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

    /**
     * 処理が返した値をフィールドコードで書き戻す。
     * このアプリに無いコードは黙って捨てる（処理側の書き間違いでレコードを壊さない）。
     *
     * @return array<string, mixed> 実際に書いたコード => 値
     */
    private function writeBack(FlowRecord $record, FlowDefinition $def, array $values): array
    {
        if ($values === []) {
            return [];
        }
        $byKey = collect($def->fields)->keyBy('key');
        $written = [];

        foreach ($values as $key => $value) {
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
}
