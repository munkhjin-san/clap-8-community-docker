<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimecardReceiptFile extends Model
{
    protected $fillable = [
        'timecard_record_id',
        'timecard_cost_record_id',
        'draft_uuid',
        'user_id',
        'uploaded_by_user_id',
        'original_name',
        'mime_type',
        'extension',
        'size_bytes',
        'sha256',
        'canonical_path',
        'preview_path',
        'source_type',
        'status',
        'uploaded_at',
        'finalized_at',
        'scan_dpi',
        'scan_color_depth',
        'scan_color_mode',
        'document_size',
        'image_width_px',
        'image_height_px',
        'is_deleted',
        'deleted_at',
        'deleted_by_user_id',
        'metadata',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'finalized_at' => 'datetime',
        'deleted_at' => 'datetime',
        'metadata' => 'array',
        'is_deleted' => 'boolean',
        'size_bytes' => 'int',
        'scan_dpi' => 'int',
        'scan_color_depth' => 'int',
        'image_width_px' => 'int',
        'image_height_px' => 'int',
    ];

    public function timecard()
    {
        return $this->belongsTo(timecardRecord::class, 'timecard_record_id');
    }

    public function timecardCost()
    {
        return $this->belongsTo(timecardCostRecord::class, 'timecard_cost_record_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
