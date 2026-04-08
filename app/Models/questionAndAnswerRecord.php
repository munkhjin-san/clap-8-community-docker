<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class questionAndAnswerRecord extends Model
{  
    protected $fillable = [
        'user_id',
        'question',
        'answer',
        'content',
        'tag_text',
        'deleted_flag',
        'useful_count',
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

}