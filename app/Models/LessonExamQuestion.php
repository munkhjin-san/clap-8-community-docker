<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonExamQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_exam_id',
        'prompt',
        'explanation',
        'correct_explanation',
        'position',
    ];

    public function exam()
    {
        return $this->belongsTo(LessonExam::class, 'lesson_exam_id');
    }

    public function options()
    {
        return $this->hasMany(LessonExamOption::class);
    }
}
