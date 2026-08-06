<?php

namespace App\Services\Freee;

use App\Models\FreeeCredential;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * freee OAuth 2.0 のトークンストア兼更新サービス。
 *
 * freeeにはclient_credentials相当のフローが無いため、最初の1回だけは管理者が
 * ブラウザで「許可する」を押す必要がある。それ以降は人の操作は不要だが、それは
 * リフレッシュトークンの連鎖が切れない限りにおいてである。
 *
 * 連鎖を切らないための不変条件:
 *  1. リフレッシュトークンは単回使用。更新すると新しいものが発行され、古いものは即座に無効になる。
 *  2. よって「同じリフレッシュトークンでリトライ」は絶対に行わない。失敗したら読み直す。
 *  3. 更新は必ず排他制御下で行い、新旧の差し替えは1トランザクションで確定させる。
 *  4. 更新は有効期限ギリギリではなく短い間隔で回す（90日は安全余裕であってスケジュールではない）。
 */
class FreeeTokenService
{
    private const AUTHORIZE_URL = 'https://accounts.secure.freee.co.jp/public_api/authorize';

    private const TOKEN_URL = 'https://accounts.secure.freee.co.jp/public_api/token';

    /** リフレッシュトークンの有効期間（発行後90日）。 */
    private const REFRESH_TOKEN_TTL_DAYS = 90;

    /** 更新ロックの保持上限。外部HTTP呼び出しを含むため余裕を持たせる。 */
    private const LOCK_SECONDS = 60;

    /** 他プロセスが更新中の場合に待つ秒数。 */
    private const LOCK_WAIT_SECONDS = 25;

    /**
     * 認可画面のURL。$state は呼び出し側がセッションに保存し、コールバックで照合する。
     */
    public function authorizationUrl(FreeeCredential $credential, string $state): string
    {
        if (! $credential->isAppConfigured()) {
            throw ValidationException::withMessages([
                'message' => 'クライアントID・クライアントシークレット・コールバックURLを先に保存してください。',
            ]);
        }

        return self::AUTHORIZE_URL.'?'.http_build_query([
            'response_type' => 'code',
            'client_id' => $credential->client_id,
            'redirect_uri' => $credential->redirect_uri,
            'state' => $state,
            // 利用者に連携する事業所を選ばせる。付けない場合は所属する全事業所が対象になる。
            'prompt' => 'select_company',
        ]);
    }

    /**
     * 認可コードをトークンペアに交換する。ブラウザでの同意直後に一度だけ呼ばれる。
     */
    public function exchangeAuthorizationCode(FreeeCredential $credential, string $code, ?int $authorizedByUserId = null): FreeeCredential
    {
        $payload = $this->recordingReauthNeed($credential, fn () => $this->requestToken($credential, [
            'grant_type' => 'authorization_code',
            'client_id' => $credential->client_id,
            'client_secret' => $credential->client_secret,
            'code' => $code,
            'redirect_uri' => $credential->redirect_uri,
        ]));

        $this->storeTokenPayload($credential, $payload, [
            'authorized_by' => $authorizedByUserId ?? $credential->authorized_by,
            'authorized_at' => now(),
        ]);

        // 付与された権限はここだけに残る。HR APIが403になる場合の一次切り分けに使う
        // （会計スコープしか付いていない、などがすぐ分かる）。
        Log::info('freee authorization completed.', [
            'freee_credential_id' => $credential->id,
            'company_id' => $credential->company_id,
            'granted_scope' => $payload['scope'] ?? null,
        ]);

        return $credential;
    }

    /**
     * 有効なアクセストークンを返す。期限が近ければ透過的に更新する。
     * freee APIを叩く箇所は必ずここを経由すること。
     */
    public function accessToken(FreeeCredential $credential): string
    {
        if (! $credential->isConnected()) {
            throw new FreeeReauthorizationRequiredException(
                $credential,
                'freee連携が未認可です。管理画面から認可してください。',
            );
        }

        if ($credential->reauthorizationRequired()) {
            throw new FreeeReauthorizationRequiredException($credential);
        }

        if (! $credential->accessTokenNeedsRefresh()) {
            return (string) $credential->access_token;
        }

        return (string) $this->refresh($credential)->access_token;
    }

