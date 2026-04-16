<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class timecardCostRecord extends Model
{
    use HasFactory;
    use SoftDeletes;

    public function file(){
        return $this->hasOne(FileRecord::class, 'id', 'file_id');
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function timecard(){
        return $this->belongsTo(timeCardRecord::class, 'record_id', 'id');
    }

    public function auditEvents()
    {
        return $this->hasMany(TimecardAuditEvent::class, 'timecard_cost_record_id');
    }

    public function ocrRuns()
    {
        return $this->hasMany(TimecardCostOcrRun::class, 'timecard_cost_record_id');
    }

    protected $casts = [
        'receipt_date' => 'string',
        'file_uploaded_at' => 'datetime',
        'file_size_bytes' => 'int',
        'expenses' => 'float',
        'transport_type' => 'int',
    ];

    protected $guarded = [];
}
