<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonAnswer extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['id', 'material_id', 'user_id', 'answer', 'ai_review', 'status'];
}
