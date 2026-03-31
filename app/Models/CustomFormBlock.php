<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomFormBlock extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        "is_required" => 'boolean',
        "depends_on" => 'array',
        "categories" => 'array',
    ];
    public function elements(){
        return $this->hasMany(CustomFormBlockElement::class);
    }

    public function answers() {
        return $this->hasMany(SurveyBlockAnswer::class);
    }

    public function checkitemCategories()
    {
        return $this->belongsToMany(
            ProjectCheckitemCategory::class,
            'custom_form_block_project_checkitem_category'
        )->orderBy('sort_order');
    }
}
