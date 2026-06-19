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
        return $this->belongsTo(timecardRecord::class, 'record_id', 'id');
    }

    public function auditEvents()
    {
        return $this->hasMany(TimecardAuditEvent::class, 'timecard_cost_record_id');
    }

    public function ocrRuns()
    {
        return $this->hasMany(TimecardCostOcrRun::class, 'timecard_cost_record_id');
    }

    public function receiptFile()
    {
        return $this->belongsTo(TimecardReceiptFile::class, 'receipt_file_id');
    }

    public function project()
    {
        return $this->belongsTo(ProjectRecord::class, 'project_id');
    }

    public function projectSegment()
    {
        return $this->belongsTo(TimecardProjectSegment::class, 'timecard_project_segment_id');
    }

    protected $casts = [
        'receipt_date' => 'string',
        'file_uploaded_at' => 'datetime',
        'file_size_bytes' => 'int',
        'expenses' => 'float',
        'project_id' => 'int',
        'timecard_project_segment_id' => 'int',
        'transport_type' => 'int',
        'scan_dpi' => 'int',
        'scan_color_depth' => 'int',
        'image_width_px' => 'int',
        'image_height_px' => 'int',
    ];

    protected $guarded = [];
}
