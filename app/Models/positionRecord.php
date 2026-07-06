<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCommunity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class positionRecord extends Model{
    use BelongsToCommunity;

    public function employees(){
        return $this->hasMany(User::class, 'position_id', 'id')->where('retire', '=', 0);
    }
   
    protected $casts = [   
        'deleted_flag' => 'int',       
        'sort_flag' => 'int', 
    ];
}