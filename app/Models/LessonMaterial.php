<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class LessonMaterial extends Model
{
    use HasFactory, SoftDeletes;

    public function section_status()
    {
        return $this->hasOne(LessonSection::class, 'material_id');
    }
    public function answer(){
        return $this->hasOne(LessonAnswer::class, 'material_id');
    }
    public function answers() {
        return $this->hasMany(LessonAnswer::class, 'material_id');
    }
    public function summaries() {
        return $this->hasMany(LessonSummary::class, 'lesson_material_id');
    }
    protected $fillable = [
        'lesson_theme_id',
        'user_id',
        'title',
        'content_detailed',
        'content',
        'has_feedback',
        'updated_by',
        'priority',
        'has_question',
        'has_understand',
        'assistant_id',
        'material_type'
    ];
}
