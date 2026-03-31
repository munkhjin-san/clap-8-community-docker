<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class SurveyAnswer extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = [];
    protected $appends = ['respondent_label'];
    public function block_answers(){
        return $this->hasMany(SurveyBlockAnswer::class);
    }
    public function user(){
        return $this->belongsTo(User::class, 'user_id')->select('id', 'name', 'email', 'icon_path', 'icon_bg');
    }
    public function custom_form(){
        return $this->belongsTo(CustomForm::class, 'custom_form_id');
    }
    public function getRespondentLabelAttribute(): string
    {
        if ($this->relationLoaded('user') && $this->user) {
            return $this->user->name;
        }

        return '匿名回答 #' . $this->id;
    }
}
