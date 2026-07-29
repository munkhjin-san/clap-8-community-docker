<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarExtraUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'member_id'
    ];

    public function member(){
        return $this->belongsTo(User::class, 'member_id');
    }
}
