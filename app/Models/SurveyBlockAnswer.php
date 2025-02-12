<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class SurveyBlockAnswer extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = [];
    public function element_answers(){
        return $this->hasMany(SurveyBlockElementAnswer::class);
    }
    public function user(){
        return $this->belongsTo(User::class, 'user_id')->select('id', 'name', 'email', 'icon_path', 'icon_bg');
    }
    public function files(){
        return $this->belongsToMany(FileRecord::class, 'survey_use_files');
    }
}
