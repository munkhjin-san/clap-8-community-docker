<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmail;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;
use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable
{
    // use Notifiable;
    use HasApiTokens, Notifiable, SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'email_or_phone', 
        'phone', 'password','icon_id', 'login', 
        'phone_isVerified', 'phone_prefix', 'q_token', 
        'is_public', 'color', 'language', 'work_email', 'footer_view', 'ical_key'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'position_id'    => 'int', 
        'office_id' => 'int',
        'icon_id' => 'int',   
        'hide_flag' => 'int',
        'partner_flag' => 'int',
        'award_charge' => 'int'
    ];
    public function friends()
    {
        return $this->belongsToMany(User::class, 'friends', 'user_id', 'friend_id')->select('users.id', 'users.name', 'users.icon_id', 'users.q_token');
    }
    //board message relation
    public function message_records(){
        return $this->hasMany(messageRecord::class, 'user_id');
    }

    public function icons(){
        return $this->hasOne(Icons::class, 'id', 'icon_id');
    }

    public function user_last_record(){
        return $this->hasOne(userLastRecord::class, 'user_id');
    }
    public function messageRecords()
    {
        return $this->belongsToMany(MessageRecord::class, 'message_check_users')
                    ->using(messageCheckUser::class)
                    ->withPivot(['checked']);
    }
    public function reactedMessages(): BelongsToMany
    {
        return $this->belongsToMany(MessageRecord::class, 'message_reacted_users');
    }

  
    public function app_remember_record(){
        return $this->hasOne(appRememberRecord::class, 'user_id', 'id');
    }

    public function user_detail(){
        return $this->hasOne(userDetail::class, 'user_id', 'id');
    }

    public function rules(){
        return $this->hasOne(appRule::class, 'id', 'rule_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }
    public function boards()
    {
        return $this->belongsToMany(Board::class)
                    ->using(BoardUser::class)
                    ->withPivot([
                        'admin_flag',
                        'last_message',
                        'pin_flag',
                    ]);
    }
    public function reactions()
    {
        return $this->belongsToMany(messageRecord::class, 'message_reacted_users');
    }
    public function checks()
    {
        return $this->belongsToMany(messageRecord::class, 'message_check_users');
    }
    public function signs()
    {
        return $this->belongsToMany(messageFile::class, 'message_sign_users');
    }

    /**
     * Get the users who have blocked the current user.
     */
    public function usersWhoBlockedMe(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'block_list', 'blocked_user_id', 'user_id')
            ->withTimestamps();
    }
    public function time_card_records(){
        return $this->hasMany(timecardRecord::class, 'user_id');
    }
    public function shift_records(){
        return $this->hasMany(shiftRecord::class, 'user_id');
    }
    public function attendance_records(){
        return $this->hasMany(attendanceRecord::class, 'user_id');
    }
    public function custom_field_data_records(){
        return $this->hasMany(customFieldDataRecord::class, 'user_id');
    }
    public function my_group(){
        return $this->hasOne(MyGroup::class, 'id', 'user_id');
    }
    public function positions(){
        return $this->hasOne(positionRecord::class, 'id', 'position_id');
    }
    public function offices(){
        return $this->hasOne(officeRecord::class, 'id', 'office_id');
    }
    public function work_group_user(){
        return $this->hasMany(workGroupUser::class, 'user_id', 'id')->with('work_group');
    }
    public function user_album(){
        return $this->hasMany(UserAlbum::class, 'user_id');
    }
    public function weathers(){
        return $this->hasOne(customFieldDataRecord::class, 'user_id')->select('user_id', 'value_int');
    }
    public function today_weather(){
        $today = Carbon::now()->format('Y-m-d');
        return $this->hasOne(customFieldDataRecord::class, 'user_id')->select('user_id', 'value_int')->where('type_id', 43)->where('date', $today);
    }
    public function days_weathers(){
        return $this->hasMany(customFieldDataRecord::class, 'user_id')->select('user_id', 'value_int', 'date', 'id');
    }
    public function files(){
        return $this->belongsToMany(UserAlbum::class, 'user_albums', 'user_id', 'id');
    }
    public function portfolio(){
        return $this->hasMany(LessonPortfolio::class, 'user_id')->with('lesson_theme');
    }
    public function workTemps(){
        return $this->hasOne(workTemp::class, 'user_code', 'user_code');
    }
    // public function sendEmailVerificationNotification()
    // {
    //     if($this->phone){
    //         return redirect('/');
    //     }else if($this->email){
    //         Mail::to($this->email)->send(new VerifyEmail($this));
    //     }
    // }
}
