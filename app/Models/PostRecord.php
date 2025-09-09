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
        return $this->belongsToMany(FileRecord::class, 'post_use_files', 'record_id', 'file_id')->wherePivot('result_flag', 0)->where('file_records.deleted_flag', 0);
    }
    public function result_files(){
        return $this->belongsToMany(FileRecord::class, 'post_use_files', 'record_id', 'file_id')->wherePivot('result_flag', 1)->where('file_records.deleted_flag', 0);
    }
    public function receipts(){
        return $this->belongsToMany(FileRecord::class, 'post_refresh_use_files', 'record_id', 'file_id');
    }
    public function tags()
    {
        return $this->belongsToMany(TagRecord::class, 'post_use_tags', 'record_id', 'tag_id')->where('tag_records.deleted_flag', 0);
    }
    public function comment_records(){
        return $this->hasMany(CommentRecord::class, 'record_id')->where('app_name', 'post')->with('user');
    }
    public function to_users(){
        return $this->belongsToMany(User::class, 'post_to_users', 'record_id', 'user_id')->withPivot('id')->select(['users.id as id', 'users.name', 'users.icon_path','users.icon_bg']);
    }
    public function comments(){
        return $this->hasMany(CommentRecord::class, 'record_id')->where('app_name', 'post')->where('deleted_flag', 0);
    }
    public function claps(){
        return $this->hasMany(ClapRecord::class, 'record_id')->where('app_name', 'post')->where('deleted_flag', 0)->select('record_id', 'from_user');;
    }
    public function awards(){
        return $this->belongsToMany(User::class, 'post_awards', 'record_id', 'user_id')->withPivot('award_bet')->select(['users.id as id', 'users.name','users.icon_path','users.icon_bg']);
    }
    public function entries() {
        return $this->hasMany(PostEntry::class)->with(['user' => function($query) {
            $query->select('id', 'name', 'icon_path', 'icon_bg');
        }, 'files']);
    }
    public function grants() {
        return $this->hasMany(PostGrant::class);
    }
    protected $guarded = [];

    protected $casts = [
        'chargeable' => 'boolean',
    ];
}
