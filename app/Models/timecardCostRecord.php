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
        return $this->hasOne(FileRecord::class, 'id', 'file_id')->select('id', 'user_id', 'path', 'extension', 'mime_type');
    }
    public function user(){
        return $this->belongsTo(User::class)->select('id', 'name');
    }
    protected $fillable = [
        'user_id', 'record_id'
    ];
}
