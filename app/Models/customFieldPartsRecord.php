<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class customFieldPartsRecord extends Model
{
    use SoftDeletes;
    use HasFactory;
    public function user(){
        return $this->belongsTo(User::class);
    }

    public function custom_field_type_records(){
        return $this->belongsTo(customFieldTypeRecord::class, 'id');
    }

    protected $casts = [
        'use_flag' => 'int',
        'deleted_flag' => 'int',
    ];
}
