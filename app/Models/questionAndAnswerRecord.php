<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCommunity;
use Illuminate\Database\Eloquent\Model;

class questionAndAnswerRecord extends Model
{
    use BelongsToCommunity;

    public const AI_SYNC_STATUS_NOT_SYNCED = 'not_synced';
    public const AI_SYNC_STATUS_SYNCING = 'syncing';
    public const AI_SYNC_STATUS_SYNCED = 'synced';
    public const AI_SYNC_STATUS_ERROR = 'error';

    protected $fillable = [
        'user_id',
        'question',
        'answer',
        'content',
        'tag_text',
        'deleted_flag',
        'useful_count',
        'ai_sync_status',
        'ai_sync_error',
        'ai_synced_at',
        'ai_sync_hash',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'deleted_flag' => 'integer',
        'useful_count' => 'integer',
        'ai_synced_at' => 'datetime',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
    public function qanda_use_tags(){
        return $this->hasMany(qandaUseTag::class, 'record_id');
    }
    public function qanda_use_key_words(){
        return $this->hasMany(qandaUseKeyWord::class, 'record_id');
    }

    public function vectorDocuments()
    {
        return $this->hasMany(QuestionAndAnswerVectorDocument::class, 'question_and_answer_record_id');
    }

}
