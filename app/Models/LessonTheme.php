<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonTheme extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'discussion_date', 'active'];

    public function lesson_portfolio(){
        return $this->hasOne(LessonPortfolio::class, 'topic_id')->select('topic_id', 'status', 'understand');
    }
}
