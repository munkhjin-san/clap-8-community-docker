<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLastRecord extends Model
{
    use HasFactory;
    protected $fillable = [
        'last_knowledge', 'last_nice', 'last_challenge'
    ];
}
