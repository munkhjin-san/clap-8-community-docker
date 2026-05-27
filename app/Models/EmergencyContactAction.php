<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmergencyContactAction extends Model
{
    protected $guarded = [];

    public function emergencyContact()
    {
        return $this->belongsTo(EmergencyContact::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->select('id', 'name', 'icon_path', 'icon_bg');
    }
}