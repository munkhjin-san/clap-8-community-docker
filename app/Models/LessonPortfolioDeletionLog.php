<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonPortfolioDeletionLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'snapshot' => 'array',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id')->select('id', 'name', 'icon_path', 'icon_bg');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by')->select('id', 'name', 'icon_path', 'icon_bg');
    }

    public function theme()
    {
        return $this->belongsTo(LessonTheme::class, 'lesson_theme_id')->select('id', 'title');
    }
}
