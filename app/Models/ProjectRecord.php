<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class ProjectRecord extends Model
{
    use HasFactory;
    use SoftDeletes;

    public function members(){
        return $this->belongsToMany(User::class, 'project_members', 'project_id', 'user_id')->wherePivot('authority', 0)->select(['users.id as id', 'users.name','users.icon_path','users.icon_bg', 'users.user_code', 'users.work_authority', 'users.position_id', 'users.icon_bg', 'users.general_position', 'users.work_type', 'users.work_time_day'])->withPivot(['authority', 'id'])->with(['positions:id,name']);
    }

    public function manager(){
        return $this->belongsToMany(User::class, 'project_members', 'project_id', 'user_id')->wherePivot('authority', 1)->select(['users.id as id', 'users.name','users.icon_path','users.icon_bg', 'users.user_code', 'users.work_authority', 'users.position_id', 'users.icon_bg', 'users.general_position', 'users.work_type', 'users.work_time_day'])->withPivot(['authority', 'id'])->with('positions');
    }

    public function director(){
        return $this->hasOne(User::class, 'id', 'director_id')->select('id', 'name', 'icon_path', 'icon_bg');
    }
    public function goals() {
        return $this->hasMany(ProjectGoal::class, 'project_id', 'id');
    }
    public function cases()
    {
        return $this->hasMany(ProjectCase::class, 'project_record_id');
    }
    public function tasks(){
        return $this->hasMany(taskRecord::class);
    }
    public function project_conditions() {
        return $this->hasMany(ProjectCondition::class);
    }
    protected $guarded = [];

    protected $casts = [
        'category' => 'array',
        'partners' => 'array',
        'customers' => 'array',
        'industry_type' => 'array',
        'date_start' => 'date',
        'date_end' => 'date',
    ];

    public function scopeActiveOn(Builder $q, ?Carbon $day = null): Builder
    {
        $day ??= now('Asia/Tokyo')->startOfDay();

        return $q->whereDate('date_start', '<=', $day)
                ->where(function ($q) use ($day) {
                    $q->whereNull('date_end')
                      ->orWhereDate('date_end', '>=', $day);
                });
    }

    public function scopeOverlapping(Builder $q, Carbon $start, Carbon $end): Builder
    {
        return $q->whereDate('date_start', '<=', $end)
                 ->where(function ($q) use ($start) {
                     $q->whereNull('date_end')
                       ->orWhereDate('date_end', '>=', $start);
                 });
    }
}
