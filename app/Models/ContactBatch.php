<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactBatch extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'scan_requested_at' => 'datetime',
        'scan_completed_at' => 'datetime',
        'enrich_requested_at' => 'datetime',
        'enrich_completed_at' => 'datetime',
    ];

    public const STATUS_QUEUED = 'queued';
    public const STATUS_SCANNING = 'scanning';
    public const STATUS_ENRICHING = 'enriching';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ContactBatchItem::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ContactBatchLog::class);
    }

    public function scopeOwnedBy($query, ?int $userId)
    {
        if ($userId === null) {
            return $query;
        }

        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }
}
