<?php

namespace App\Console\Commands;

use App\Models\FreeeCredential;
use App\Services\Freee\FreeeReauthorizationRequiredException;
use App\Services\Freee\FreeeTokenService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * freeeのリフレッシュトークン連鎖を温め続けるための定期実行。
 *
 * リフレッシュトークンは更新のたびに90日の期限が打ち直される。逆に言えば、
 * 一度も更新しないまま90日経つと人の再認可が必要になる。APIを日常的に使って
 * いなくてもこのコマンドが毎日回っていれば連鎖は途切れない。
 * 90日は安全余裕であってスケジュールではない、という前提で短い間隔で回す。
 */
class RefreshFreeeTokens extends Command
{
    protected $signature = 'freee:refresh-tokens {--force : 期限に関係なく全件更新する}';

    protected $description = 'Refresh freee OAuth access tokens and keep the refresh-token chain alive.';

    public function __construct(private readonly FreeeTokenService $tokens)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $credentials = FreeeCredential::query()
            ->where('active', true)
            ->whereIn('status', [FreeeCredential::STATUS_CONNECTED, FreeeCredential::STATUS_NEEDS_REAUTH])
            ->orderBy('id')
            ->get();

        if ($credentials->isEmpty()) {
            $this->info('No active freee credentials to refresh.');

            return self::SUCCESS;
        }

        $force = (bool) $this->option('force');
        $refreshed = 0;
        $skipped = 0;
        $needsReauth = 0;
        $failed = 0;

        foreach ($credentials as $credential) {
            $label = "#{$credential->id} {$credential->label}";

            if ($credential->reauthorizationRequired()) {
                $needsReauth++;
                $this->warn("{$label}: 再認可が必要です（管理画面 > 施設 > freee）。");

                continue;
            }

            if (! $force && ! $credential->accessTokenNeedsRefresh()) {
                $skipped++;

                continue;
            }

            try {
                $this->tokens->refresh($credential, force: true);
                $refreshed++;
                $this->line("{$label}: 更新しました（次回期限 {$credential->access_token_expires_at?->toDateTimeString()}）。");
            } catch (FreeeReauthorizationRequiredException $exception) {
                $needsReauth++;
                $this->error("{$label}: {$exception->getMessage()}");
            } catch (Throwable $exception) {
                $failed++;
                $this->error("{$label}: 更新に失敗しました - {$exception->getMessage()}");

                Log::warning('freee token scheduled refresh failed.', [
                    'freee_credential_id' => $credential->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $this->info("freee tokens: refreshed={$refreshed}, skipped={$skipped}, needs_reauth={$needsReauth}, failed={$failed}.");

        // 再認可待ちが残っている場合は非ゼロで返し、ログ監視で気付けるようにする。
        return $needsReauth > 0 || $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
