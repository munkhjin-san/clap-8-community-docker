<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class userDetail extends Model
{   
    use SoftDeletes;
    protected $casts = [
        'user_id'    => 'int',   
    ];
    protected $fillable = [
        'profession', 'occupation', 'company', 'intro'
    ];
}
