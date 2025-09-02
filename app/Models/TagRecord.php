<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class TagRecord extends Model
{
    use HasFactory;
    use SoftDeletes;
    public function knowledgeRecords()
    {
        return $this->belongsToMany(KnowledgeRecord::class, 'knowledge_use_tags', 'tag_id', 'record_id')->wherePivot('deleted_flag', 0)->where('knowledge_records.deleted_flag', 0);
    }
    public function challengeRecords()
    {
        return $this->belongsToMany(ChallengeRecord::class, 'challenge_use_tags', 'tag_id', 'record_id')->wherePivot('deleted_flag', 0)->where('challenge_records.deleted_flag', 0);
    }
    public function niceRecords()
    {
        return $this->belongsToMany(NiceRecord::class, 'nice_use_tags', 'tag_id', 'record_id')->wherePivot('deleted_flag', 0)->where('nice_records.deleted_flag', 0);
    }
    public function challengeOccurence()
    {
        return $this->belongsToMany(ChallengeRecord::class, 'challenge_use_tags', 'tag_id', 'record_id');
    }
    public function niceOccurence()
    {
        return $this->belongsToMany(NiceRecord::class, 'nice_use_tags', 'tag_id', 'record_id');
    }
    public function knowledgeOccurence()
    {
        return $this->belongsToMany(KnowledgeRecord::class, 'knowledge_use_tags', 'tag_id', 'record_id');
    }
    public function postRecords()
    {
        return $this->belongsToMany(PostRecord::class, 'post_use_tags', 'tag_id', 'record_id');
    }
    public function postOccurence()
    {
        return $this->belongsToMany(PostRecord::class, 'post_use_tags', 'tag_id', 'record_id');
    }
    protected $casts = [
        'hits' => 'int', 
    ];
    protected $guarded = [];
}
