<?php

namespace App\Services\Freee;

use App\Models\FreeeCredential;
use RuntimeException;
use Throwable;

/**
 * リフレッシュトークンの連鎖が切れ、ブラウザでの再認可以外に復帰手段がない状態。
 *
 * 主な原因:
 *  - 同一のリフレッシュトークンを2度使った（並行更新・素朴なリトライ）
 *  - 90日間一度も更新しなかった
 *  - 利用者がfreee側でアプリ連携を解除した
 */
class FreeeReauthorizationRequiredException extends RuntimeException
{
    /**
     * @param  string|null  $reason  永続化すべき理由。更新トランザクションの内側では
     *                               書き込んでもロールバックされるため、理由だけを運び、
     *                               トランザクションを抜けた公開メソッド側で記録する。
     */
    public function __construct(
        public readonly FreeeCredential $credential,
        string $message = '',
        ?Throwable $previous = null,
        public readonly ?string $reason = null,
    ) {
        parent::__construct(
            $message !== '' ? $message : 'freee連携の再認可が必要です。管理画面から認可し直してください。',
            previous: $previous,
        );
    }
}
