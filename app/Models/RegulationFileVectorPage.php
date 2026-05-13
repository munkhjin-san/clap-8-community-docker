<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegulationFileVectorPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'regulation_file_id',
        'page_number',
        'markdown_path',
        'markdown_copy_path',
        'openai_file_id',
        'vector_store_file_id',
    ];

    protected $casts = [
        'regulation_file_id' => 'integer',
        'page_number' => 'integer',
    ];

    public function regulationFile()
    {
        return $this->belongsTo(RegulationFile::class);
    }
}
