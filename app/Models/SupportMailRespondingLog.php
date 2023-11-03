<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportMailRespondingLog extends Model
{
    use HasFactory;
    public function user(){
        return $this->hasOne(User::class, 'id', 'user_id')->select('id', 'name', 'icon_id');
    }
    protected $fillable = [
        'text', 'record_id', 'user_id'
    ];
}
