<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SystemUpdateRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'scheduled_start_at' => 'datetime',
        'scheduled_end_at' => 'datetime',
        'must_read' => 'boolean',
    ];

    public function details()
    {
        return $this->hasMany(SystemUpdateDetail::class)->orderBy('sort_order')->orderBy('id');
    }

    public function user()
    {
        return $this->belongsTo(User::class)->select('id', 'name', 'icon_path', 'icon_bg');
    }

    public function systemUpdateChecks()
    {
        return $this->hasMany(SystemUpdateCheck::class);
    }
}
