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
    protected $casts = [
        'period' => 'date',
    ];
}
