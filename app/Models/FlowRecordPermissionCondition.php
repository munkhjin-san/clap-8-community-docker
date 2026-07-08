<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlowRecordPermissionCondition extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'values' => 'array',
    ];

    public function set()
    {
        return $this->belongsTo(FlowRecordPermissionSet::class, 'set_id', 'id');
    }

    public function field()
    {
        return $this->belongsTo(FlowField::class, 'field_id', 'id');
    }
}
