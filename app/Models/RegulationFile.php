<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegulationFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'regulation_record_id',
        'vector_file_id',
        'mime_type',
        'extension',
        'name',
        'path',
        'size',
        'chat_supported'
    ];

    protected $casts = [
        'regulation_record_id' => 'integer',
        'size' => 'integer',
        'chat_supported' => 'boolean',
    ];

    /**
     * Get the regulation record that owns the file.
     */
    public function regulationRecord()
    {
        return $this->belongsTo(RegulationRecord::class);
    }
}
