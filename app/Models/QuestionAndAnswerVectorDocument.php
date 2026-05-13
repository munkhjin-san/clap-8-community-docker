<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionAndAnswerVectorDocument extends Model
{
    protected $fillable = [
        'question_and_answer_record_id',
        'markdown_path',
        'markdown_copy_path',
        'openai_file_id',
        'vector_store_file_id',
    ];

    protected $casts = [
        'question_and_answer_record_id' => 'integer',
    ];

    public function record()
    {
        return $this->belongsTo(questionAndAnswerRecord::class, 'question_and_answer_record_id');
    }
}
