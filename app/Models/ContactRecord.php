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

    public function collaborators()
    {
        return $this->belongsToMany(User::class, 'contact_record_user', 'contact_record_id', 'user_id')
            ->select('users.id', 'users.name', 'users.icon_path', 'users.icon_bg')
            ->withTimestamps()
            ->withPivot('role', 'private_memo');
    }
    public function comments()
    {
        return $this->hasMany(ContactRecordComment::class);
    }

    public function types()
    {
        return $this->belongsToMany(ContactType::class, 'contact_record_type', 'contact_record_id', 'contact_type_id')
            ->select('contact_types.id', 'contact_types.title')
            ->orderBy('contact_types.title');
    }

    public function projects()
    {
        return $this->belongsToMany(ProjectRecord::class, 'contact_record_project', 'contact_record_id', 'project_id')
            ->select('project_records.id', 'project_records.name');
    }

    public function relatedContacts()
    {
        return $this->belongsToMany(ContactRecord::class, 'contact_record_related', 'contact_record_id', 'related_contact_record_id')
            ->select('contact_records.id', 'contact_records.name', 'contact_records.company_name', 'contact_records.department', 'contact_records.icon_path');
    }

    // Contact-level uploaded files: 裏面 photos (kind=image) + attachments (kind=file).
    public function files()
    {
        return $this->hasMany(messageFile::class, 'contact_record_id')
            ->whereNull('deleted_at')
            ->orderBy('id');
    }

    public function histories()
    {
        return $this->hasMany(ContactRecordHistory::class, 'contact_record_id')
            ->with('user')
            ->orderByDesc('id');
    }
}
