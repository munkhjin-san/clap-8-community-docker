<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonExamOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_exam_question_id',
        'label',
        'is_correct',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function question()
    {
        return $this->belongsTo(LessonExamQuestion::class, 'lesson_exam_question_id');
    }
}
