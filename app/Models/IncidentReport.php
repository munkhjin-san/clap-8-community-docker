<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncidentReport extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function incident()
    {
        return $this->belongsTo(Incident::class, 'incident_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')
            ->select('id', 'name', 'icon_path', 'icon_bg');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id')
            ->select('id', 'name', 'icon_path', 'icon_bg');
    }

    public function assignees()
    {
        return $this->hasMany(IncidentAssignee::class, 'incident_report_id', 'id')
            ->with('user')
            ->orderBy('id');
    }
}
