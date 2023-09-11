<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class appRememberRecord extends Model
{   
    use SoftDeletes;
    protected $fillable = [
        'user_id', 'favorite_tray', 'my_task_priority', 'file_sort_by', 'file_sort_desc', 'task_sort_desc'
    ];
    protected $hidden = [
        'created_at', 'updated_at'
    ];
    public function user(){
        return $this->belongsTo(User::class, 'id', 'user_id');

    }
}
