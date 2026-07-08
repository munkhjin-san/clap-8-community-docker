<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlowStatus extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_initial' => 'boolean',
    ];

    public function definition()
    {
        return $this->belongsTo(FlowDefinition::class, 'flow_definition_id', 'id');
    }

    public function fieldRules()
    {
        return $this->hasMany(FlowStatusFieldRule::class, 'flow_status_id', 'id');
    }

    public function actions()
    {
        return $this->hasMany(FlowStatusAction::class, 'flow_status_id', 'id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
