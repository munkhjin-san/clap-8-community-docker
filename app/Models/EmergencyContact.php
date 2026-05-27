<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmergencyContact extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETE = 'complete';

    protected $guarded = [];

    public function actions(): HasMany
    {
        return $this->hasMany(EmergencyContactAction::class)->orderBy('created_at');
    }
}
