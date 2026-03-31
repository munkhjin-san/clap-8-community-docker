<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectCheckitemTemplate extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function projectType()
    {
        return $this->belongsTo(ProjectType::class);
    }

    public function category()
    {
        return $this->belongsTo(ProjectCheckitemCategory::class, 'project_checkitem_category_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }
}
