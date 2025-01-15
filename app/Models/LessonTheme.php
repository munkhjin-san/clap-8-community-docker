<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
class LessonTheme extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $appends = ['survey_completed', 'survey_date'];
    public function lesson_portfolio(){
        return $this->hasOne(LessonPortfolio::class, 'lesson_theme_id')->select('lesson_theme_id', 'status', 'understand');
    }
    public function materials() {
        return $this->hasMany(LessonMaterial::class, 'lesson_theme_id');
    }
    public function form(){
        return $this->hasOne(CustomForm::class, 'id', 'custom_form_id');
    }

    public function getSurveyCompletedAttribute()
    {
        return $this->form?->survey_answers?->where('user_id', Auth::id())->isNotEmpty();
    }
    public function getSurveyDateAttribute()
    {
        return $this->form?->survey_answers?->where('user_id', Auth::id())->first()->updated_at;
    }
}

