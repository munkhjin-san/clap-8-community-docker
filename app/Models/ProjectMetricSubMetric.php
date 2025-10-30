<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectMetricSubMetric extends Model
{
    protected $fillable = ['project_metric_id','expression','sort_order'];

    public function metric()
    {
        return $this->belongsTo(ProjectMetric::class, 'project_metric_id');
    }
}
