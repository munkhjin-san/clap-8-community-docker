<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class timecardBreakRecord extends Model
{
    use SoftDeletes;
    protected $guarded = [];
    public function timecard_records(){
        return $this->belongsTo(timecardRecord::class,'id');
    }
}
