<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class CustomFormBlockElement extends Model
{
    use HasFactory, SoftDeletes;

    public function answers() {
        return $this->hasMany(SurveyBlockElementAnswer::class);
    }
    protected $guarded = [];

    protected $casts = [
        "has_sub_text" => 'boolean',
        "has_sub_text_required" => 'boolean',
        "is_required" => 'boolean'
    ];

}
