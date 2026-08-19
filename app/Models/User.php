<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;
use App\Services\Community\CommunityPermissionService;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;
class User extends Authenticatable implements PasskeyUser
{
    // use Notifiable;
    use HasApiTokens, Notifiable, SoftDeletes, TwoFactorAuthenticatable, PasskeyAuthenticatable;

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
        'two_factor_secret', 'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
        'email_otp_enabled_at' => 'datetime',
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
    /**
     * 振込口座（1人1件）。管理者のみが扱う機微情報なので、既定でどこからも eager load しないこと
     * — 必要な経路が明示的に読む。番号はモデル側で復号される。
     */
    public function bankAccount(){
        return $this->hasOne(EmployeeBankAccount::class, 'user_id', 'id');
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
    public function communities(): BelongsToMany
    {
        return $this->belongsToMany(Community::class, 'community_user')
            ->using(CommunityMembership::class)
            ->withPivot(['community_role_id', 'scope', 'is_default', 'last_active_at'])
            ->withTimestamps();
    }
    public function communityMemberships(): HasMany
    {
        return $this->hasMany(CommunityMembership::class);
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
        return app(CommunityPermissionService::class)->isAdmin($this);
    }
    public function isBoss(): bool
    {
        return app(CommunityPermissionService::class)->isBoss($this);
    }
    public function isPM(): bool
    {
        return app(CommunityPermissionService::class)->isPM($this);
    }
    public function isPartnerScope(): bool
    {
        return app(CommunityPermissionService::class)->isPartner($this);
    }
    public function isRegisteredScope(): bool
    {
        return app(CommunityPermissionService::class)->isRegistered($this);
    }

    /**
     * Canonical capability check for this user against the community
     * authorization model (admin bypass + the user's role capabilities,
     * resolved side-effect-free via CommunityPermissionService). This is the
     * PHP counterpart to the frontend `auth.can(capability)`; use it instead of
     * position_id / hardcoded-id checks. Note: unlike isBoss/isPM/etc. above,
     * this does NOT gate on app()->bound() — that guard resolves false (the
     * service is auto-resolved, never explicitly bound), which would make this
     * always return false. The service self-guards missing community tables.
     */
    public function hasCapability(string $capability): bool
    {
        return app(CommunityPermissionService::class)->can($capability, $this);
    }

    /**
     * Query scope: users whose community-membership role grants $capability.
     * The query counterpart to hasCapability() for whereIn/list contexts (e.g.
     * "all refresh-eligible employees"). Admin is included because the admin
     * role stores the full capability set (it also bypasses at runtime).
     */
    public function scopeWhereHasCapability($query, string $capability)
    {
        return $query->whereIn('id', function ($q) use ($capability) {
            $q->select('cu.user_id')
                ->from('community_user as cu')
                ->join('community_roles as r', 'r.id', '=', 'cu.community_role_id')
                ->whereJsonContains('r.capabilities', $capability);
        });
    }

    /**
     * Query scope: users whose community-membership role key is one of $keys
     * (e.g. 'admin'). The list counterpart to isAdmin()/the role predicates, for
     * whereIn/whereNotIn contexts that previously hardcoded admin id arrays.
     * Not community-filtered (single active community today) — mirrors
     * scopeWhereHasCapability.
     *
     * @param  string|array<int,string>  $keys
     */
    public function scopeWhereCommunityRole($query, string|array $keys)
    {
        $keys = (array) $keys;

        return $query->whereIn('id', function ($q) use ($keys) {
            $q->select('cu.user_id')
                ->from('community_user as cu')
                ->join('community_roles as r', 'r.id', '=', 'cu.community_role_id')
                ->whereIn('r.key', $keys);
        });
    }

    /**
     * Confine a user list to the ACTIVE community's members. `User` is not
     * community-scoped (membership lives in the community_user pivot, not a
     * column), so any picker/list endpoint built on `User::...` would otherwise
     * span every community. Apply this to member-picker endpoints.
     *
     * Fails open (no filter) when there is no active community — matching the
     * BelongsToCommunity global scope and harmless while single-community.
     */
    public function scopeInActiveCommunity($query)
    {
        $ids = app(\App\Services\Community\CommunityContext::class)->userIds();

        return $ids === null ? $query : $query->whereIn('id', $ids);
    }
    // Manage any member's timecard/shift/overtime: the work_authority column
    // (per-user grant) or the timesheet.manage_all blade (admin bypasses).
    public function canManageTimesheets(): bool
    {
        if ((int) $this->work_authority === 1) {
            return true;
        }

        return app(CommunityPermissionService::class)->can('timesheet.manage_all', $this);
    }
    // HR approval / confirmation (monthly-goal confirm & view, member assignment,
    // change applications). Replaces the hardcoded HR id 631. Admin bypasses.
    public function canHrApprove(): bool
    {
        return app(CommunityPermissionService::class)->can('hr.approve', $this);
    }

    // ⚠️ MERGE RED FLAG — do NOT re-add `protected $appends = ['is_admin']` +
    // getIsAdminAttribute() from main (origin commit 4d6140cb). In main, isAdmin()
    // is a cheap config-id check so the append is harmless; on THIS branch isAdmin()
    // resolves community→membership→role per user (a DB query), so the append fires
    // that for EVERY serialized User → N+1 (~1,277 queries on /board_list, ~0.78s).
    // The FE reads admin via communityRole/capabilities (store/auth.ts), NOT this
    // attribute — it is unused here. If a future main merge brings it back, delete it.
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
