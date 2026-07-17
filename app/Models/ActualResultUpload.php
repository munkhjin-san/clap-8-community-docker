<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActualResultUpload extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'target_month' => 'date',
        'file_metadata' => 'array',
        'calculated_summary' => 'array',
    ];

    public function report()
    {
        return $this->belongsTo(ActualResultReport::class, 'actual_result_report_id');
    }
}
