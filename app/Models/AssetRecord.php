<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];
    public function users()
    {
        return $this->belongsToMany(User::class, 'asset_users')->select(['users.id as id', 'users.name','users.icon_path', 'users.icon_bg']);
    }
    public function projects()
    {
        return $this->belongsToMany(ProjectRecord::class, 'asset_projects');
    }
}
