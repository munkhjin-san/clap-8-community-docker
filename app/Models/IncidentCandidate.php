<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentCandidate extends Model
{
    public const SOURCE_DAILY_REPORT_STREAK = 'daily_report_streak';
    public const SOURCE_GOAL_SUBMISSION = 'outcome_goal_submission';
    public const SOURCE_GOAL_PM_APPROVAL = 'outcome_goal_pm_approval';

    public const AUDIENCE_PM = 'pm';
    public const AUDIENCE_DIRECTOR = 'director';

    public const STATUS_PENDING = 'pending';
    public const STATUS_INCIDENT_CREATED = 'incident_created';
    public const STATUS_DISMISSED = 'dismissed';

    protected $guarded = [];

    protected $casts = [
        'context' => 'array',
        'decided_at' => 'datetime',
    ];

    public function subject()
    {
        return $this->belongsTo(User::class, 'subject_user_id')
            ->select('id', 'name', 'icon_path', 'icon_bg', 'position_id');
    }

    public function project()
    {
        return $this->belongsTo(ProjectRecord::class, 'project_record_id')
            ->select('id', 'name');
    }

    public function decidedByUser()
    {
        return $this->belongsTo(User::class, 'decided_by')
            ->select('id', 'name', 'icon_path', 'icon_bg');
    }

    public function resultingIncident()
    {
        return $this->belongsTo(Incident::class, 'resulting_incident_id');
    }

    public function logs()
    {
        return $this->morphMany(UpdateLog::class, 'loggable');
    }
    public function readHistories()
    {
        return $this->morphMany(UserReadHistory::class, 'readable');
    }
}
