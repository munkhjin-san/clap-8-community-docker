<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FileRecord extends Model
{
    use HasFactory, SoftDeletes;
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
    
    
}
