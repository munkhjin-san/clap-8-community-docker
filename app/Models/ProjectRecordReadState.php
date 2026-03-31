<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectRecordReadState extends Model
{
    protected $fillable = [
        'user_id',
        'project_record_id',
        'last_seen_at',
        'type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function projectRecord()
    {
        return $this->belongsTo(ProjectRecord::class);
    }
}
