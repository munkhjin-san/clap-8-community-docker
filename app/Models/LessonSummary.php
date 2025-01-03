<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonSummary extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    public function questions()
    {
        return $this->hasMany(LessonSummaryQuestion::class, 'lesson_summary_id');
    }
    public function answers()
    {
        return $this->hasMany(LessonSummaryAnswer::class, 'lesson_summary_id')->select('id', 'lesson_summary_question_id', 'answer_val', 'lesson_summary_id', 'user_id');
    }
}
