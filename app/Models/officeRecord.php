<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCommunity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class officeRecord extends Model
{
    use HasFactory, SoftDeletes, BelongsToCommunity;

    public function employees(){
        return $this->hasMany(User::class, 'office_id', 'id')->where('retire', '=', 0)->select('id', 'name', 'icon_path', 'icon_bg', 'office_id', 'position_id');
    }

    protected $guarded = [];

    public function fileAttachments()
    {
        return $this->morphMany(FileAttachment::class, 'attachable');
    }

    public function files()
    {
        return $this->belongsToMany(FileRecord::class, 'file_attachments', 'attachable_id', 'file_id')
            ->wherePivot('attachable_type', self::class)
            ->withPivot(['collection', 'created_at']);
    }
}
