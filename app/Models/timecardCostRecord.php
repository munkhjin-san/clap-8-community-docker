<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class timecardCostRecord extends Model
{
    use HasFactory;
    use SoftDeletes;
    public function file(){
        return $this->hasOne(FileRecord::class, 'id', 'file_id');
    }
    public function user(){
        return $this->belongsTo(User::class);
    }
    public function timecard(){
        return $this->belongsTo(timeCardRecord::class, 'record_id', 'id');
    }
    protected $guarded = [];
}
