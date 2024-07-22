<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskRepeat extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function tasks(){
        return $this->hasMany(taskRecord::class, 'repeat_id', 'record_id')                    
        ->with('executors')
        ->with('files')
        ->with('supervisors')
        ->with('repeat');
    }

    protected $casts = [
        'day_of_week' => 'array'
    ];
}
