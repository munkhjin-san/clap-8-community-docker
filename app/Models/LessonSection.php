<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonSection extends Model
{
    use HasFactory;
    
    public function lesson_material(){
        return $this->hasOne(LessonMaterial::class, 'id', 'material_id')->select('id', 'title');
    }
    protected $fillable = [
        'user_id',
        'content',
        'status',
        'material_id',
        'portfolio_id'
    ];

}
