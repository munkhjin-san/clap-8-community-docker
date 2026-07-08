<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlowRecordPermissionSet extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function definition()
    {
        return $this->belongsTo(FlowDefinition::class, 'flow_definition_id', 'id');
    }

    public function conditions()
    {
        return $this->hasMany(FlowRecordPermissionCondition::class, 'set_id', 'id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function grants()
    {
        return $this->hasMany(FlowRecordPermissionGrant::class, 'set_id', 'id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
