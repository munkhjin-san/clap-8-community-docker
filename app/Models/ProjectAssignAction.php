<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectAssignAction extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'additional_data' => 'array',
    ];
    
        /**
        * Get the assign record that owns this action.
        */

    /**
     * Get the parent assign record.
     */
    public function projectAssignRecord()
    {
        return $this->belongsTo(ProjectAssignRecord::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->select('id', 'name', 'icon_path', 'icon_bg');
    }

    public function actualUser()
    {
        return $this->belongsTo(User::class, 'actual_user_id')->select('id', 'name', 'icon_path', 'icon_bg');
    }
}
