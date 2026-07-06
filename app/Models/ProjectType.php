<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCommunity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectType extends Model
{
    use BelongsToCommunity;

    use SoftDeletes;

    protected $guarded = [];

    public function forms()
    {
        return $this->hasMany(CustomForm::class);
    }

    public function projects()
    {
        return $this->hasMany(ProjectRecord::class);
    }

    public function checkitemTemplates()
    {
        return $this->hasMany(ProjectCheckitemTemplate::class);
    }
}
