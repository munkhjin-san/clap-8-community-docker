<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryIssue extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    public function files(){
        return $this->belongsToMany(FileRecord::class, 'project_use_files', 'record_id', 'file_id')->where('file_records.deleted_flag', 0);
    }
}
