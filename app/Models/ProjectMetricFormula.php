<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectMetricFormula extends Model
{
    protected $fillable = ['project_metric_id','expression','version'];
    public function metric() { return $this->belongsTo(ProjectMetric::class); }
}
