<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlowRecordValue extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'value_date' => 'date',
        'value_datetime' => 'datetime',
        'value_boolean' => 'boolean',
        'value_json' => 'array',
    ];

    public function record()
    {
        return $this->belongsTo(FlowRecord::class, 'flow_record_id', 'id');
    }

    public function field()
    {
        return $this->belongsTo(FlowField::class, 'flow_field_id', 'id');
    }
}
