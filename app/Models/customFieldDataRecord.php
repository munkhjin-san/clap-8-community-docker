<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class customFieldDataRecord extends Model
{
    use SoftDeletes;

    use HasFactory;
    public function user(){
        return $this->belongsTo(User::class)->select('id', 'name', 'icon_id');
    }

    public function custom_field_records(){
        return $this->hasMany(customFieldRecord::class, 'record_id');
    }

    public function time_card_records(){
        return $this->belongsTo(timecardRecord::class, 'id');
    }


    protected $casts = [
        'field_id' => 'int',
        'type_id' => 'int',
        'table_record_id' => 'int',
        'user_id' => 'int',
        'value_int' => 'int',
        'deleted_flag' => 'int',
    ];

    protected $fillable = ['value_int'];
}
