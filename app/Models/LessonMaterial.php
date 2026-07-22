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
    public function theme() {
        return $this->belongsTo(LessonTheme::class, 'lesson_theme_id');
    }
    public function version() {
        return $this->belongsTo(LessonMaterialVersion::class, 'lesson_material_version_id');
    }
    public function exam() {
        return $this->hasOne(LessonExam::class, 'lesson_material_id');
    }

    // Active content = not retired. Retired materials are kept for history
    // (lesson_answers / lesson_sections reference material_id) but hidden
    // from new/first-time learners.
    public function scopeActive($query)
    {
        return $query->whereNull('retired_at');
    }
    public function scopeRetired($query)
    {
        return $query->whereNotNull('retired_at');
    }

    protected $casts = [
        'retired_at' => 'datetime',
    ];
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
        'has_exam',
        'prompt_id',
        'material_type',
        'retired_at',
        'lesson_material_version_id',
    ];
}
