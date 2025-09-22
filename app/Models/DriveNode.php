<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DriveNode extends Model
{
    use SoftDeletes;

    public $incrementing = false;  // UUID/ULID
    protected $keyType = 'string';

    protected $fillable = [
        'id','parent_id','type','name','mime','size','storage_path','owner_id', 'project_id', 'ext'
    ];
    public function project() { 
        return $this->belongsTo(ProjectRecord::class, 'project_id', 'id'); 
    }
    
    public function acls() {
        return $this->hasMany(DriveNodeAcl::class, 'node_id');
    }
    public function owner() {
        return $this->belongsTo(User::class, 'owner_id', 'id');
    }

}
