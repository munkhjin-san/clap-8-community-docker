<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCommunity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FlowDefinition extends Model
{
    use HasFactory;
    use SoftDeletes;
    // Flow apps (アプリ) are an aggregate root — each belongs to one community.
    // Records/fields/statuses/assignees/shares isolate transitively via their app.
    use BelongsToCommunity;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'use_status_flow' => 'boolean',
    ];

    public function fields()
    {
        return $this->hasMany(FlowField::class, 'flow_definition_id', 'id')
            ->orderBy('order_number')
            ->orderBy('id');
    }

    public function statuses()
    {
        return $this->hasMany(FlowStatus::class, 'flow_definition_id', 'id')
            ->orderBy('order_number')
            ->orderBy('id');
    }

    public function statusActions()
    {
        return $this->hasMany(FlowStatusAction::class, 'flow_definition_id', 'id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function shares()
    {
        return $this->hasMany(FlowShare::class, 'flow_definition_id', 'id')
            ->with(['user', 'position']);
    }

    public function records()
    {
        return $this->hasMany(FlowRecord::class, 'flow_definition_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id')
            ->select('id', 'name', 'icon_path', 'icon_bg');
    }

    public function projectRecord()
    {
        return $this->belongsTo(ProjectRecord::class, 'project_record_id', 'id');
    }

    public function appPermissions()
    {
        return $this->hasMany(FlowAppPermission::class, 'flow_definition_id', 'id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function recordPermissionSets()
    {
        return $this->hasMany(FlowRecordPermissionSet::class, 'flow_definition_id', 'id')
            ->with(['conditions', 'grants'])
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function fieldPermissions()
    {
        return $this->hasMany(FlowFieldPermission::class, 'flow_definition_id', 'id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function views()
    {
        return $this->hasMany(FlowView::class, 'flow_definition_id', 'id')
            ->orderBy('id');
    }

    public function tools()
    {
        return $this->hasMany(FlowAppTool::class, 'flow_definition_id', 'id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
