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
    protected $casts = [
        'period' => 'date',
    ];
}
