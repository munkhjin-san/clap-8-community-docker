<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TagRecord extends Model
{
    use HasFactory;

    public function knowledgeRecords()
    {
        return $this->belongsToMany(KnowledgeRecord::class, 'knowledge_use_tags', 'tag_id', 'record_id');
    }
    public function challengeRecords()
    {
        return $this->belongsToMany(ChallengeRecord::class, 'challenge_use_tags', 'tag_id', 'record_id');
    }
    public function niceRecords()
    {
        return $this->belongsToMany(NiceRecord::class, 'nice_use_tags', 'tag_id', 'record_id');
    }
    protected $casts = [
        'hits' => 'int', 
    ];
    protected $fillable = [
        'text'
    ];
}
