<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NiceUseTag extends Model
{
    use HasFactory;

    public function app_record(){
        return $this->belongsTo(NiceRecord::class, 'record_id', 'id');
    }
}
