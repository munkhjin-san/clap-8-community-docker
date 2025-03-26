<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetRequest extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $guarded = [];
    public function files()
    {
        return $this->belongsToMany(FileRecord::class, 'asset_use_files', 'asset_request_id', 'file_record_id');
    }

    public function recieve_user()
    {
        return $this->hasOne(User::class, 'id', 'to_user')->select('id', 'name', 'icon_path', 'icon_bg');
    }

    public function send_user()
    {
        return $this->hasOne(User::class, 'id', 'from_user')->select('id', 'name', 'icon_path', 'icon_bg');
    }
    public function send_project()
    {
        return $this->hasOne(ProjectRecord::class, 'from_project')->select('id', 'name');
    }
    public function recieve_project()
    {
        return $this->hasOne(ProjectRecord::class, 'to_project')->select('id', 'name');
    }

    public function created_by()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->select('id', 'name', 'icon_path');
    }

    public function asset()
    {
        return $this->belongsTo(AssetRecord::class, 'asset_record_id');
    }

    public function steps()
    {
        return $this->hasMany(AssetRequestStep::class);
    }
}
