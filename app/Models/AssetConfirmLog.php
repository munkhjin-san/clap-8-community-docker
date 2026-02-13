<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetConfirmLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function asset()
    {
        return $this->belongsTo(AssetRecord::class, 'asset_record_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function files()
    {
        return $this->belongsToMany(FileRecord::class, 'asset_confirm_log_use_files', 'asset_confirm_log_id', 'file_id');
    }
}
