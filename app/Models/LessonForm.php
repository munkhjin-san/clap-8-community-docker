<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonForm extends Model
{
    use HasFactory;
    protected $fillable = [
        'topic_id',
        'user_id',
        'question1',
        'answer1',
        'question2',
        'answer2',
        'question3',
        'answer3',
    ];
}
