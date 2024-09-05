<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommentRecord extends Model
{
    use HasFactory;

    public function user(){
        return $this->belongsTo(User::class)->select('id', 'name', 'icon_id', 'icon_id');
    }
    public function claps(){
        return $this->hasMany(ClapRecord::class, 'record_id')->where('app_id', 5)->where('deleted_flag', 0)->select('record_id', 'from_user')->with('user');
    }
    protected $guarded = [];
    protected $casts = [
        'user_id' => 'int',  
        'record_id' => 'int',
        'comment_id' => 'int',      
        
    ];
}
