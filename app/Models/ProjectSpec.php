<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectSpec extends Model
{
    protected $guarded = [];
    protected $casts = [
        'spec_data' => 'array',
        'plan_data' => 'array'
    ];

    public function project()
    {
        return $this->belongsTo(ProjectRecord::class, 'project_id');
    }

    public function files() {
        return $this->belongsToMany(FileRecord::class, 'project_spec_reference_files', 'project_spec_id', 'file_id');
    }
}
