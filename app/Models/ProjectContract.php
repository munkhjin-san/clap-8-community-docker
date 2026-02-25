<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectContract extends Model
{
     protected $guarded = [];

     public function project(): BelongsTo
     {
        return $this->belongsTo(ProjectRecord::class, 'project_record_id');
     }
    protected $casts = [
        'result_json' => 'array', 
    ];
}
