<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class LessonPersonalMaterial extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden = [
        'presentation_path',
    ];

    protected $appends = [
        'presentation_available',
    ];

    protected $casts = [
        'presentation_spec' => 'array',
        'source_snapshot' => 'array',
        'understand' => 'boolean',
        'generated_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function theme()
    {
        return $this->belongsTo(LessonTheme::class, 'lesson_theme_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function aiConfig()
    {
        return $this->belongsTo(LessonThemeAiConfig::class, 'lesson_theme_ai_config_id');
    }

    public function getPresentationAvailableAttribute(): bool
    {
        return filled($this->presentation_path)
            && Storage::disk('local')->exists($this->presentation_path);
    }
}
