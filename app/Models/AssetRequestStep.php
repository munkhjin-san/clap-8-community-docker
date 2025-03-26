<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetRequestStep extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function asset_request()
    {
        return $this->belongsTo(AssetRequest::class, 'asset_request_id');
    }
    public function approver()
    {
        return $this->hasOne(User::class, 'id', 'approved_by')->select('id', 'name', 'icon_path', 'icon_bg');
    }
    public function creator()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->select('id', 'name', 'icon_path', 'icon_bg');
    }
}
