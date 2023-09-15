<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class boardRecord extends Model
{   
    use SoftDeletes;  

   
    //ユーザー情報取得リレーション
    public function user(){
        return $this->belongsTo(User::class)->select('id', 'name', 'icon_id');
    }
    public function board_to_users(){
        return $this->hasMany(boardToUser::class, 'record_id');
    }
    public function messages(){
        return $this->hasMany(messageRecord::class, 'record_id');
    }
    public function message_records(){
        return $this->belongsTo(messageRecord::class, 'id');
    }
    public function last_message(){
        return $this->hasOne(messageRecord::class, 'record_id')->latest('created_at')->select('id', 'message', 'message_text', 'record_id');
    }
    public function icons(){
        return $this->hasOne(Icons::class, 'id', 'icon_id');
    }
    protected $casts = [        
        'user_id' => 'int',  
        'icon_id' => 'int', 
        'private_flag' => 'int', 
        'app_type' => 'int'        
         
    ];
    protected $fillable = [
        'q_token',
        'icon_id'
    ];
}
