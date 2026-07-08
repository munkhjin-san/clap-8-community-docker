<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlowField extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'options' => 'array',
        'depends_on' => 'array',
        'validation' => 'array',
        'is_required' => 'boolean',
        'hidden' => 'boolean',
    ];

    public function definition()
    {
        return $this->belongsTo(FlowDefinition::class, 'flow_definition_id', 'id');
    }
}
