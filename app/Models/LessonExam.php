<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonExam extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_theme_id',
        'title',
        'description',
        'passing_score',
        'max_attempts',
        'created_by',
        'updated_by',
    ];

    public function theme()
    {
        return $this->belongsTo(LessonTheme::class, 'lesson_theme_id');
    }

    public function questions()
    {
        return $this->hasMany(LessonExamQuestion::class)->orderBy('position');
    }

    public function attempts()
    {
        return $this->hasMany(LessonExamAttempt::class);
    }
}
