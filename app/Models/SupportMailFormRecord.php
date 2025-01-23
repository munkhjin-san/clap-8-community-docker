<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportMailFormRecord extends Model
{
    use HasFactory;
    public function user(){
        return $this->hasOne(User::class, 'id', 'user_id')->select('id', 'name', 'icon_path', 'icon_bg');
    }
    public function support_mail_responding_logs(){
        return $this->hasMany(SupportMailRespondingLog::class, 'record_id');
    }
    protected $fillable = [
        'consultation_content', 'contact_address', 'kind_value', 'user_id', 'status_flag'
    ];
}
