<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectResourceComment extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function author() {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected $casts = [
        'period' => 'string',
    ];
}
