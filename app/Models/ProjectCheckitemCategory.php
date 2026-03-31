<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectCheckitemCategory extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function templates()
    {
        return $this->hasMany(ProjectCheckitemTemplate::class);
    }

    public function checkitems()
    {
        return $this->hasMany(ProjectCheckitems::class, 'project_checkitem_category_id');
    }

    public function formBlocks()
    {
        return $this->belongsToMany(
            CustomFormBlock::class,
            'custom_form_block_project_checkitem_category'
        );
    }
}
