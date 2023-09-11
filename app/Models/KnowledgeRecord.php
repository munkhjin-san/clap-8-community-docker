<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KnowledgeRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'description',
    ];
    public function user(){
        return $this->belongsTo(User::class)->select('id', 'name', 'icon_id', 'a_path', 'a_version');
    }
    public function files(){
        return $this->belongsToMany(FileRecord::class, 'knowledge_use_files', 'record_id', 'file_id')->where('file_records.deleted_flag', 0);
    }
    public function tags()
    {
        return $this->belongsToMany(TagRecord::class, 'knowledge_use_tags', 'record_id', 'tag_id')->where('tag_records.deleted_flag', 0);
    }
    public function comment_records(){
        return $this->hasMany(CommentRecord::class, 'record_id')->where('app_name', 'knowledge')->with('user');
    }
    protected $casts = [
        'user_id' => 'int',       
        'deleted_flag' => 'int',
        'app_type' => 'int'     
        
    ];
}
