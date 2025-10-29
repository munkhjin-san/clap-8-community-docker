<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactBatchLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'context' => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ContactBatch::class, 'contact_batch_id');
    }
}
