<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class officeRecord extends Model
{
    use HasFactory, SoftDeletes;

    public function employees(){
        return $this->hasMany(User::class, 'office_id', 'id')->where('retire', '=', 0);
    }
}
