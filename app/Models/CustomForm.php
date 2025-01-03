<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomForm extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function blocks(){
        return $this->hasMany(CustomFormBlock::class)->orderBy('order_number')->with('elements');
    }
    public function survey_answers() {
        return $this->hasMany(SurveyAnswer::class);
    }
}
