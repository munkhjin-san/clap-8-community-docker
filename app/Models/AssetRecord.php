<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function current_user(){
        return $this->belongsTo(User::class, 'user_id')->select('id', 'name', 'icon_path', 'icon_bg');
    }
    public function current_project(){
        return $this->belongsTo(ProjectRecord::class, 'project_id' )->select('id', 'name');
    }
    public function current_office()
    {
        return $this->belongsTo(officeRecord::class, 'office_id');
    }

    public function requests()
    {
        return $this->hasMany(AssetRequest::class)->where(function($q){
            $q->where('status', 1);
        });
    }
    public function all_requests()
    {
        return $this->hasMany(AssetRequest::class);
    }
    public function request_logs()
    {
        return $this->hasMany(AssetRequest::class)->where('status', '>', 1);
    }

    public function confirm_logs()
    {
        return $this->hasMany(AssetConfirmLog::class, 'asset_record_id')->with(['files', 'user']);
    }
}
