<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriveDownloadLog extends Model
{
    protected $fillable = [
        'node_id','user_id','action','requested_name','file_count',
        'bytes_expected','bytes_sent','status','success',
        'client_ip','user_agent','referer','manifest',
        'started_at','ended_at','duration_ms',
    ];

    protected $casts = [
        'success' => 'bool',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'manifest' => 'array',
    ];
}
