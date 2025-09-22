<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriveNodeAcl extends Model
{
    protected $table = 'drive_node_acls';

    protected $fillable = [
        'node_id','user_id','role','inherited_from','expires_at','granted_by', 'publicly'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
    public function members()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
