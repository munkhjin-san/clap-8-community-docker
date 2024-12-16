<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class taskUser extends Model
{   
    use SoftDeletes;
    use HasFactory;
    protected $guarded = [];
    public function user(){
        return $this->hasOne(User::class, 'id', 'user_id')->select('id', 'name', 'icon_id');
    }
    public function taskRecord()
    {
        return $this->belongsTo(TaskRecord::class, 'id', 'record_id');
    }

    public function unreadComments()
    {
        return $this->taskRecord->comments()
                    ->where('created_at', '>', $this->checked_at);
    }
       
}
