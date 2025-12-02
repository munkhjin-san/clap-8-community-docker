<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectFinanceLastRead extends Model
{
    protected $fillable = [
        'user_id',
        'project_record_id',
        'last_read_at',
        'period'
    ];
}
