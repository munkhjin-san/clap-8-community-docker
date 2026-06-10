<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncidentAdvice extends Model
{
    use HasFactory;

    protected $table = 'incident_advice';

    protected $guarded = [];

    public function incident()
    {
        return $this->belongsTo(Incident::class, 'incident_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id')
            ->select('id', 'name', 'icon_path', 'icon_bg');
    }
}
