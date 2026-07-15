<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonThemeAiConfig extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'settings' => 'array',
    ];

    public function theme()
    {
        return $this->belongsTo(LessonTheme::class, 'lesson_theme_id');
    }
    public function material()
    {
        return $this->belongsTo(LessonMaterial::class, 'lesson_material_id');
    }
}
