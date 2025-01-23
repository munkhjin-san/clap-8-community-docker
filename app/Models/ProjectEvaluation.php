<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectEvaluation extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function mentor() {
        return $this->hasOne(User::class, 'id', 'mentor_id')->select('id', 'name', 'icon_path', 'icon_bg');
    }
}
