<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class timecardVehicle extends Model
{
    use HasFactory;
    use SoftDeletes;
    public function before_user(){
        return $this->belongsTo(User::class, 'confirm_before_user')->select('id', 'name', 'icon_id');
    }
    public function after_user(){
        return $this->belongsTo(User::class, 'confirm_after_user')->select('id', 'name', 'icon_id');
    }
}
