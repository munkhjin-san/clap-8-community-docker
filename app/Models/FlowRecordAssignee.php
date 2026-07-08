<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlowRecordAssignee extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function record()
    {
        return $this->belongsTo(FlowRecord::class, 'flow_record_id', 'id');
    }

    public function status()
    {
        return $this->belongsTo(FlowStatus::class, 'flow_status_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')
            ->select('id', 'name', 'icon_path', 'icon_bg', 'position_id');
    }
}
