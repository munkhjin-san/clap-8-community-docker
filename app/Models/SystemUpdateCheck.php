<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemUpdateCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'system_update_record_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function systemUpdateRecord()
    {
        return $this->belongsTo(SystemUpdateRecord::class);
    }
}
