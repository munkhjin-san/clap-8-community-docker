<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class timecardVehicle extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $guarded = [];
    public function before_user(){
        return $this->belongsTo(User::class, 'confirm_before_user')->select('id', 'name', 'icon_path', 'icon_bg');
    }
    public function after_user(){
        return $this->belongsTo(User::class, 'confirm_after_user')->select('id', 'name', 'icon_path', 'icon_bg');
    }
    public function project(){
        return $this->belongsTo(ProjectRecord::class, 'project_id');
    }
    public function project_segment(){
        return $this->belongsTo(TimecardProjectSegment::class, 'timecard_project_segment_id');
    }
}
