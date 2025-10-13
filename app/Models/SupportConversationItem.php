<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportConversationItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function conversation()
    {
        return $this->belongsTo(SupportConversation::class, 'support_conversation_id');
    }

    protected $casts = [
        'keywords' => 'array',
        'source' => 'array',
    ];
}
