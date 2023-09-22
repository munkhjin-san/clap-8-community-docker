<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClapRecord extends Model
{
    use SoftDeletes;
    use HasFactory;
    protected $fillable = [
        'deleted_flag', 'from_user', 'record_id', 'app_name'
    ];
}
