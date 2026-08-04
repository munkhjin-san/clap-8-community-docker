<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PostRecord extends Model
{
    use HasFactory;
    use SoftDeletes;
    public function user(){
        return $this->belongsTo(User::class)->select('id', 'name', 'icon_path', 'icon_bg');
    }
    public function files(){
        return $this->belongsToMany(FileRecord::class, 'post_use_files', 'record_id', 'file_id')->wherePivot('result_flag', 0)->wherePivot('progress', 0)->where('file_records.deleted_flag', 0);
    }
    public function result_files(){
        return $this->belongsToMany(FileRecord::class, 'post_use_files', 'record_id', 'file_id')->wherePivot('result_flag', 1)->wherePivot('progress', 0)->where('file_records.deleted_flag', 0);
    }
    public function receipts(){
        return $this->belongsToMany(FileRecord::class, 'post_refresh_use_files', 'record_id', 'file_id');
    }
    public function tags()
    {
        return $this->belongsToMany(TagRecord::class, 'post_use_tags', 'record_id', 'tag_id')->where('tag_records.deleted_flag', 0);
    }
    public function sport_tags()
    {
        return $this->belongsToMany(TagRecord::class, 'post_use_sport_tags', 'record_id', 'tag_id')->where('tag_records.deleted_flag', 0);
    }
    public function comment_records(){
        return $this->hasMany(CommentRecord::class, 'record_id')->where('app_name', 'post')->with('user');
    }
    public function to_users(){
        return $this->belongsToMany(User::class, 'post_to_users', 'record_id', 'user_id')->withPivot('id')->select(['users.id as id', 'users.name', 'users.icon_path','users.icon_bg', 'users.email']);
    }
    public function comments(){
        return $this->hasMany(CommentRecord::class, 'record_id')->where('app_name', 'post')->where('deleted_flag', 0);
    }
    public function progressReports()
    {
        return $this->hasMany(CommentRecord::class, 'record_id')
            ->where('app_name', 'post')
            ->where('deleted_flag', 0)
            ->where('comment_type', 'progress_report');
    }
    public function claps(){
        return $this->hasMany(ClapRecord::class, 'record_id')->where('app_name', 'post')->where('deleted_flag', 0)->select('record_id', 'from_user');;
    }
    public function awards(){
        return $this->belongsToMany(User::class, 'post_awards', 'record_id', 'user_id')->withPivot('award_bet')->select(['users.id as id', 'users.name','users.icon_path','users.icon_bg', 'users.email']);
    }
    public function rakuawardScores(){
        return $this->hasMany(PostRakuawardScore::class, 'post_id');
    }
    public function emotedUsers()
    {
        return $this->morphToMany(User::class, 'stampable', 'stamps', 'stampable_id', 'user_id')
                    ->withPivot(['emote_name'])
                    ->withTimestamps()
                    ->select('users.id', 'users.name', 'users.icon_path', 'users.icon_bg', 'users.deleted_at');
    }
    public function stamps()
    {
        return $this->morphMany(Stamp::class, 'stampable');
    }
    public function entries() {
        return $this->hasMany(PostEntry::class)->with(['user' => function($query) {
            $query->select('id', 'name', 'icon_path', 'icon_bg');
        }, 'files', 'photos']);
    }
    public function grants() {
        return $this->hasMany(PostGrant::class);
    }
    public function refreshUsage()
    {
        return $this->hasOne(RefreshUsage::class, 'post_record_id', 'id');
    }
    public function postRelays()
    {
        return $this->hasMany(PostRelay::class, 'source_post_id');
    }
    public function acceptedPostRelay()
    {
        return $this->hasOne(PostRelay::class, 'accepted_post_id');
    }
    protected $guarded = [];

    protected $casts = [
        'chargeable' => 'boolean',
    ];

    /**
     * Rakuaward months grouped by 'Y-m' => [total, ranked] nomination counts.
     * A director announcement stores rakuaward_rank on every nomination of that month,
     * so a month is "announced" only when every nomination in it is ranked. A month that
     * received new nominations after being announced becomes pending again.
     */
    private static function rakuawardMonthCounts(): \Illuminate\Support\Collection
    {
        return static::where('app_type', 7)
            ->get(['created_at', 'rakuaward_rank'])
            ->groupBy(fn ($post) => \Carbon\Carbon::parse($post->created_at)->format('Y-m'))
            ->map(fn ($posts) => [
                'total' => $posts->count(),
                'ranked' => $posts->whereNotNull('rakuaward_rank')->count(),
            ]);
    }

    public static function latestAnnouncedRakuawardMonth(): ?\Carbon\Carbon
    {
        $key = static::rakuawardMonthCounts()
            ->filter(fn ($counts) => $counts['total'] > 0 && $counts['ranked'] === $counts['total'])
            ->keys()
            ->sortDesc()
            ->first();

        return $key ? \Carbon\Carbon::createFromFormat('Y-m', $key)->startOfMonth() : null;
    }

    /**
     * Oldest month that still has nominations waiting for a director announcement.
     */
    public static function earliestPendingRakuawardMonth(): ?\Carbon\Carbon
    {
        $key = static::rakuawardMonthCounts()
            ->filter(fn ($counts) => $counts['ranked'] < $counts['total'])
            ->keys()
            ->sort()
            ->first();

        return $key ? \Carbon\Carbon::createFromFormat('Y-m', $key)->startOfMonth() : null;
    }
}
