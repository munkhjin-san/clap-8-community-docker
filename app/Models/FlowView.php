<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlowView extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'columns' => 'array',
        'filters' => 'array',
        'sort' => 'array',
        'is_default' => 'boolean',
    ];

    public function definition()
    {
        return $this->belongsTo(FlowDefinition::class, 'flow_definition_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id')
            ->select('id', 'name', 'icon_path', 'icon_bg');
    }
}
