<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectFinanceComment extends Model
{
    use SoftDeletes;
    protected $guarded = [];
    public function project() {
        return $this->belongsTo(ProjectRecord::class, 'project_record_id');
    }

    public function author() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function readers(){
        return $this->hasMany(ProjectFinanceLastRead::class, 'comment_id');
    }
    public function checkedUsers()
    {
        return $this->belongsToMany(User::class, 'project_finance_comment_checks', 'comment_id')->select('users.id', 'users.name', 'users.icon_path', 'users.icon_bg', 'users.deleted_at');
    } 
    public function reply() {
        return $this->hasOne(ProjectFinanceComment::class, 'id', 'reply_id')->with(['author:id,name,icon_path,icon_bg']);
    }
    // messageRecord::messageRemindUsers と同じく、リマインド中の行だけを返す
    public function remindUsers()
    {
        return $this->morphMany(ProjectCommentRemind::class, 'comment')->where('reminded', 1);
    }
    protected $casts = [
        'period' => 'string',
    ];
}
