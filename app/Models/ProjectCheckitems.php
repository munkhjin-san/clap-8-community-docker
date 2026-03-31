<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectCheckitems extends Model
{
    protected $guarded = [];

    use SoftDeletes;

    protected $casts = [
        'is_applicable' => 'boolean',
    ];

    public function check_user()
    {
        return $this->belongsTo(User::class, 'checked_by')->select('id', 'icon_path', 'icon_bg', 'name');
    }
    public function link_user()
    {
        return $this->belongsTo(User::class, 'linked_by')->select('id', 'icon_path', 'icon_bg', 'name');
    }
    public function parent()
    {
        return $this->belongsTo(ProjectCheckitems::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ProjectCheckitems::class, 'parent_id')
            ->where('is_applicable', true)
            ->orderBy('sort_order');
    }

    public function categoryRecord()
    {
        return $this->belongsTo(ProjectCheckitemCategory::class, 'project_checkitem_category_id');
    }

    public function template()
    {
        return $this->belongsTo(ProjectCheckitemTemplate::class, 'project_checkitem_template_id');
    }
}   
