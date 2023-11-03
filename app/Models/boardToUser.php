<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class boardToUser extends Model
{   
    use SoftDeletes;
    public function user(){
        return $this->belongsTo(User::class)->select('id', 'name', 'icon_id', 'name_kana');
    }
    public function board_records(){
        return $this->belongsTo(boardRecord::class, 'id');
    }
    public function board(){
        return $this->belongsTo(boardRecord::class, 'record_id', 'id');
    }
    public function messageRecords(){
        return $this->hasMany(messageRecord::class, 'record_id', 'record_id')->withTrashed();
    }
    protected $casts = [
        'admin_flag'    => 'int', 
        'record_id' => 'int',
        'user_id' => 'int',     
        'deleted_status' => 'int',
        'last_message' => 'int',    
        'last_task' => 'int',       
        'pin_flag' => 'int',
    ];
    protected $fillable = ['user_id', 'admin_flag', 'last_message', 'last_act'];
}
