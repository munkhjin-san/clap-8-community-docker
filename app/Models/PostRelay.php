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

    // A nice relay is considered complete once it reaches this many participants.
    public const NICE_RELAY_LIMIT = 9;

    // Prize tiers a completed-relay participant can win in the GlowdNine dice game.
    // Mirrors the tiers in resources/js/components/Global/RollDice.vue.
    // Per-participant results are stored in the post_relay_prizes table.
    public const GLOWD_NINE_PRIZES = [0, 100, 200, 400, 800];

    public function relayPrizes()
    {
        return $this->hasMany(PostRelayPrize::class, 'root_post_id', 'source_post_id');
    }

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
