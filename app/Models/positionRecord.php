<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class positionRecord extends Model{
    public function employees(){
        return $this->hasMany(User::class, 'position_id', 'id')->where('retire', '=', 0);
    }
   
    protected $casts = [   
        'deleted_flag' => 'int',       
        'sort_flag' => 'int', 
    ];
}