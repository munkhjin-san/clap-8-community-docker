<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlowStatusFieldRule extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function status()
    {
        return $this->belongsTo(FlowStatus::class, 'flow_status_id', 'id');
    }

    public function field()
    {
        return $this->belongsTo(FlowField::class, 'flow_field_id', 'id');
    }
}
