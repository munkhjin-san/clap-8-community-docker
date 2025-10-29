<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectMetricValue extends Model
{
    protected $fillable = ['project_record_id','period','project_metric_id','value','source','calc_version'];
    protected $casts = ['period'=>'date', 'value'  => 'float',];

    public function metric()   { return $this->belongsTo(ProjectMetric::class, 'project_metric_id'); }
    public function project()  { return $this->belongsTo(ProjectRecord::class, 'project_record_id'); }
}
