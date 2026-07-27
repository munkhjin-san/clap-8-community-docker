<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectContract extends Model
{
    protected $fillable = [
        'project_record_id',
        'review_type',
        'overall_risk',
        'findings_count',
        'result_json',
        'response_hash',
        'file_path',
        'role',
        'contract_type',
        'version',
        'active',
    ];

    protected $casts = [
        'result_json' => 'array',
        'active' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectRecord::class, 'project_record_id');
    }
}
