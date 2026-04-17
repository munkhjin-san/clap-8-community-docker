<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactBatchNotification extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'read_at' => 'datetime',
        'pushed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ContactBatch::class, 'contact_batch_id');
    }
}
