<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectMetric extends Model
{
    protected $fillable = ['label_ja','kind','value_type','line','is_active','sort_order','scenario_label_ja'];
    public function formula() { return $this->hasOne(ProjectMetricFormula::class); }
    public function values() { return $this->hasMany(ProjectMetricValue::class); }
    public function displayConfig() { return $this->hasOne(ProjectMetricDisplayConfig::class); }

    public function subMetrics()
    {
        return $this->hasMany(ProjectMetricSubMetric::class);
    }

    public function valuesForProject(int $projectId)
    {
        return $this->values()->where('project_record_id', $projectId);
    }

}
