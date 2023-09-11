<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class memoRecord extends Model
{   
    use SoftDeletes;
    public function user(){
        return $this->hasOne(User::class, 'id', 'user_id')->withTrashed()->select('id', 'name', 'a_version', 'a_path');
    }
}
