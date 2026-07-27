<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActualResultDepartment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'source_departments' => 'array',
        'metrics' => 'array',
        'accounts' => 'array',
        'manual_adjusted' => 'boolean',
        'real_margin' => 'float',
    ];

    public function report()
    {
        return $this->belongsTo(ActualResultReport::class, 'actual_result_report_id');
    }

    public function project()
    {
        return $this->belongsTo(ProjectRecord::class, 'project_record_id');
    }
}
