<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActualResultReport extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'target_month' => 'date',
        'file_metadata' => 'array',
        'summary' => 'array',
        'account_totals' => 'array',
        'result_payload' => 'array',
    ];

    public function departments()
    {
        return $this->hasMany(ActualResultDepartment::class);
    }

    public function uploads()
    {
        return $this->hasMany(ActualResultUpload::class);
    }

    public function currentUpload()
    {
        return $this->belongsTo(ActualResultUpload::class, 'current_upload_id');
    }
}
