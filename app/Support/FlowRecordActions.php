<?php

namespace App\Support;

use App\Models\FlowRecord;
use App\Models\User;

/**
 * カスタムボタンから呼ばれる処理の置き場。
 *
 * 1処理 = このクラスのメソッド1つ + ACTIONS に1行。ボタンの設定に入るのはそのメソッド名だけで、
 * 中身は全部ここ＝コードに書く。フィールドの読み方も書き戻し方も宣言せず、必要なことをそのまま
 * PHPで書けばよい（kintoneのカスタマイズJSをサーバー側でやる、という位置づけ）。
 *
 * URLやクラス名を設定に持たせないのは意図的。設定に書かれた宛先をサーバーが呼ぶ形にすると、
 * アプリ管理者が任意の内部エンドポイントへレコードを送れてしまう。ACTIONS に載っている名前
 * だけが呼ばれ、それ以外は実行されない。
 *
 * 追加のしかた：
 *   1. ACTIONS に 'メソッド名' => '画面に出す名前' を足す
 *   2. 同じ名前の public メソッドを書く
 *
 * メソッドの約束：
 *   - 引数は (FlowRecord $record, User $user)
 *   - 戻り値は ['message' => '完了時に出す文', 'values' => ['フィールドコード' => 値]]（どちらも任意）
 *     values に入れたものは FlowRecordActionService が書き戻して変更履歴に残す。
 *     編集させたくないフィールド（編集権限なし）にも入る——保存エンドポイントを通らないため。
 *   - 実行すべきでない状態なら ValidationException を投げる（画面にそのメッセージが出る）。
 */
class FlowRecordActions
{
    /**
     * 実行を許可するメソッド名 => 設定画面に出す名前。
     *
     * @var array<string, string>
     */
    public const ACTIONS = [
        // 例）'createFreeePartner' => 'freeeに取引先を登録',
    ];

    /** 設定画面用の一覧。 */
    public static function catalog(): array
    {
        $out = [];
        foreach (self::ACTIONS as $method => $label) {
            $out[] = ['key' => $method, 'label' => $label];
        }

        return $out;
    }

    /** 許可リストに載っていて、実体のあるメソッドかどうか。 */
    public static function isCallable(?string $method): bool
    {
        return filled($method)
            && array_key_exists($method, self::ACTIONS)
            && method_exists(self::class, $method);
    }

    /**
     * 実行。呼べることは呼び出し側（FlowRecordActionService）が確認済み。
     *
     * @return array{message?: string, values?: array<string, mixed>}
     */
    public function run(string $method, FlowRecord $record, User $user): array
    {
        $result = $this->{$method}($record, $user);

        return is_array($result) ? $result : [];
    }

    /* ================================================================
     | ここから下が実際の処理。1メソッド1ボタン。
     |================================================================ */

    // 例）
    // public function createFreeePartner(FlowRecord $record, User $user): array
    // {
    //     $values = app(FlowService::class)->recordValues($record, $record->definition->fields);
    //     ... freee API を叩く ...
    //     return ['message' => '登録しました。', 'values' => ['freee_partner_id' => $id]];
    // }
}
