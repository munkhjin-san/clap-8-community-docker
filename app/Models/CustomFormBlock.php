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
        "is_required" => 'boolean'
    ];
    public function elements(){
        return $this->hasMany(CustomFormBlockElement::class);
    }

    public function answers() {
        return $this->hasMany(SurveyBlockAnswer::class);
    }
}