    /**
     * トークンを更新して新しいペアを永続化する。
     *
     * 排他は二重に張る。Cacheロックは同時要求の山を平す速い経路だが、fileドライバでは
     * インスタンス単位でしか効かない。そのため権威ある排他はDBの行ロックに置き、
     * 「読み直し・更新・保存」を1トランザクションに閉じ込める。
     */
    public function refresh(FreeeCredential $credential, bool $force = false): FreeeCredential
    {
        $lock = Cache::lock("freee:token-refresh:{$credential->id}", self::LOCK_SECONDS);

        try {
            return $this->recordingReauthNeed(
                $credential,
                fn () => $lock->block(self::LOCK_WAIT_SECONDS, fn () => $this->refreshUnderLock($credential, $force)),
            );
        } catch (LockTimeoutException) {
            // 待っている間に他プロセスが更新し終えている可能性が高い。読み直して使えるなら使う。
            $credential->refresh();

            if ($credential->isConnected() && ! $credential->accessTokenNeedsRefresh()) {
                return $credential;
            }

            throw ValidationException::withMessages([
                'message' => 'freeeトークンの更新が他の処理と競合しました。時間をおいて再試行してください。',
            ]);
        }
    }

    private function refreshUnderLock(FreeeCredential $credential, bool $force): FreeeCredential
    {
        return DB::transaction(function () use ($credential, $force) {
            /** @var FreeeCredential $locked */
            $locked = FreeeCredential::query()
                ->whereKey($credential->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // ロック待ちの間に他プロセスが更新を終えていたら、その結果をそのまま使う。
            // ここで再確認しないと、無効化済みのリフレッシュトークンを二度使ってしまう。
            if (! $force && $locked->isConnected() && ! $locked->accessTokenNeedsRefresh()) {
                $credential->setRawAttributes($locked->getAttributes(), true);

                return $credential;
            }

            // 以下の throw はこのトランザクションをロールバックさせる。よって状態の記録は
            // ここでは行わず、reason だけを載せて recordingReauthNeed() に委ねる。
            if ($locked->refreshTokenExpired()) {
                throw new FreeeReauthorizationRequiredException(
                    $locked,
                    reason: 'リフレッシュトークンの有効期限（90日）が切れています。',
                );
            }

            if (! filled($locked->refresh_token)) {
                throw new FreeeReauthorizationRequiredException(
                    $locked,
                    reason: 'リフレッシュトークンが保存されていません。',
                );
            }

            $payload = $this->requestToken($locked, [
                'grant_type' => 'refresh_token',
                'client_id' => $locked->client_id,
                'client_secret' => $locked->client_secret,
                'refresh_token' => $locked->refresh_token,
            ]);

            $this->storeTokenPayload($locked, $payload, [
                'last_refreshed_at' => now(),
                'refresh_count' => $locked->refresh_count + 1,
            ]);

            // 呼び出し元が持っているインスタンスにも反映させる。
            $credential->setRawAttributes($locked->getAttributes(), true);
            $credential->syncOriginal();

            return $credential;
        });
    }

    /**
     * トークンを破棄して未接続状態に戻す。アプリ資格情報は残す。
     */
    public function disconnect(FreeeCredential $credential): void
    {
        $credential->forceFill([
            'access_token' => null,
            'refresh_token' => null,
            'token_type' => null,
            'access_token_expires_at' => null,
            'refresh_token_expires_at' => null,
            'last_refreshed_at' => null,
            'refresh_count' => 0,
            'company_id' => null,
            'company_name' => null,
            'external_cid' => null,
            'authorized_by' => null,
            'authorized_at' => null,
            'status' => FreeeCredential::STATUS_UNCONFIGURED,
            'last_error' => null,
            'last_error_at' => null,
        ])->save();
    }

    /**
     * 連鎖が切れたことを「トランザクションの外で」記録する。
     *
     * 更新は DB::transaction の内側で行うため、そこで status を書いてから例外を投げると
     * ロールバックで消えてしまう。管理画面に「再認可が必要」を出すにはこの記録が残らねば
     * ならないので、例外が抜けてきてから改めて書き込む。
     *
     * @template T
     *
     * @param  callable():T  $operation
     * @return T
     */
    private function recordingReauthNeed(FreeeCredential $credential, callable $operation)
    {
        try {
            return $operation();
        } catch (FreeeReauthorizationRequiredException $exception) {
            if ($exception->reason !== null) {
                // ロールバック後の値を読み直してから記録する。
                $credential->refresh();
                $this->markNeedsReauth($credential, $exception->reason);
            }

            throw $exception;
        }
    }

    public function markNeedsReauth(FreeeCredential $credential, string $reason): void
    {
        $credential->forceFill([
            'status' => FreeeCredential::STATUS_NEEDS_REAUTH,
            'last_error' => $reason,
            'last_error_at' => now(),
        ])->save();

        Log::warning('freee credential needs re-authorization.', [
            'freee_credential_id' => $credential->id,
            'company_id' => $credential->company_id,
            'reason' => $reason,
        ]);
    }

    /**
     * トークンレスポンスを保存する。
     *
     * refresh_token は毎回新しいものが返るため必ず上書きし、90日の期限も打ち直す。
     * 呼び出し元がトランザクション内にいるため、旧トークンの無効化と新トークンの保存が原子的になる。
     */
    private function storeTokenPayload(FreeeCredential $credential, array $payload, array $extra = []): void
    {
        $accessToken = $payload['access_token'] ?? null;
        $refreshToken = $payload['refresh_token'] ?? null;

        if (! is_string($accessToken) || $accessToken === '') {
            throw ValidationException::withMessages([
                'message' => 'freeeからアクセストークンが返されませんでした。',
            ]);
        }

        if (! is_string($refreshToken) || $refreshToken === '') {
            // freeeは常に再発行する。返ってこない場合は連鎖を維持できないので明示的に失敗させる。
            throw ValidationException::withMessages([
                'message' => 'freeeからリフレッシュトークンが返されませんでした。連携をやり直してください。',
            ]);
        }

        $expiresIn = (int) ($payload['expires_in'] ?? 0);

        $attributes = [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => $payload['token_type'] ?? 'bearer',
            // scopeは保存しない（分岐に使わず、freee側の設定変更で古くなるだけ）。認可時にログへ残す。
            'access_token_expires_at' => $expiresIn > 0 ? now()->addSeconds($expiresIn) : now()->addHours(6),
            'refresh_token_expires_at' => now()->addDays(self::REFRESH_TOKEN_TTL_DAYS),
            'status' => FreeeCredential::STATUS_CONNECTED,
            'last_error' => null,
            'last_error_at' => null,
        ];

        if (filled($payload['company_id'] ?? null)) {
            $companyId = (int) $payload['company_id'];

            // 事業所が変わるときだけ重複を確認する。通常の更新では同じ事業所が返るので、
            // ここで無条件に検査すると定期更新が重複エラーで止まってしまう。
            if ((int) $credential->company_id !== $companyId) {
                $this->guardCompanyIsNotAlreadyConnected($credential, $companyId);
            }

            $attributes['company_id'] = $companyId;
        }

        if (filled($payload['external_cid'] ?? null)) {
            $attributes['external_cid'] = (string) $payload['external_cid'];
        }

        $credential->forceFill($attributes + $extra)->save();
    }

    /**
     * 同じ事業所を二重に連携させない。
     *
     * freee_credentials.company_id には UNIQUE が張ってあるため、素通しすると
     * QueryException（500）になる。初回セットアップで複数行を作り、同じ事業所を
     * 選んでしまうのはよくある操作なので、何が起きたか読める形で弾く。
     */
    private function guardCompanyIsNotAlreadyConnected(FreeeCredential $credential, int $companyId): void
    {
        $owner = FreeeCredential::query()
            ->where('company_id', $companyId)
            ->whereKeyNot($credential->getKey())
            ->first();

        if ($owner) {
            throw ValidationException::withMessages([
                'message' => "この事業所（ID: {$companyId}）は既に「{$owner->label}」で連携されています。"
                    .'別の事業所を選ぶか、先に既存の連携を解除してください。',
            ]);
        }
    }

    /**
     * 認可後に事業所を手動で確定する。
     *
     * 事業所選択なしで認可された場合（company_idが返らない場合）、トークンはあるのに
     * API呼び出しが全て失敗する。その状態から復帰するための唯一の手段。
     *
     * @param  array<int, array{id: int|null, name: string|null}>  $availableCompanies
     */
    public function selectCompany(FreeeCredential $credential, int $companyId, array $availableCompanies): FreeeCredential
    {
        $match = collect($availableCompanies)->firstWhere('id', $companyId);

        if (! $match) {
            throw ValidationException::withMessages([
                'message' => '指定された事業所は、認可されたfreeeアカウントで利用できません。',
            ]);
        }

        $this->guardCompanyIsNotAlreadyConnected($credential, $companyId);

        $credential->forceFill([
            'company_id' => $companyId,
            'company_name' => $match['name'] ?? null,
        ])->save();

        return $credential;
    }

    /**
     * トークンエンドポイントを叩く。
     *
     * ここでは決してリトライしない。リフレッシュトークンは単回使用なので、
     * タイムアウト後の再送は「使用済みトークンの再利用」になり連鎖を壊す。
     */
    private function requestToken(FreeeCredential $credential, array $form): array
    {
        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(20)
                ->post(self::TOKEN_URL, $form)
                ->throw();
        } catch (RequestException $exception) {
            $status = $exception->response?->status();
            $detail = $this->tokenErrorDetail($exception);

            Log::warning('freee token request failed.', [
                'freee_credential_id' => $credential->id,
                'grant_type' => $form['grant_type'] ?? null,
                'status' => $status,
                'detail' => $detail,
            ]);

            // invalid_grant（400/401）は連鎖が切れた合図。復帰はブラウザでの再認可のみ。
            if (in_array($status, [400, 401], true)) {
                throw new FreeeReauthorizationRequiredException(
                    $credential,
                    'freeeの認可が無効になりました（'.$detail.'）。管理画面から認可し直してください。',
                    $exception,
                    reason: 'freee認可エラー（HTTP '.$status.'）：'.$detail,
                );
            }

            throw ValidationException::withMessages([
                'message' => 'freeeトークン取得エラー'.($status ? '（HTTP '.$status.'）' : '').'：'.$detail,
            ]);
        } catch (ConnectionException $exception) {
            Log::warning('freee token request could not connect.', [
                'freee_credential_id' => $credential->id,
                'grant_type' => $form['grant_type'] ?? null,
            ]);

            // 接続失敗はトークンを消費していない可能性が高いが、確証がないため
            // 自動リトライはせず失敗として扱う。次回のスケジュール実行に委ねる。
            throw ValidationException::withMessages([
                'message' => 'freee接続エラー：'.$this->clean($exception->getMessage()),
            ]);
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw ValidationException::withMessages([
                'message' => 'freeeトークンレスポンスの解析に失敗しました。',
            ]);
        }

        return $payload;
    }

    private function tokenErrorDetail(RequestException $exception): string
    {
        $payload = $exception->response?->json();
        $payload = is_array($payload) ? $payload : [];

        $detail = $payload['error_description']
            ?? $payload['error']
            ?? $payload['message']
            ?? $exception->response?->body()
            ?? $exception->getMessage();

        if (is_array($detail) || is_object($detail)) {
            $detail = json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $this->clean((string) $detail);
    }

    private function clean(string $detail): string
    {
        $detail = preg_replace('/\s+/u', ' ', strip_tags($detail)) ?? $detail;

        return mb_substr(trim($detail), 0, 500);
    }
}
