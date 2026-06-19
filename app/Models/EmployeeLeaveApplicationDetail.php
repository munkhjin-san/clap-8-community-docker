<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeLeaveApplicationDetail extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'expected_birth_date' => 'date',
        'maternity_leave_start' => 'date',
        'maternity_leave_end' => 'date',
        'childcare_leave_start' => 'date',
        'childcare_leave_end' => 'date',
    ];

    public function application()
    {
        return $this->belongsTo(EmployeeChangeApplication::class, 'employee_change_application_id', 'id');
    }
}
