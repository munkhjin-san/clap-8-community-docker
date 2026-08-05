<?php

namespace Tests\Feature;

use App\Models\FlowField;
use App\Services\FlowRecordActionService;
use App\Support\FlowActions\FreeePartnerCreateAction;
use Tests\TestCase;

/**
 * カスタムボタンの状態判定（FlowRecordActionService::stateFor）。DBは使わない。
 *
 * ここが「マッピングUIを作らない」代償を引き受けている部分：処理はフィールドコードで値を
 * 読み書きするので、アプリ側にそのコードが無ければボタンは黙って失敗するのではなく
 * 「設定不足」と言わなければならない。その約束を固定する。
 */
class FlowRecordActionStateTest extends TestCase
{
    private function fields(array $keys): array
    {
        $out = [];
        $id = 0;
        foreach ($keys as $key) {
            $f = new FlowField(['key' => $key, 'label' => $key, 'input_type' => 'short']);
            $f->id = ++$id;
            $out[] = $f;
        }

        return $out;
    }

    private function state(?string $handler, array $keys, array $values): array
    {
        return app(FlowRecordActionService::class)->stateFor($handler, $this->fields($keys), $values);
    }

    /** 完全に揃っていて未実行 = 押せる。 */
    public function test_ready_when_every_declared_key_exists_and_the_done_field_is_empty(): void
    {
        $state = $this->state(
            FreeePartnerCreateAction::key(),
            ['partner_name', 'freee_partner_id'],
            ['partner_name' => 'テルウェル東日本', 'freee_partner_id' => null],
        );

        $this->assertSame('ready', $state['status']);
        $this->assertNull($state['reason']);
    }

    /** 未登録のキー（設定の書き換え・ハンドラの削除）は実行できない。 */
    public function test_unknown_handler_is_blocked(): void
    {
        $state = $this->state('definitely_not_registered', ['partner_name'], []);

        $this->assertSame('blocked', $state['status']);
        $this->assertStringContainsString('登録されていません', $state['reason']);
    }

    /** 必須の入力フィールドがアプリに無い → 設定不足として、足りないコードを名指しする。 */
    public function test_missing_required_input_field_is_blocked_and_names_the_key(): void
    {
        $state = $this->state(FreeePartnerCreateAction::key(), ['freee_partner_id'], []);

        $this->assertSame('blocked', $state['status']);
        $this->assertStringContainsString('設定不足', $state['reason']);
        $this->assertStringContainsString('partner_name', $state['reason']);
    }

    /**
     * 書き戻し先が無いのも設定不足。ここを許すと外部登録は成功したのに結果を保存できず、
     * 「実行済み」にもならないので二重登録できてしまう。
     */
    public function test_missing_output_field_is_blocked(): void
    {
        $state = $this->state(FreeePartnerCreateAction::key(), ['partner_name'], ['partner_name' => 'A社']);

        $this->assertSame('blocked', $state['status']);
        $this->assertStringContainsString('freee_partner_id', $state['reason']);
    }

    /** doneFieldKey に値が入っていれば実行済み（一度だけの処理を二度実行させない）。 */
    public function test_done_when_the_done_field_already_holds_a_value(): void
    {
        $state = $this->state(
            FreeePartnerCreateAction::key(),
            ['partner_name', 'freee_partner_id'],
            ['partner_name' => 'A社', 'freee_partner_id' => '12345'],
        );

        $this->assertSame('done', $state['status']);
    }

    /** 必須の値が空 = 押しても失敗する。押させてから謝らない。 */
    public function test_blank_required_value_is_blocked_with_an_input_prompt(): void
    {
        $state = $this->state(
            FreeePartnerCreateAction::key(),
            ['partner_name', 'freee_partner_id'],
            ['partner_name' => '   ', 'freee_partner_id' => null],
        );

        $this->assertSame('blocked', $state['status']);
        $this->assertStringContainsString('取引先名', $state['reason']);
    }

    /** 実行済み判定は必須値の空チェックより先（登録後に入力を消しても「実行済み」のまま）。 */
    public function test_done_wins_over_a_blank_required_value(): void
    {
        $state = $this->state(
            FreeePartnerCreateAction::key(),
            ['partner_name', 'freee_partner_id'],
            ['partner_name' => '', 'freee_partner_id' => '999'],
        );

        $this->assertSame('done', $state['status']);
    }
}
