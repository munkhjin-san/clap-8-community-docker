<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefreshAnnualReview extends Model
{
    protected $guarded = [];

    protected $casts = [
        'grant_date' => 'date',
        'reviewed_at' => 'datetime',
        'leave_review_confirmed_at' => 'datetime',
    ];

    public function refreshAccount()
    {
        return $this->belongsTo(RefreshAccount::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
