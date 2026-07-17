<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlowShare extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function definition()
    {
        return $this->belongsTo(FlowDefinition::class, 'flow_definition_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')
            ->select('id', 'name', 'icon_path', 'icon_bg', 'position_id');
    }

    public function position()
    {
        return $this->belongsTo(positionRecord::class, 'position_id', 'id');
    }
}
