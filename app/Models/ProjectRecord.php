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
        return $this->belongsToMany(User::class, 'project_members', 'project_id', 'user_id')
        ->using(ProjectMember::class)
        ->wherePivot('authority', 0)
        ->withPivot(['project_member_role_id', 'authority', 'assign_data', 'overall_assign_score'])
        ->select(['users.id as id', 'users.name','users.icon_path','users.icon_bg', 'users.user_code', 'users.work_authority', 'users.position_id', 'users.icon_bg', 'users.general_position', 'users.work_type', 'users.work_time_day'])->withPivot(['authority', 'id', 'compatibility_number', 'review'])->with(['positions:id,name']);
    }

    public function manager(){
        // return $this->belongsToMany(User::class, 'project_members', 'project_id', 'user_id')->wherePivot('authority', 1)->select(['users.id as id', 'users.name','users.icon_path','users.icon_bg', 'users.user_code', 'users.work_authority', 'users.position_id', 'users.icon_bg', 'users.general_position', 'users.work_type', 'users.work_time_day'])->withPivot(['authority', 'id', 'compatibility_number', 'review'])->with('positions');
        return $this->belongsToMany(User::class, 'project_members', 'project_id', 'user_id')
        ->using(ProjectMember::class)
        ->wherePivot('authority', 1)
        ->withPivot(['project_member_role_id', 'authority', 'assign_data', 'overall_assign_score'])
        ->select(['users.id as id', 'users.name','users.icon_path','users.icon_bg', 'users.user_code', 'users.work_authority', 'users.position_id', 'users.icon_bg', 'users.general_position', 'users.work_type', 'users.work_time_day'])->withPivot(['authority', 'id', 'compatibility_number', 'review'])->with(['positions:id,name']);
    }
    public function members_and_managers(){
        return $this->belongsToMany(User::class, 'project_members', 'project_id', 'user_id')
        ->using(ProjectMember::class)
        ->withPivot(['project_member_role_id', 'authority', 'assign_data', 'overall_assign_score'])
        ->select(['users.id as id', 'users.name','users.icon_path','users.icon_bg', 'users.user_code', 'users.work_authority', 'users.position_id', 'users.icon_bg', 'users.general_position', 'users.work_type', 'users.work_time_day'])->withPivot(['authority', 'id', 'compatibility_number', 'review'])->with(['positions:id,name']);
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
    public function contract()
    {
        return $this->hasOne(ProjectContract::class)->latestOfMany('updated_at');
    }
    public function specs()
    {
        return $this->hasOne(ProjectSpec::class, 'project_id', 'id');
    }

    public function contracts()
    {
        return $this->hasMany(ProjectContract::class)->orderByDesc('updated_at');
    }

    public function projectAssignRecords()
    {
        return $this->hasMany(ProjectAssignRecord::class, 'project_record_id');
    }

    public function projectAssignStatusHistories()
    {
        return $this->hasMany(ProjectAssignStatusHistory::class, 'project_record_id')
            ->orderByDesc('changed_at')
            ->orderByDesc('id');
    }

    public function memberRoles()
    {
        return $this->hasMany(ProjectMemberRole::class, 'project_record_id');
    }
    public function checkitems()
    {
        return $this->hasMany(ProjectCheckitems::class)
            ->where('parent_id', null)
            ->where('is_applicable', true);
    }
    public function reports()
    {
        return $this->hasMany(ProjectCheckitemsReport::class);
    }
    public function projectType()
    {
        return $this->belongsTo(ProjectType::class);
    }
    protected $guarded = [];

    protected $casts = [
        'category' => 'array',
        'partners' => 'array',
        'customers' => 'array',
        'industry_type' => 'array',
        'date_start' => 'date',
        'date_end' => 'date',
        'has_goals' => 'boolean',
        'actual_statuses' => 'array',
        'has_actual_func' => 'boolean',
        'freee_section_id' => 'integer',
        'freee_synced_at' => 'datetime',
    ];

    /**
     * freeeの部門と紐付いているか。freee_section_id の有無がそのまま同期状態。
     */
    public function isFreeeSynced(): bool
    {
        return filled($this->freee_section_id);
    }

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
    public function loadMemberRoles(): void
    {
        // Ensure members are loaded (or load them)
        $this->loadMissing('members');
        $this->loadMissing('manager');

        $allMembers = $this->members->merge($this->manager);

        $roleIds = $allMembers
            ->pluck('pivot.project_member_role_id')
            ->filter()
            ->unique()
            ->values();

        if ($roleIds->isEmpty()) return;

        $roles = ProjectMemberRole::whereIn('id', $roleIds)->get()->keyBy('id');

        foreach ($allMembers as $user) {
            $roleId = $user->pivot->project_member_role_id;
            $user->pivot->setRelation('roleRecord', $roleId ? ($roles[$roleId] ?? null) : null);
        }
    }
}
