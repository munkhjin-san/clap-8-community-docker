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
    protected $fillable = [
        'lesson_theme_id',
        'user_id',
        'content',
        'title',
        'status',
        'basic_knowledge',
        'positive_feedback',
        'negative_feedback',
        'understand',
        'not_understand_content',
        'portfolio_title'
    ];

}
