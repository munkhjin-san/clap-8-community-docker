<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectCommentRemind extends Model
{
    protected $guarded = [];

    protected $casts = [
        'reminded' => 'boolean',
    ];

    public function comment()
    {
        return $this->morphTo();
    }
}
