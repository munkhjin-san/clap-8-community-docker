<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlowAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'meta' => 'array',
    ];

    public function definition()
    {
        return $this->belongsTo(FlowDefinition::class, 'flow_definition_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')
            ->select('id', 'name', 'icon_path', 'icon_bg');
    }

    public function record()
    {
        return $this->belongsTo(FlowRecord::class, 'flow_record_id')
            ->select('id', 'record_number', 'flow_definition_id');
    }
}
