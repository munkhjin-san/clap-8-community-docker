<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActualResultEditHistory extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'before_value' => 'array',
        'after_value' => 'array',
    ];

    public function report()
    {
        return $this->belongsTo(ActualResultReport::class, 'actual_result_report_id');
    }

    public function department()
    {
        return $this->belongsTo(ActualResultDepartment::class, 'actual_result_department_id');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
}
