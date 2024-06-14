<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonPortfolio extends Model
{
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->select('id', 'name', 'icon_id');
    }
    public function lesson_sections()
    {
        return $this->hasMany(LessonSection::class, 'portfolio_id', 'id')->with('lesson_material');
    }
    public function lesson_theme()
    {
        return $this->hasOne(LessonTheme::class, 'id', 'lesson_theme_id')->select('id', 'title');
    }
    public function lesson_form()
    {
        return $this->hasOne(LessonForm::class, 'user_id', 'user_id')->latest();
    }

    public function claps(){
        return $this->hasMany(ClapRecord::class, 'record_id')->where('app_id', 6)->where('deleted_flag', 0)->select('record_id', 'from_user');;
    }
    protected $guarded = [];

}
