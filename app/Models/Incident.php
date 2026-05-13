<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Incident extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    public function reportedByUser()
    {
        return $this->belongsTo(User::class, 'reported_by', 'id')
            ->select('id', 'name', 'icon_path', 'icon_bg');
    }

    public function causedByUser()
    {
        return $this->belongsTo(User::class, 'caused_by', 'id')
            ->select('id', 'name', 'icon_path', 'icon_bg');
    }

    public function category()
    {
        return $this->belongsTo(IncidentCategory::class, 'incident_category_id', 'id');
    }

    public function punishment()
    {
        return $this->belongsTo(IncidentPunishment::class, 'incident_punishment_id', 'id');
    }

    public function projectRecord()
    {
        return $this->belongsTo(ProjectRecord::class, 'project_record_id', 'id');
    }

    public function reports()
    {
        return $this->hasMany(IncidentReport::class, 'incident_id', 'id')->with('user');
    }
}
