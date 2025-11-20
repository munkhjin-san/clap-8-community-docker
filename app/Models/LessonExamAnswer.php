<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonExamAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_exam_attempt_id',
        'lesson_exam_question_id',
        'lesson_exam_option_id',
        'is_correct',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function attempt()
    {
        return $this->belongsTo(LessonExamAttempt::class, 'lesson_exam_attempt_id');
    }

    public function question()
    {
        return $this->belongsTo(LessonExamQuestion::class, 'lesson_exam_question_id');
    }

    public function option()
    {
        return $this->belongsTo(LessonExamOption::class, 'lesson_exam_option_id');
    }
}
