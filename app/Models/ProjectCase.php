<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectCase extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'report_date'  => 'date',
        'submitted_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectRecord::class, 'project_record_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeForMonth($query, Carbon $month)
    {
        return $query->whereDate('report_date', $month->copy()->startOfMonth());
    }
}

