<?php

namespace App\Support;

use App\Support\FlowActions\FlowRecordAction;
use App\Support\FlowActions\FreeePartnerCreateAction;

/**
 * カスタムボタンから実行できる処理の一覧。
 *
 * ここに載っているキーだけが実行対象になる。ボタンの設定に入っているのは「キー」であって宛先URL
 * ではないので、アプリ管理者が新しい処理を生やすことはできない（コード追加＝この配列に1行）。
 * 知らないキーは実行せず、ボタン自身が「登録されていません」と言う。
 */
class FlowRecordActions
{
    /** @var array<int, class-string<FlowRecordAction>> */
    private const HANDLERS = [
        FreeePartnerCreateAction::class,
    ];

    /** 設定画面用。処理の一覧と、必要／書き戻しフィールドのキーを渡す。 */
    public static function catalog(): array
    {
        return array_map(fn (string $class) => [
            'key' => $class::key(),
            'label' => $class::label(),
            'description' => $class::description(),
            'inputs' => array_map(fn ($k, $m) => [
                'key' => $k,
                'label' => $m['label'] ?? $k,
                'required' => (bool) ($m['required'] ?? false),
            ], array_keys($class::inputs()), $class::inputs()),
            'outputs' => array_map(fn ($k, $m) => [
                'key' => $k,
                'label' => $m['label'] ?? $k,
            ], array_keys($class::outputs()), $class::outputs()),
            'once_only' => $class::doneFieldKey() !== null,
            'confirm' => $class::confirmMessage(),
        ], self::HANDLERS);
    }

    /** @return class-string<FlowRecordAction>|null */
    public static function classFor(?string $key): ?string
    {
        if (! filled($key)) {
            return null;
        }

        foreach (self::HANDLERS as $class) {
            if ($class::key() === $key) {
                return $class;
            }
        }

        return null;
    }

    /** 実行できるインスタンス（依存はコンテナが解決する）。未登録キーは null。 */
    public static function resolve(?string $key): ?FlowRecordAction
    {
        $class = self::classFor($key);

        return $class ? app($class) : null;
    }
}
