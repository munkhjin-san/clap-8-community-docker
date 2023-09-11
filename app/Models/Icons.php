<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Icons extends Model
{   
    use SoftDeletes;
    public function user(){
        // return $this->belongsTo(User::class, 'icon_id', 'id');
        return $this->belongsTo(User::class);
        // return $this->belongsTo(User::class, 'icon_id');

    }
    public function board_record(){
        // return $this->belongsTo(User::class, 'icon_id', 'id');
        return $this->belongsTo(boardRecord::class, 'icon_id', 'id');
        // return $this->belongsTo(User::class, 'icon_id');

    }
    protected $casts = [
        
        'record_id' => 'int',
        'user_id' => 'int',       
        'profile_id' => 'int',         
        
    ];
    protected $fillable = [
        'record_id'
    ];
}
