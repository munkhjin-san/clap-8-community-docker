<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChallengeRecord extends Model
{
    use HasFactory;
    use SoftDeletes;
    public function user(){
        return $this->belongsTo(User::class)->select('id', 'name', 'icon_id', 'icon_id');
    }
    public function files(){
        return $this->belongsToMany(FileRecord::class, 'challenge_use_files', 'record_id', 'file_id')->wherePivot('result_flag', 0)->where('file_records.deleted_flag', 0);
    }
    public function result_files(){
        return $this->belongsToMany(FileRecord::class, 'challenge_use_files', 'record_id', 'file_id')->wherePivot('result_flag', 1)->where('file_records.deleted_flag', 0);
    }
    public function tags()
    {
        return $this->belongsToMany(TagRecord::class, 'challenge_use_tags', 'record_id', 'tag_id')->where('tag_records.deleted_flag', 0);
    }
    public function comments(){
        return $this->hasMany(CommentRecord::class, 'record_id')->where('app_name', 'challenge')->where('deleted_flag', 0);
    }
    public function to_users(){
        return $this->belongsToMany(User::class, 'challenge_to_users', 'record_id', 'user_id')->withPivot('id')->select(['users.id as id', 'users.name','users.icon_id']);
    }
    public function challenge_awards(){
        return $this->belongsToMany(User::class, 'challenge_awards', 'record_id', 'user_id')->withPivot('award_bet')->select(['users.id as id', 'users.name','users.icon_id']);
    }
    public function claps(){
        return $this->hasMany(ClapRecord::class, 'record_id')->where('app_id', 4)->where('deleted_flag', 0)->select('record_id', 'from_user')->with('user');
    }
    protected $fillable = [
        'deleted_flag', 'status_flag'
    ];
    
    

}
