<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarFavouriteUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id', 'member_id', 'score', 'shared_count', 'last_together_at'
    ];

    protected $casts = [
        'score' => 'float',
        'shared_count' => 'integer',
        'last_together_at' => 'datetime',
    ];

    public function member(){
        return $this->belongsTo(User::class, 'member_id');
    }
}
