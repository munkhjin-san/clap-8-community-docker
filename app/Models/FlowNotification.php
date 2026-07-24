<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlowNotification extends Model
{
    /** Event rows never change after insert (except read_at), no updated_at column. */
    const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'meta' => 'array',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function definition()
    {
        return $this->belongsTo(FlowDefinition::class, 'flow_definition_id');
    }

    public function record()
    {
        return $this->belongsTo(FlowRecord::class, 'flow_record_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id')->select('id', 'name', 'icon_path', 'icon_bg');
    }
}
