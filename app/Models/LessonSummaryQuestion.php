<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonSummaryQuestion extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $guarded = [];
    public function answer()
    {
        return $this->hasOne(LessonSummaryAnswer::class, 'lesson_summary_question_id')->select('id', 'lesson_summary_question_id', 'answer_val', 'lesson_summary_id', 'user_id');
    }
}
