<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
class ContactRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function creator(){
        return $this->belongsTo(User::class, 'created_by')->select('id', 'name', 'icon_path', 'icon_bg');
    }

    public function updater(){
        return $this->belongsTo(User::class, 'updated_by')->select('id', 'name', 'icon_path', 'icon_bg');
    }

    public function type(){
        return $this->belongsTo(ContactType::class, 'contact_type_id');
    }
}
