<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZoomAccount extends Model
{
    protected $guarded = [];

    protected $hidden = [
        'host_password',
        'client_secret',
        'webhook_secret',
    ];

    protected $casts = [
        'slot' => 'integer',
        'active' => 'boolean',
        'host_password' => 'encrypted',
        'client_secret' => 'encrypted',
        'webhook_secret' => 'encrypted',
    ];

    public function isApiConfigured(): bool
    {
        return filled($this->host_email)
            && filled($this->account_id)
            && filled($this->client_id)
            && filled($this->client_secret);
    }

    public function calendarOption(): array
    {
        return [
            'label' => $this->label,
            'value' => $this->slot,
            'selected' => false,
            'selectable' => $this->active,
        ];
    }

    public function adminPayload(): array
    {
        return [
            'id' => $this->id,
            'slot' => $this->slot,
            'label' => $this->label,
            'host_email' => $this->host_email,
            'account_id' => $this->account_id,
            'client_id' => $this->client_id,
            'active' => $this->active,
            'api_configured' => $this->isApiConfigured(),
            'host_password_configured' => filled($this->host_password),
            'client_secret_configured' => filled($this->client_secret),
            'webhook_secret_configured' => filled($this->webhook_secret),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
