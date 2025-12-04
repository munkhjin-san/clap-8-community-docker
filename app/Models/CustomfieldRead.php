<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomfieldRead extends Model
{
    protected $fillable = [
        'user_id',
        'type_id',
        'last_read_customfield_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
