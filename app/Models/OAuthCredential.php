<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject;
use Illuminate\Database\Eloquent\Model;

class OAuthCredential extends Model
{
    protected $fillable = [
        'user_id','provider','service','provider_user_id','account_email',
        'account_name','avatar_url','calendar_ids',
        'access_token_enc','refresh_token_enc','id_token_enc',
        'expires_at','token_type','scope','revoked_at',
    ];

    protected $casts = [
        'access_token_enc'  => AsEncryptedArrayObject::class,   // stores array as encrypted JSON
        'refresh_token_enc' => AsEncryptedArrayObject::class,
        'id_token_enc'      => AsEncryptedArrayObject::class,
        'calendar_ids'      => 'array',
        'expires_at'        => 'datetime',
        'revoked_at'        => 'datetime',
    ];

    // Convenience accessors
    public function getAccessTokenAttribute(): ?string
    {
        return $this->access_token_enc['access_token'] ?? null;
    }

    public function getRefreshTokenAttribute(): ?string
    {
        return $this->refresh_token_enc['refresh_token'] ?? null;
    }
}