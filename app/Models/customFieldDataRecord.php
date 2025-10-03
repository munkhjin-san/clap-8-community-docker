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
        return $this->belongsTo(User::class)->select('id', 'name', 'icon_path', 'icon_bg');
    }

    public function custom_field_records(){
        return $this->hasMany(customFieldRecord::class, 'record_id');
    }

    public function time_card_records(){
        return $this->belongsTo(timecardRecord::class, 'table_record_id');
    }


    protected $casts = [
        'field_id' => 'int',
        'type_id' => 'int',
        'table_record_id' => 'int',
        'user_id' => 'int',
        'value_int' => 'int',
        'deleted_flag' => 'int',
        'date' => 'date:Y-m-d',
    ];

    protected $fillable = ['value_int', 'user_id', 'date', 'field_id', 'type_id', 'app_name'];
}
