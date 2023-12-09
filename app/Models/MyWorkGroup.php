<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MyWorkGroup extends Model
{
    use HasFactory;

    public function work_group(){
        return $this->belongsTo(workGroup::class, 'id', 'work_group_id');
    }

    protected $fillable = [
        'work_group_id', 'user_id'
    ];
}
