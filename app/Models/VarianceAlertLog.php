<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VarianceAlertLog extends Model
{
    protected $fillable = ['project_record_id','period','hash','sent_at'];
}
