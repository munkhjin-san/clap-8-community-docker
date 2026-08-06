<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FreeeCredential extends Model
{
    public const STATUS_UNCONFIGURED = 'unconfigured';

    public const STATUS_AWAITING_CONSENT = 'awaiting_consent';

    public const STATUS_CONNECTED = 'connected';

    public const STATUS_NEEDS_REAUTH = 'needs_reauth';

    /**
     * コールバックURLを登録できない環境（ローカル開発など）向けの値。
     * これを指定すると、freeeは認可コードをブラウザに表示するだけでリダイレクトしない。
     * 管理者がその文字列を貼り付けて交換する。
     */
    public const OOB_REDIRECT_URI = 'urn:ietf:wg:oauth:2.0:oob';

    /**
     * アクセストークンの残り有効時間がこれを下回ったら更新する。
     * 有効期限は6時間なので十分な余裕を取る。
     */
    public const REFRESH_SKEW_MINUTES = 30;

    /**
     * リフレッシュトークンの残り日数がこれを下回ったら管理画面で警告する。
     * 有効期限は90日で、更新のたびに再発行されるため通常ここには到達しない。
     */
    public const REAUTH_WARNING_DAYS = 14;

    protected $guarded = [];

    protected $hidden = [
        'client_secret',
        'access_token',
        'refresh_token',
    ];

    protected $casts = [
        'client_secret' => 'encrypted',
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'company_id' => 'integer',
        'refresh_count' => 'integer',
        'active' => 'boolean',
        'access_token_expires_at' => 'datetime',
        'refresh_token_expires_at' => 'datetime',
        'last_refreshed_at' => 'datetime',
        'last_error_at' => 'datetime',
        'authorized_at' => 'datetime',
    ];

    public function authorizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    /**
     * 認可フローを開始できるだけの情報が入っているか。
     */
    public function isAppConfigured(): bool
    {
        return filled($this->client_id)
            && filled($this->client_secret)
            && filled($this->redirect_uri);
    }

    /**
     * コードを手貼りする方式か（リダイレクトを受け取らない）。
     */
    public function isOutOfBand(): bool
    {
        return $this->redirect_uri === self::OOB_REDIRECT_URI;
    }

    /**
     * トークンは取れているが事業所IDが未確定な状態。
     * この状態ではAPI呼び出しが全て失敗するので、管理画面で事業所を選ばせる必要がある。
     */
    public function awaitingCompanySelection(): bool
    {
        return $this->isConnected() && ! filled($this->company_id);
    }

    /**
     * トークンペアを保持していて、まだ再認可を要求されていないか。
     */
    public function isConnected(): bool
    {
        return $this->status === self::STATUS_CONNECTED
            && filled($this->access_token)
            && filled($this->refresh_token);
    }

    public function accessTokenExpired(): bool
    {
        return $this->access_token_expires_at === null
            || $this->access_token_expires_at->isPast();
    }

    /**
     * 期限切れ間近も含めて更新すべきか。
     */
    public function accessTokenNeedsRefresh(): bool
    {
        return $this->access_token_expires_at === null
            || $this->access_token_expires_at->isBefore(now()->addMinutes(self::REFRESH_SKEW_MINUTES));
    }

    /**
     * リフレッシュトークン自体が切れている場合、ブラウザでの再認可しか復帰手段がない。
     */
    public function refreshTokenExpired(): bool
    {
        return $this->refresh_token_expires_at !== null
            && $this->refresh_token_expires_at->isPast();
    }

    public function reauthorizationRequired(): bool
    {
        return $this->status === self::STATUS_NEEDS_REAUTH || $this->refreshTokenExpired();
    }

    /**
     * 管理画面用。シークレットとトークンは一切返さず、設定済みかどうかだけを返す。
     */
    public function adminPayload(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'company_id' => $this->company_id,
            'company_name' => $this->company_name,
            'status' => $this->status,
            'active' => $this->active,
            'app_configured' => $this->isAppConfigured(),
            'client_secret_configured' => filled($this->client_secret),
            'connected' => $this->isConnected(),
            'out_of_band' => $this->isOutOfBand(),
            'awaiting_company_selection' => $this->awaitingCompanySelection(),
            'reauthorization_required' => $this->reauthorizationRequired(),
            'access_token_expires_at' => $this->access_token_expires_at?->toISOString(),
            'refresh_token_expires_at' => $this->refresh_token_expires_at?->toISOString(),
            'refresh_token_days_left' => $this->refresh_token_expires_at
                ? max(0, (int) now()->startOfDay()->diffInDays($this->refresh_token_expires_at->startOfDay(), false))
                : null,
            'last_refreshed_at' => $this->last_refreshed_at?->toISOString(),
            'refresh_count' => $this->refresh_count,
            'authorized_at' => $this->authorized_at?->toISOString(),
            'authorized_by_name' => $this->authorizer?->name,
            'last_error' => $this->last_error,
            'last_error_at' => $this->last_error_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
