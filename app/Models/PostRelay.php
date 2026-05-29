<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PostRelay extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_CHALLENGE = 'challenge';
    public const TYPE_NICE = 'nice';
    public const EXCLUDED_USER_IDS = [100, 101, 102, 103, 608, 610, 830];

    public const STATUS_PENDING = 0;
    public const STATUS_DECLINED = 1;
    public const STATUS_CLOSED = 2;
    public const STATUS_COMPLETED = 3;

    protected $guarded = [];

    protected $casts = [
        'assigned_at' => 'datetime',
        'deadline_at' => 'datetime',
        'declined_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function sourcePost()
    {
        return $this->belongsTo(PostRecord::class, 'source_post_id');
    }

    public function acceptedPost()
    {
        return $this->belongsTo(PostRecord::class, 'accepted_post_id');
    }

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id')->select('id', 'name', 'icon_path', 'icon_bg');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id')->select('id', 'name', 'icon_path', 'icon_bg');
    }

    public function declinedByUser()
    {
        return $this->belongsTo(User::class, 'declined_by_user_id')->select('id', 'name', 'icon_path', 'icon_bg');
    }

    public function closedByUser()
    {
        return $this->belongsTo(User::class, 'closed_by_user_id')->select('id', 'name', 'icon_path', 'icon_bg');
    }
}
