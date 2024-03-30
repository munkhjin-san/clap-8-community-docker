<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShiftOvertimeRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'content', 'approved_by', 'record_id', 'status', 'minutes', 'created_by', 'overtime_day', 'user_id', 'descendant_of'
    ];
}
