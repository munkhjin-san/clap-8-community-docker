<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class customFieldRecord extends Model
{
    use SoftDeletes;
    use HasFactory;
    public function user(){
        return $this->belongsTo(User::class);
    }

    public function custom_field_type_records(){
        return $this->hasMany(customFieldTypeRecord::class, 'record_id');
    }

    public function custom_field_data_records(){
        return $this->belongsTo(customFieldDataRecord::class, 'id');
    }


    protected $casts = [
        'user_id' => 'int',
        'use_flag' => 'int',
        'deleted_flag' => 'int',
    ];
}
