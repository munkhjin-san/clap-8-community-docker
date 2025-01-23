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
        return $this->belongsToMany(FileRecord::class, 'project_use_files', 'salary_issue_id', 'file_id')->where('file_records.deleted_flag', 0);
    }

    public function mentor() {
        return $this->hasOne(User::class, 'id', 'mentor_id')->select('id', 'name', 'icon_path', 'icon_bg');
    }
}
