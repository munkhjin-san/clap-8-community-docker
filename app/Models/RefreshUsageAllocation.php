<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefreshUsageAllocation extends Model
{
    protected $guarded = [];

    public function usage()
    {
        return $this->belongsTo(RefreshUsage::class, 'refresh_usage_id');
    }

    public function grant()
    {
        return $this->belongsTo(RefreshGrant::class, 'refresh_grant_id');
    }
}
