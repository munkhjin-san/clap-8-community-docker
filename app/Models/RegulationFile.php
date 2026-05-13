<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegulationFile extends Model
{
    use HasFactory, SoftDeletes;

    public const AI_SYNC_STATUS_NOT_SYNCED = 'not_synced';
    public const AI_SYNC_STATUS_SYNCING = 'syncing';
    public const AI_SYNC_STATUS_SYNCED = 'synced';
    public const AI_SYNC_STATUS_ERROR = 'error';

    protected $fillable = [
        'regulation_record_id',
        'vector_file_id',
        'mime_type',
        'extension',
        'name',
        'path',
        'size',
        'chat_supported',
        'ai_sync_status',
        'ai_sync_error',
        'ai_synced_at',
        'ai_sync_hash',
    ];

    protected $casts = [
        'regulation_record_id' => 'integer',
        'size' => 'integer',
        'chat_supported' => 'boolean',
        'ai_synced_at' => 'datetime',
    ];

    /**
     * Get the regulation record that owns the file.
     */
    public function regulationRecord()
    {
        return $this->belongsTo(RegulationRecord::class);
    }

    public function vectorPages()
    {
        return $this->hasMany(RegulationFileVectorPage::class);
    }
}
