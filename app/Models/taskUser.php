<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class taskUser extends Model
{   
    use SoftDeletes;
    use HasFactory;

    public function user(){
        return $this->hasOne(User::class, 'id', 'user_id')->select('id', 'name', 'a_path', 'a_version');
    }
}
