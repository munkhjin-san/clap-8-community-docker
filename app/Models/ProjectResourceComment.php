<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectResourceComment extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function author() {
        return $this->belongsTo(User::class, 'user_id');
    }

    // 収支コメントと共用の project_comment_reminds を使う
    public function remindUsers()
    {
        return $this->morphMany(ProjectCommentRemind::class, 'comment')->where('reminded', 1);
    }

    protected $casts = [
        'period' => 'string',
    ];
}
