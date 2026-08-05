<?php

namespace App\Support\FlowActions;

use App\Models\FlowRecord;
use App\Models\User;

/**
 * 「カスタムボタン」の処理本体。
 *
 * アプリ作成者が設定できるのはボタンの名前・色・押せる人だけで、何をするかはこのクラス＝コードが
 * 決める。freeeへの登録のように「どのフィールドが何を意味するか」を分かっていないと組めない処理を、
 * マッピングUIを作らずに載せるための入り口。ボタンの設定にURLは持たせない（設定に書かれた宛先を
 * サーバーが呼ぶ形にすると、アプリ管理者が任意の宛先へレコードを送れてしまう）。実行できるのは
 * FlowRecordActions に登録されたこのクラスたちだけ。
 *
 * 入出力はフィールドIDではなく「キー」で宣言する。フィールドIDはアプリごとに違うがキーは作成者が
 * 付けられるので、ハンドラはEAVを一切知らずに済み、キーが足りなければボタン自身が「設定不足」と
 * 言える（推測して黙って失敗しない）。
 */
abstract class FlowRecordAction
{
    /** 設定に保存される識別子。変更するとそのボタンは「登録されていません」になる。 */
    abstract public static function key(): string;

    /** ボタン追加時の既定の名前。作成者は自由に変えられる。 */
    abstract public static function label(): string;

    /** 設定画面に出す説明。 */
    abstract public static function description(): string;

    /**
     * レコードから読むフィールドキー。required のものが無いとボタンは押せない。
     *
     * @return array<string, array{label: string, required?: bool}>
     */
    public static function inputs(): array
    {
        return [];
    }

    /**
     * 結果を書き戻すフィールドキー。ここに宣言されていないキーは書き戻さない。
     *
     * @return array<string, array{label: string}>
     */
    public static function outputs(): array
    {
        return [];
    }

    /**
     * 値が入っていれば「実行済み」と見なすフィールドキー。null なら何度でも実行できる。
     * 一度だけの処理（外部への登録など）はここを必ず指定する。
     */
    public static function doneFieldKey(): ?string
    {
        return null;
    }

    /** 押したときの確認文。外部に出る処理なので、既定でも必ず確認する。 */
    public static function confirmMessage(): string
    {
        return 'この処理を実行しますか？';
    }

    /**
     * @param  array<string, mixed>  $input  inputs() で宣言したキー => レコードの値
     * @return array{message: string, values?: array<string, mixed>}
     *                                                               values は outputs() のキー => 書き戻す値
     */
    abstract public function run(FlowRecord $record, User $user, array $input): array;
}
