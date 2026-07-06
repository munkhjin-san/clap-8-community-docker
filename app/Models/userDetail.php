<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCommunity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class userDetail extends Model
{
    use BelongsToCommunity;
   
    use SoftDeletes;
    protected $casts = [
        'user_id'    => 'int',   
    ];

    protected $guarded = [];
}
