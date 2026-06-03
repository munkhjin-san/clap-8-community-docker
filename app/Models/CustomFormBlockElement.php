<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class CustomFormBlockElement extends Model
{
    use HasFactory, SoftDeletes;

    public function answers() {
        return $this->hasMany(SurveyBlockElementAnswer::class);
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
            ->where('file_records.deleted_flag', 0)
            ->withPivot(['collection', 'created_at']);
    }
    protected $guarded = [];

    protected $casts = [
        "has_sub_text" => 'boolean',
        "has_sub_text_required" => 'boolean',
        "has_file_attachment" => 'boolean',
        "is_required" => 'boolean'
    ];

}
