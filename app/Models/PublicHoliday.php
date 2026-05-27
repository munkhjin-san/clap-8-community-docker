<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicHoliday extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'holiday_name',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];
}