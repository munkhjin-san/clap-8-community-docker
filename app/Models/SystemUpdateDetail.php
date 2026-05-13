<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SystemUpdateDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function record()
    {
        return $this->belongsTo(SystemUpdateRecord::class, 'system_update_record_id');
    }

    public function fileAttachments()
    {
        return $this->morphMany(FileAttachment::class, 'attachable');
    }

    public function files()
    {
        return $this->belongsToMany(FileRecord::class, 'file_attachments', 'attachable_id', 'file_id')
            ->wherePivot('attachable_type', self::class)
            ->wherePivot('collection', 'attachments')
            ->withPivot(['collection', 'created_at']);
    }
}
