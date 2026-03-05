<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileAttachment extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'file_id',
        'attachable_type',
        'attachable_id',
        'collection'
    ];

    public function file()
    {
        return $this->belongsTo(FileRecord::class, 'file_id');
    }

    public function attachable()
    {
        return $this->morphTo();
    }
}
