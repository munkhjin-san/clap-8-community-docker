<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class LessonMaterial extends Model
{
    use HasFactory, SoftDeletes;

    public function portfolio()
    {
        return $this->hasOne(LessonPortfolio::class, 'topic_id', 'topic_id');
    }
    protected $fillable = [
        'topic_id',
        'user_id',
        'title',
        'content_detailed',
        'content',
        'has_feedback',
        'updated_by'
    ];
}
