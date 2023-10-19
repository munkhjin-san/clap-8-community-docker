<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppFolderRecord extends Model
{   
    use SoftDeletes;
      protected $casts = [
        'user_id' => 'int',       
        'record_id' => 'int',
        'parent_id' => 'int',     
        'recycle_flag' => 'int',
        'folder' => 'int',
        'color' => 'int',
        'recycle_flag' => 'int'              
    ];
    protected $fillable = [
        'recycle_flag','old_parent_id', 'parent_id', 'updated_by', 'deleted_at', 'path'
    ];
    public function user(){
        return $this->hasOne(User::class, 'id', 'user_id')->select('id', 'name', 'icon_id')->with('icons');
    }
    public function files(){
        return $this->hasMany(AppFileRecord::class, 'parent_id', 'id');
    }
    public function parent()
    {
        return $this->belongsTo(self::class, 'id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id', 'id')->with('files');
    }

    public function sub_directories()
    {
        return $this->hasMany(self::class, 'parent_id', 'id')->with('sub_directories')->with('files');
    }
}
