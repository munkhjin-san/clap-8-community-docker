<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactBatchItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'scan_result' => 'array',
        'enrich_result' => 'array',
        'duplicate_candidates' => 'array',
        'needs_review' => 'boolean',
    ];

    public const STATUS_QUEUED = 'queued';
    public const STATUS_SCANNING = 'scanning';
    public const STATUS_SCANNED = 'scanned';
    public const STATUS_ENRICHING = 'enriching';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ContactBatch::class, 'contact_batch_id');
    }

    public function contactRecord(): BelongsTo
    {
        return $this->belongsTo(ContactRecord::class);
    }
}
