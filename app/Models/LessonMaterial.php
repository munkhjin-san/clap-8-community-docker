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
    protected $fillable = [
        'lesson_theme_id',
        'user_id',
        'title',
        'content_detailed',
        'content',
        'has_feedback',
        'updated_by',
        'priority'
    ];
}
