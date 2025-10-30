<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactCommentLastRead extends Model
{
    protected $fillable = [
        'user_id',
        'contact_record_id',
        'last_read_at'
    ];
}
