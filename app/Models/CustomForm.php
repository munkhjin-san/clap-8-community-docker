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
        return $this->hasMany(CustomFormBlock::class)->orderBy('order_number')->with(['elements', 'checkitemCategories']);
    }
    public function survey_answers() {
        return $this->hasMany(SurveyAnswer::class);
    }
    public function users() {
        return $this->belongsToMany(User::class, 'custom_form_users', 'custom_form_id', 'user_id')->wherePivot('authority', 0)->withPivot(['try_flag', 'prize'])->select('users.id', 'users.name', 'users.icon_path', 'users.icon_bg');
    }
    public function admins() {
        return $this->belongsToMany(User::class, 'custom_form_users', 'custom_form_id', 'user_id')->wherePivot('authority', 1)->withPivot(['try_flag', 'prize'])->select('users.id', 'users.name', 'users.icon_path', 'users.icon_bg');;
    }
    public function projectType()
    {
        return $this->belongsTo(ProjectType::class);
    }

    protected $casts = [
        'has_prize' => 'boolean',
    ];
}
