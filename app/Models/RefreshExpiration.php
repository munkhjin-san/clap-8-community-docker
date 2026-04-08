<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefreshExpiration extends Model
{
    protected $guarded = [];

    protected $casts = [
        'expired_at' => 'date',
    ];

    public function refreshAccount()
    {
        return $this->belongsTo(RefreshAccount::class);
    }

    public function refreshGrant()
    {
        return $this->belongsTo(RefreshGrant::class);
    }
}
