<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCommunity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncidentCategory extends Model
{
    use BelongsToCommunity;

    use HasFactory;

    protected $guarded = [];

    public function incidents()
    {
        return $this->hasMany(Incident::class, 'incident_category_id', 'id');
    }
}
