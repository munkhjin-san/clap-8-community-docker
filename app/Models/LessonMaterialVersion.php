<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonMaterialVersion extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function theme()
    {
        return $this->belongsTo(LessonTheme::class, 'lesson_theme_id');
    }

    public function materials()
    {
        return $this->hasMany(LessonMaterial::class, 'lesson_material_version_id');
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
