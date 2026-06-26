<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToCommunity;
use Carbon\Carbon;

class ProjectGoal extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToCommunity;

    protected $guarded = [];

    public function project(){
        return $this->belongsTo(ProjectRecord::class);
    }

    public function salaryIssue(){
        return $this->hasOne(SalaryIssue::class);
    }
    public function files() {
        return $this->belongsToMany(FileRecord::class, 'project_use_files', 'project_goal_id', 'file_id');
    }
    public function steps() {
        return $this->hasMany(ProjectGoalStep::class);
    }
    public function reports() {
        return $this->hasMany(ProjectGoalReport::class)->with(['files']);
    }

    public function statusLogs()
    {
        return $this->hasMany(StatusLog::class, 'record_id', 'id')
            ->where('type', 'project_goal')->orderBy('created_at', 'desc');
    }

    public function scopeOverdue($q, $now)
    {
        return $q->where('status', '!=', 9)
                ->where('end_date', '<', $now);
    }

    public function scopeRelevantToViewer($q, $userId)
    {
        return $q->where(function ($s) use ($userId) {
            $s->where('user_id', $userId)
            ->orWhereHas('project.manager', fn($m) => $m->where('users.id', $userId))
            ->orWhereHas('salaryIssue', fn($si) => $si->where('mentor_id', $userId));
        });
    }
    public function user(){
        return $this->belongsTo(User::class);
    }
    public function scopeInAllowedHalves($q, array $periods)
    {
        return $q->where(function ($w) use ($periods) {
            foreach ($periods as $i => $p) {
                $method = $i === 0 ? 'where' : 'orWhere';
                $w->{$method}(fn($z) => $z->where('year', $p['year'])->where('which_half', $p['which_half']));
            }
        });
    }

    protected $appends = ['due_plus_7'];

    public function getDuePlus7Attribute()
    {
        if (!$this->end_date) return null;
        return Carbon::parse($this->end_date)->addDays(7)->toDateString();
    }
    public function goal_notifications(){
        return $this->hasMany(ProjectMemberReportNotification::class);
    }

}
