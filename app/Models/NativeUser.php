<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NativeUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'fcm_token', 'user_id', 'device_id', 'model'
    ];
}
