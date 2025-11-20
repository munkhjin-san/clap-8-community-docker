<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonExamAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_exam_id',
        'user_id',
        'score',
        'attempt_number',
        'status',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function exam()
    {
        return $this->belongsTo(LessonExam::class, 'lesson_exam_id');
    }

    public function answers()
    {
        return $this->hasMany(LessonExamAnswer::class, 'lesson_exam_attempt_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
