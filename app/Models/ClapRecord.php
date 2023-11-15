<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClapRecord extends Model
{
    use SoftDeletes;
    use HasFactory;
    protected $fillable = [
        'deleted_flag', 'from_user', 'record_id', 'app_name'
    ];
    public function user(){
        return $this->hasOne(User::class, 'id', 'from_user')->select('id', 'name');
    }
    public function challenge_records(){
        return $this->belongsTo(ChallengeRecord::class, 'id');
    }
    public function nice_records(){
        return $this->belongsTo(NiceRecord::class, 'id');
    }
    public function knowledge_records(){
        return $this->belongsTo(KnowledgeRecord::class, 'id');
    }


    public function comment_records(){
        return $this->belongsTo(CommentRecord::class);
    }
    public function message_records(){
        return $this->belongsTo(MessageRecord::class);
    }
    protected $casts = [
        'from_user' => 'int',  
        'record_id' => 'int',
        'comment_id' => 'int',      
        
    ];
}
