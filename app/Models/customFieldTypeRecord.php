<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class customFieldTypeRecord extends Model
{
    use SoftDeletes;
    use HasFactory;
    public function user(){
        return $this->belongsTo(User::class);
    }

    //レコードとリレーション
    public function custom_field_records(){
        return $this->belongsTo(customFieldRecord::class, 'id');
    }

    //パーツとリレーション
    public function custom_field_parts_records(){
        return $this->hasMany(customFieldPartsRecord::class, 'record_id')->orderBy('created_at', 'asc');
    }

    protected $casts = [
        'use_flag' => 'int',
        'deleted_flag' => 'int',
    ];
}
