<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;
use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable
{
    // use Notifiable;
    use HasApiTokens, Notifiable, SoftDeletes;

    public const ADMIN_USER_IDS = [608, 610];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'email_or_phone', 
        'phone', 'password','icon_path', 'login', 
        'phone_isVerified', 'phone_prefix', 'q_token', 
        'is_public', 'color', 'language', 'work_email', 'footer_view', 'ical_key',
        'award_charge', 'general_position', 'office_id'
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
        'icon_path' => 'string',   
        'hide_flag' => 'int',
        'partner_flag' => 'int',
        'award_charge' => 'int'
    ];
   
    public function friends()
    {
        return $this->belongsToMany(User::class, 'friends', 'user_id', 'friend_id')->select('users.id', 'users.name', 'users.icon_path','users.icon_bg', 'users.q_token');
    }
    //board message relation
    public function message_records(){
        return $this->hasMany(messageRecord::class, 'user_id');
    }

    public function icons(){
        return $this->hasOne(Icons::class, 'id', 'icon_id');
    }

    public function user_last_record(){
        return $this->hasOne(UserLastRecord::class, 'user_id');
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

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }
    public function boards()
    {
        return $this->belongsToMany(boardRecord::class)
                    ->using(boardToUser::class)
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
    public function today_comment(){
        $today = Carbon::now()->format('Y-m-d');
        return $this->hasOne(customFieldDataRecord::class, 'user_id')->select('user_id', 'value_text', 'id')->where('type_id', 43)->where('date', $today)->with(['emotedUsers']);
    }
    public function days_weathers(){
        return $this->hasMany(customFieldDataRecord::class, 'user_id')->select('user_id', 'value_int', 'date', 'id');
    }
    public function files(){
        return $this->belongsToMany(UserAlbum::class, 'user_albums', 'user_id', 'id');
    }
    public function portfolio(){
        return $this->hasMany(LessonPortfolio::class, 'user_id')->with('lesson_theme')->with('claps')->orderBy('lesson_theme_id');
    }
    public function workTemps(){
        return $this->hasOne(workTemp::class, 'user_code', 'user_code');
    }
    public function paidLeaveAccount(){
        return $this->hasOne(PaidLeaveAccount::class);
    }
    public function work_groups(){
        return $this->belongsToMany(ProjectRecord::class, 'project_members', 'user_id', 'project_id')->withPivot(['authority']);
    }
    public function linked(){
        return $this->belongsToMany(User::class, 'user_linked_accounts', 'main_id', 'link_id')->withPivot(['active']);
    }
    public function knowledge(){
        return $this->hasMany(KnowledgeRecord::class, 'user_id');
    }
    public function nice(){
        return $this->hasMany(NiceRecord::class, 'user_id');
    }
    public function nice_recieved(){
        return $this->belongsToMany(NiceRecord::class, 'nice_to_users', 'user_id', 'record_id');
    }
    public function challenge(){
        return $this->belongsToMany(ChallengeRecord::class, 'challenge_to_users', 'user_id', 'record_id');
    }
    public function shift_overtime(){
        return $this->hasMany(ShiftOvertimeRequest::class, 'user_id');
    }
    public function comment(){
        return $this->hasMany(CommentRecord::class, 'user_id');
    }
    public function post(){
        return $this->hasMany(PostRecord::class, 'user_id');
    }
    public function post_recieved(){
        return $this->belongsToMany(PostRecord::class, 'post_to_users', 'user_id', 'record_id');
    }
    public function evaluation(){
        return $this->hasOne(EvaluationRecord::class);
    }
    public function evaluations(){
        return $this->hasMany(EvaluationRecord::class);
    } 
    public function outcome_goals(){
        return $this->hasMany(ProjectGoal::class);
    }
    public function salary_issues(){
        return $this->hasMany(SalaryIssue::class);
    }
    public function task_users() {
        return $this->hasMany(taskUser::class);
    }
    public function custom_form_users(){
        return $this->hasMany(CustomFormUser::class);
    }
    public function relay_prizes(){
        return $this->hasMany(PostRelayPrize::class, 'user_id');
    }
    public function related_projects()
    {
        return $this->belongsToMany(ProjectRecord::class, 'project_members', 'user_id', 'project_id');
    }
    public function assets()
    {
        return $this->hasMany(AssetRecord::class);
    }

    public function post_entries(){
        return $this->hasMany(PostEntry::class)->whereHas('post');
    }
    public function isProjectManager($projectId): bool
    {
        return $this->work_groups()->where('project_id', $projectId)->wherePivot('authority', 1)->exists();
    }
    public function isAdmin(): bool
    {
        $adminIds = config('profitplan.admin_user_ids', self::ADMIN_USER_IDS);

        return in_array((int) $this->id, array_map('intval', (array) $adminIds), true);
    }

    // Serialized as `is_admin` so the frontend reads admin status from the
    // server instead of hardcoding ids (see resources/js/store/auth.ts).
    protected $appends = ['is_admin'];

    public function getIsAdminAttribute(): bool
    {
        return $this->isAdmin();
    }
    public function oauthCredentials()
    {
        return $this->hasMany(OAuthCredential::class);
    }

    public function googleCalendarCredential()
    {
        return $this->hasOne(OAuthCredential::class)
            ->where('provider', 'google')
            ->where('service', 'calendar');
    }
    public function project_settings()
    {
        return $this->hasMany(UserProjectSetting::class, 'user_id', 'id');
    }
    public function pushSubscriptions()
    {
        return $this->hasMany(PushSubscription::class, 'user_id', 'id');
    }
    public function projectMemberRoles()
    {
        return $this->hasMany(ProjectMemberRole::class, 'user_id', 'id');
    }
    public function refreshAccount()
    {
        return $this->hasOne(RefreshAccount::class, 'user_id', 'id');
    }
    public function systemUpdateCheck()
    {
        return $this->hasOne(SystemUpdateCheck::class, 'user_id', 'id');
    }
    public function refreshAnnualReviews()
    {
        return $this->hasManyThrough(
            RefreshAnnualReview::class,
            RefreshAccount::class,
            'user_id',
            'refresh_account_id',
            'id',
            'id',
        );
    }
    public function activeLeaveRecord()
    {
        return $this->hasOne(UserLeaveRecord::class, 'user_id', 'id')
            ->where('active', 1);
    }

    public function employeeChangeApplications()
    {
        return $this->hasMany(EmployeeChangeApplication::class, 'user_id', 'id');
    }

    public function submittedEmployeeChangeApplications()
    {
        return $this->hasMany(EmployeeChangeApplication::class, 'submitted_by', 'id');
    }

    public function reviewedEmployeeChangeApplications()
    {
        return $this->hasMany(EmployeeChangeApplication::class, 'reviewed_by', 'id');
    }

    public function getRefreshCurrentBalanceAttribute(): int
    {
        $refreshAccount = $this->refreshAccount;

        if (!$refreshAccount) {
            return 0;
        }

        $grantRemaining = $refreshAccount->grants
            ->whereNotNull('remaining_amount')
            ->sum(fn (RefreshGrant $grant) => (int) $grant->remaining_amount);

        return (int) ($refreshAccount->opening_remaining_amount ?? 0) + (int) $grantRemaining;
    }
    
}
