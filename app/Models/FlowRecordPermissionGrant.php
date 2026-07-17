<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlowRecordPermissionGrant extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'can_view' => 'boolean',
        'can_edit' => 'boolean',
        'can_delete' => 'boolean',
    ];

    public function set()
    {
        return $this->belongsTo(FlowRecordPermissionSet::class, 'set_id', 'id');
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

    public function field()
    {
        return $this->belongsTo(FlowField::class, 'subject_id', 'id');
    }
}
