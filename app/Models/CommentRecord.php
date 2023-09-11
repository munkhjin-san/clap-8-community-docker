<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommentRecord extends Model
{
    use HasFactory;

    public function user(){
        return $this->belongsTo(User::class)->select('id', 'name', 'icon_id');
    }

    protected $fillable = [
        'messages'
    ];
    protected $casts = [
        'user_id' => 'int',  
        'record_id' => 'int',
        'comment_id' => 'int',      
        
    ];
}
