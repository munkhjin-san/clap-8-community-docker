<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FileRecord extends Model
{
    use HasFactory, SoftDeletes;

    public function assetConfirmLogs()
    {
        return $this->belongsToMany(AssetConfirmLog::class, 'asset_confirm_log_use_files', 'file_id', 'asset_confirm_log_id');
    }

    public function knowledgeRecords()
    {
        return $this->belongsToMany(KnowledgeRecord::class, 'knowledge_use_files', 'file_id', 'record_id');
    }
    public function NiceRecords()
    {
        return $this->belongsToMany(KnowledgeRecord::class, 'knowledge_use_files', 'file_id', 'record_id');
    }
    protected $fillable = [
        'deleted_flag'
    ];
    
    public function attachments()
    {
        return $this->hasMany(FileAttachment::class, 'file_id');
    }
    
}
