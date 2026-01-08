<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectAccount extends Model
{
    protected $guarded = [];

    public function project()
    {
        return $this->belongsTo(ProjectRecord::class, 'project_record_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function planAmounts()
    {
        return $this->hasMany(ProjectPlanAmount::class, 'project_account_id');
    }
}
