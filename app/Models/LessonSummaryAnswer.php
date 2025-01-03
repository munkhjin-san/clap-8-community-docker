<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonSummaryAnswer extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $guarded = [];
    public function lesson_summary()
    {
        return $this->belongsTo(LessonSummary::class, 'lesson_summary_id');
    }
    public function lesson_summary_question()
    {
        return $this->belongsTo(LessonSummaryQuestion::class, 'lesson_summary_question_id');
    }
}
