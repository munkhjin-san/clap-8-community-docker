<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlowFieldPermission extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'can_view' => 'boolean',
        'can_edit' => 'boolean',
    ];

    public function definition()
    {
        return $this->belongsTo(FlowDefinition::class, 'flow_definition_id', 'id');
    }

    public function field()
    {
        return $this->belongsTo(FlowField::class, 'field_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'subject_id', 'id')
            ->select('id', 'name', 'icon_path', 'icon_bg', 'position_id');
    }

    public function position()
    {
        return $this->belongsTo(positionRecord::class, 'subject_id', 'id');
    }
}
