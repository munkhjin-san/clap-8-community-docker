<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class workGroupUser extends Model
{
    use SoftDeletes;

    use HasFactory;

    public function user(){
        return $this->belongsTo(User::class)->select('name', 'id');
    }

    public function work_group(){
        return $this->belongsTo(workGroup::class, 'id');
    }
}
