<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectMemberRole extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $casts = [
        'work_conditions' => 'array',
    ];

    protected $attributes = [
        'work_conditions' => '[]',
    ];
    protected $guarded = [];

    public function getWorkConditionsAttribute($value): array
    {
        return $value ? json_decode($value, true) : [];
    }

    public function projectRecord()
    {
        return $this->belongsTo(ProjectRecord::class, 'project_record_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
