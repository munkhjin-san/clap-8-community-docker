<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectCondition extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_record_id',
        'user_id',
        'value',
        'week_start_date',
    ];

    public $timestamps = true;
}
