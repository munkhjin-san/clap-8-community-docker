<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskComment extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function user(){
        return $this->belongsTo(User::class)->select('id', 'name', 'icon_path', 'icon_bg');
    }
    public function taskRecord() {
        return $this->belongsTo(taskRecord::class);
    }
    public function taskUsers() {
        return $this->belongsTo(taskUser::class, 'user_id', 'user_id');
    }
}
