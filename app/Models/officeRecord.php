<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class officeRecord extends Model
{
    use HasFactory;

    public function employees(){
        return $this->hasMany(User::class, 'office_id', 'id')->where('retire', '=', 0);
    }
}
