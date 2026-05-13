<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncidentPunishment extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function incidents()
    {
        return $this->hasMany(Incident::class, 'incident_punishment_id', 'id');
    }
}
