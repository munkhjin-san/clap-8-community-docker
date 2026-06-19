<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeCommuteChangeDetail extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'effective_date' => 'date',
    ];

    public function application()
    {
        return $this->belongsTo(EmployeeChangeApplication::class, 'employee_change_application_id', 'id');
    }
}
