<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlowStatusAction extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'eligible' => 'array',
    ];

    public function status()
    {
        return $this->belongsTo(FlowStatus::class, 'flow_status_id', 'id');
    }

    public function toStatus()
    {
        return $this->belongsTo(FlowStatus::class, 'to_status_id', 'id');
    }
}
