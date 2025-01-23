<?php

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppFileRecord extends Model
{   
    use SoftDeletes;
    use SoftDeletes;

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new SoftDeletingScope);
    }
    protected $fillable = [
        'recycle_flag', 'old_parent_id', 'parent_id', 'updated_by', 'deleted_at', 'name'
    ];
    protected $casts = [
        'user_id' => 'int',       
        'record_id' => 'int',
        'parent_id' => 'int', 
        'old_parent_id' => 'int', 
        'folder' => 'int',
        'recycle_flag' => 'int'       
    ];
    public function user(){
        return $this->hasOne(User::class, 'id', 'user_id')->select('id', 'name', 'icon_path', 'icon_bg')->with('icons');
    }
}
