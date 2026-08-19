<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A learner's signed copy of a theme's 誓約書 (pledge document).
 */
class LessonPledgeSignature extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function theme()
    {
        return $this->belongsTo(LessonTheme::class, 'lesson_theme_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
