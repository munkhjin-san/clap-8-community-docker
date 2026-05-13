<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BadgeService;
use App\Services\ReminderMessageService;
use App\Services\RemindTaskService;
use Illuminate\Support\Facades\Auth;
use App\Models\CustomForm;
use App\Models\User;
use App\Models\EvaluationRecord;
use App\Models\ProjectGoal;
use App\Models\ProjectAssignRecord;
use Carbon\Carbon;
use App\Models\ProjectRecord;
use App\Models\CalendarRecord;
use App\Models\PostRecord;
use App\Models\AssetRecord;
use App\Models\workTemp;
use App\Models\timecardRecord;
use App\Models\shiftRecord;
use App\Models\attendanceRecord;
use App\Models\NoticeRecord;
use App\Models\Incident;
use App\Models\SystemUpdateCheck;
use App\Models\SystemUpdateRecord;


class DashboardController extends Controller
{

    public function __construct(
        protected BadgeService $badgeService,
        protected ReminderMessageService $reminderMessageService,
        protected RemindTaskService $remindTaskService,
    ){

    } 
    private function active_user(){
        $sub = Auth::user()->linked()->where('main_id', Auth::id())->wherePivot('active', 1)->first();
        if($sub){
            return $sub;
        }else{
            return Auth::user();
        }
    }
    public function dashboard_data(Request $request)
    {
        $user = $this->active_user();

        $requestedData = $request->input('requestedData', []);
        $responseCollection = [];
        foreach($requestedData as $dataKey){
            $methodName = lcfirst(implode('', array_map('ucfirst', explode('_', $dataKey))));
            if(method_exists($this, $methodName)){
                // dd($methodName);
                $responseCollection[$dataKey] = $this->$methodName();
            }
        }
        return response()->json($responseCollection);
    }
    public function mustCheckMessages(){
        $active_user = $this->active_user();
        $messages = $this->reminderMessageService->getReminderMessagesForUser($active_user, ['mustCheckMessages']);
        return $messages['mustCheckMessages'];
    }
    public function mustSignMessages(){
        $active_user = $this->active_user();
        $messages = $this->reminderMessageService->getReminderMessagesForUser($active_user, ['mustSignMessages']);
        return $messages['mustSignMessages'];
    }
    public function remindedMessages(){
        $active_user = $this->active_user();
        $messages = $this->reminderMessageService->getReminderMessagesForUser($active_user, ['remindedMessages']);
        return $messages['remindedMessages'];
    }
    public function pendingApprovalTasks(){
        $active_user = $this->active_user();
        $tasks = $this->remindTaskService->getReminderTaskForUser($active_user, ['pendingApprovalTasks']);
        return $tasks['pendingApprovalTasks'];
    }
    public function unfinishedTasks(){
        $active_user = $this->active_user();
        $tasks = $this->remindTaskService->getReminderTaskForUser($active_user, ['unfinishedTasks']);
        return $tasks['unfinishedTasks'];
    }
    public function untouchedTasks(){
        $active_user = $this->active_user();
        $tasks = $this->remindTaskService->getReminderTaskForUser($active_user, ['untouchedTasks']);
        return $tasks['untouchedTasks'];
    }

    public function projects()
    {
        $activeUser = $this->active_user();
        
        $officer_approval_waiting = match (true) {
            in_array($activeUser->id, [608, 610], true) => ProjectRecord::select('id', 'status', 'name', 'contract_started_at', 'category', 'date_start', 'date_end')
            ->whereIn('status', [
                'pending_director',
                'director_approved'
            ])
            ->with(['manager' => fn($q) => $q->select('users.name')  ])
            ->get(),

            $activeUser->position_id < 6 => ProjectRecord::select('id', 'status', 'name', 'contract_started_at', 'category', 'date_start', 'date_end')
                ->where('status', 'pending_director')
                ->with(['manager' => fn($q) => $q->select('users.name')  ])
                ->get(),

            $activeUser->position_id === 6 => ProjectRecord::select('id', 'status', 'name', 'contract_started_at', 'category', 'date_start', 'date_end')
                ->where('status', 'returned')
                ->whereHas('manager', function ($q) use ($activeUser) {
                    $q->where('users.id', $activeUser->id);
                })
                ->get(),

            default => collect(),
        };

        $assign_approval_waiting = ProjectAssignRecord::where('user_id', $activeUser->id)
            ->where('status', '本人確認中')
            ->whereNull('confirmed_at')
            ->select('id', 'project_record_id','project_member_role_id', 'status', 'created_at', 'updated_at', 'confirmed_at') // make sure to include confirmed_at for the frontend to know it's pending
            ->with([
                'projectRecord:id,name,date_start,date_end,category',
                'projectRecord.manager:users.id,users.name',
                'questions.elements',
                'questions.answers.element_answers',
                'projectMemberRole:id,title,description', // Include the project member role relationship
                'actions' => fn($q) => $q->where('action_type', 'member_confirmation_items'),
            ])
            ->get();
        
        return [
            'officer_approval_waiting' => $officer_approval_waiting,
            'assign_approval_waiting' => $assign_approval_waiting,
        ];
    }

    public function forms() {
        $active_user = $this->active_user();
        $userId = $active_user->id;
        $today = now();
        $prevStart = $today->copy()->subMonthNoOverflow()->startOfMonth();
        $prevEnd   = $today->copy()->subMonthNoOverflow()->endOfMonth()->endOfDay();
        $currStart = $today->copy()->startOfMonth();
        $currEnd   = $today->copy()->endOfMonth()->endOfDay();

        $forms = CustomForm::whereHas('users', fn($q) => $q->where('users.id', $userId))
            ->where(function ($q) use ($userId, $today, $prevStart, $prevEnd, $currStart, $currEnd) {

                // non-repeating: show only if user has never completed (status=2) any answer
                $q->where(function ($qq) use ($userId) {
                    $qq->where('repeat_setting', 0)
                    ->whereDoesntHave('survey_answers', fn($a) =>
                        $a->where('user_id', $userId)->where('status', 2)
                    );
                })

                // repeating
                ->orWhere(function ($qq) use ($userId, $today, $prevStart, $prevEnd, $currStart, $currEnd) {
                    $qq->where('repeat_setting', 1)
                    ->where(function ($w) use ($userId, $today, $prevStart, $prevEnd, $currStart, $currEnd) {
                        // BEFORE repeat day: must have no completed answer in PREVIOUS month
                        $w->where(function ($w1) use ($userId, $today, $prevStart, $prevEnd, $currStart) {
                                $w1->where('repeat_day', '>', $today->day)
                                ->where('created_at', '<=', $currStart) // created ON/BEFORE first day of CURRENT month
                                ->whereDoesntHave('survey_answers', fn($a) =>
                                    $a->where('user_id', $userId)
                                        ->where('status', 2)
                                        ->whereBetween('target_date', [$prevStart, $prevEnd])
                                );
                        })
                        // ON/AFTER repeat day: must have no completed answer in CURRENT month
                        ->orWhere(function ($w2) use ($userId, $today, $currStart, $currEnd) {
                                $w2->where('repeat_day', '<=', $today->day)
                                ->whereDoesntHave('survey_answers', fn($a) =>
                                    $a->where('user_id', $userId)
                                        ->where('status', 2)
                                        ->whereBetween('target_date', [$currStart, $currEnd])
                                );
                        });
                    });
                });
            })
            ->with([
                'users',
                'admins',
                'survey_answers' => fn($q) => $q->select('user_id', 'custom_form_id') // this is fine; unrelated to filtering
            ])
            ->get();

        

        
        $forms->each(function ($form) {
            $answeredUserIds = $form->survey_answers->pluck('user_id')->toArray();
            $form->users->each(function ($user) use ($answeredUserIds) {
                $user->is_answered = in_array($user->id, $answeredUserIds);
            });
        });
        return $forms;
    }
    public function overdueGraveCount()
    {
        $active_user = $this->active_user();
        $userId = $active_user->id;
        $now = Carbon::now();

        $fiscalYear = $now->month >= 4 ? $now->year : $now->year - 1;

        $firstStart = Carbon::create($fiscalYear, 4, 1)->startOfDay();
        $firstEnd   = Carbon::create($fiscalYear, 9, 30)->endOfDay();

        $current_half = $now->between($firstStart, $firstEnd) ? 'first' : 'second';
        $previous_half = $current_half === 'first' ? 'second' : 'first';

        $allowedPeriods = [
            ['year' => $fiscalYear, 'which_half' => $current_half],
            ['year' => $fiscalYear, 'which_half' => $previous_half],
        ];

        $members = User::query()
            ->where(function ($q) use ($userId, $now, $allowedPeriods) {
                $q->where('id', $userId)
                ->whereHas('outcome_goals', fn($og) => $og->inAllowedHalves($allowedPeriods)->overdue($now))
                ->orWhereHas('outcome_goals', function ($og) use ($userId, $now, $allowedPeriods) {
                    $og->whereHas('project.manager', fn($m) => $m->where('users.id', $userId))
                        ->inAllowedHalves($allowedPeriods)
                        ->overdue($now);
                });
            })
            ->select(['id', 'name', 'icon_path', 'icon_bg'])
            ->with([
                'outcome_goals' => function ($og) use ($userId, $now, $allowedPeriods) {
                    $og->relevantToViewer($userId)
                    ->inAllowedHalves($allowedPeriods)
                    ->overdue($now)
                    ->with(['project.manager', 'project.members', 'reports.user']);
                },
            ])
            ->get();

        // Add computed flag per member
        $members->each(function ($member) use ($now) {
            $member->has_overdue_grace = $member->outcome_goals->contains(function ($goal) use ($now) {
                if (empty($goal->end_date)) return false;
                return $now->gt(Carbon::parse($goal->end_date)->endOfDay()->addDays(7));
            });
        });


        return [
            'overdueGoals' => $members,
            'overdueGraveCount' => $members->where('has_overdue_grace', true)->count(),
        ];
    }


    public function timesheet() {
        $pendingTimesheets = $this->pendingDailyReports();
        $departuresReportUsers = $this->departuresReportUsers();
        $pendingPlannedLeaves = $this->pendingPlannedLeaves();
        $pendingAttendance = $this->pendingAttendance();
        return [
            "pendingTimesheets" => $pendingTimesheets,
            "departuresReportUsers" => $departuresReportUsers,
            "pendingPlannedLeaves" => $pendingPlannedLeaves,
            "pendingAttendance" => $pendingAttendance,
        ];
    }
    public function pendingAttendance() {
        
        $user = Auth::user();
        if ($user->position_id < 6 || $user->position_id === 14) return null;
        
        $previousMonth = Carbon::now()->subMonthNoOverflow()->format('Y-m');
        $previousM = Carbon::now()->subMonthNoOverflow()->month;
        $previousY = Carbon::now()->subMonthNoOverflow()->year;
        $timesheets = timecardRecord::where('user_id', $user->id)
        ->whereYear('day', $previousY)
        ->whereMonth('day', $previousM)
        ->exists();
        $pendingAttendance = attendanceRecord::where('user_id', $user->id)
        ->where('date_year_month', $previousMonth)->first();
        if (!$pendingAttendance && $timesheets) {
            $data = [
                "user_id" => $user->id,
                "date_year_month" => $previousMonth,
            ];
            return $data;
        }
        return null;
    }
    public function pendingDailyReports(){
        $date = Carbon::now();
        $day = $date->day;
        $year = $date->year;
        $month = $date->month;
        $prev_month = $month == 1 ? $month : $date->clone()->subMonth()->month;
        $shift_month = $day >= 25 ? $date->clone()->addMonthNoOverflow()->month : $month;
        $prev_month_start = $date->clone()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d');
        $ids = [608, 610];
        $active_user = $this->active_user();
        $target_users = [];
        $workGroupIds = [];
        $list = [];

        $headquartersIds = [];
        $hqProject = ProjectRecord::where('id', 20)->first();
        if($hqProject){
            $headquartersIds = $hqProject->members()->pluck('users.id')->toArray();
        }
        if(in_array($active_user->id, $ids)){
            $pms = User::where('retire', 0)
                        ->where('partner_flag', 0)
                        ->where('deleted_flag', 0)
                        ->where('on_leave', 0)
                        ->where(function ($query) use ($headquartersIds) {
                            $query->where('position_id', 6)
                            ->orWhereIn('id', $headquartersIds);
                        })
                        
                        ->pluck('id')->toArray();
            $target_users = $pms;
            $workGroupIds = ProjectRecord::pluck('id')->unique()->values()->toArray();
        }
        if($active_user->position_id == 6){
            $workGroups = ProjectRecord::whereHas('manager', function ($q) use($active_user, $ids) {
                $q->where('users.id', $active_user->id)->whereNotIn('users.id', $ids);
            })->with('members')->get();
            
            $workGroupIds = $workGroups->unique()->pluck('id')->toArray();
        
            $workGroups = $workGroups->flatMap(function ($workGroup) {
                return $workGroup->members;
            })->unique('id')->values()->pluck('id')->toArray();
            $target_users = array_merge($target_users, $workGroups);
        }
        $user_list = User::whereIn('id', $target_users)
                        ->with([
                            'time_card_records' => function ($q) use($year, $month, $workGroupIds, $prev_month, $prev_month_start) {
                                $q->where('day', '>=', $prev_month_start)
                                    ->where('status_flag', 1)
                                    ->whereIn('work_group_id', $workGroupIds)
                                    ->selectRaw('MONTH(day) as month, COUNT(*) as count, user_id')
                                    ->groupByRaw('MONTH(day), user_id');
                            },
                            'shift_overtime' => function ($q) use($year, $month) {
                                $q->where('status', 1)
                                    ->whereYear('overtime_day', $year)
                                    ->whereMonth('overtime_day', $month);
                            },
                            'shift_records' => function ($q) use ($year, $month, $prev_month, $shift_month) {
                                $q->whereYear('shift_day', $year)
                                    ->where('status_flag', 2)
                                    ->where(function ($innerQuery) use ($month, $prev_month, $shift_month) {
                                      $innerQuery->whereMonth('shift_day', $month)
                                                 ->orWhereMonth('shift_day', $prev_month)
                                                 ->orWhereMonth('shift_day', $shift_month);
                                    })->selectRaw('MONTH(shift_day) as month, COUNT(*) as count, user_id')
                                    ->groupByRaw('MONTH(shift_day), user_id');
                            }
                        ])->select('id', 'name', 'icon_path', 'icon_bg')->get();
        foreach($user_list as $user){
            $timeCardsCount = $user->time_card_records;
            $overtimeRequests = $user->shift_overtime->count();
            $shiftCount = $user->shift_records;
            
             $d = [
                "user" => $user,
                "timecard" => $timeCardsCount,
                "overtime" => $overtimeRequests,
                "shift" => $shiftCount,
            ];
            if($timeCardsCount->count() || $overtimeRequests || $shiftCount->count()){                    
                $list[] = $d;
            }
        }
        // $data = [
        //     "remind_timesheet" => $list
        // ];
        // return response()->json($data);
        return $list;
    }

    public function schedules(){
        $active_user = $this->active_user();
        $userId = $active_user->id;

        $now = now();

        $monthStart = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $nextWeekEnd = $now->copy()->addWeek()->endOfWeek();

        // later of endOfMonth / nextWeekEnd
        $upperBound = $endOfMonth->greaterThan($nextWeekEnd) ? $endOfMonth : $nextWeekEnd;

        $records = CalendarRecord::query()
            ->whereHas('calendar_users', fn ($q) => $q->where('user_id', $userId))
            ->whereBetween('date_start', [$monthStart, $upperBound]) // starts in range
            ->whereNot('title', '休日')
            ->with([
                'department',
                'task',
                'updated_by',
                'calendar_users' => fn($q) => $q->where('user_id', $userId),
            ])
            ->get();

        $thisWeekStart = $now->copy()->startOfWeek();
        $thisWeekEnd   = $now->copy()->endOfWeek();

        $nextWeekStart = $now->copy()->addWeek()->startOfWeek();
        $nextWeekEnd   = $now->copy()->addWeek()->endOfWeek();

        // temp: “this month and onward”
        $tempSchedules = $records->where('temp_flag', 1)->values();

        // If you want "starts in week" (same as your original logic)
        $thisWeek = $records->filter(fn($r) =>
            $r->date_start >= $thisWeekStart && $r->date_start <= $thisWeekEnd && $r->temp_flag != 1
        )->values();

        $nextWeek = $records->filter(fn($r) =>
            $r->date_start >= $nextWeekStart && $r->date_start <= $nextWeekEnd && $r->temp_flag != 1
        )->values();

        return [
            'temp_schedules' => $tempSchedules,
            'this_week_schedules' => $thisWeek,
            'next_week_schedules' => $nextWeek,
        ];
        
    }
    public function challenges()
    {
        $now = now();
        $active_user = $this->active_user();
        $niceReminders = $this->niceFollowUpReminders($active_user->id, $now);
        $challengesQuery = PostRecord::query()
            ->where('app_type', 2)
            ->whereHas('to_users', function ($q) use ($active_user) {
                $q->where('users.id', $active_user->id);
            })
            ->whereNotNull('date_start')
            ->whereNotNull('date_end')
            ->with(['progressReports' => function ($query) {
                $query->select('id', 'record_id', 'created_at', 'progress_checkpoint')
                    ->orderByDesc('created_at');
            }])
            ->orderByDesc('date_start');

        $progressNeed = (clone $challengesQuery)
            ->whereIn('status_flag', [0, 5])
            ->where('date_start', '<=', $now)
            ->where('date_end', '>=', $now)
            ->get();
        
        $updateNeed = (clone $challengesQuery)->where('date_end', '<=', $now)->whereIn('status_flag', [0, 5])->get();
        $data = $progressNeed->map(function ($challenge) use ($now) {
            $start = Carbon::parse($challenge->date_start);
            $end = Carbon::parse($challenge->date_end);
            $checkpoint = $this->latestReachedProgressCheckpoint($start, $end, $now);
            
            if (!$checkpoint) {
                return null;
            }

            $elapsed = $start->diffInSeconds($now);
            $total = max(1, $start->diffInSeconds($end));
            $pct = (int) round(($elapsed / $total) * 100);
            $pct = max(0, min(100, $pct));
            $checkpointDate = $this->progressCheckpointDate($start, $end, $checkpoint);
            $latestProgressReport = optional($challenge->progressReports)->sortByDesc('created_at')->first();

            if ($latestProgressReport && Carbon::parse($latestProgressReport->created_at)->greaterThanOrEqualTo($checkpointDate)) {
                return null;
            }

            $challenge['attention_type'] = 'progress_need';
            $challenge['attention_checkpoint'] = $checkpoint;
            $challenge['attention_progress_percent'] = $pct;
            $challenge['attention_deadline'] = Carbon::parse($challenge->date_end)->toIso8601String();
            return $challenge;
        })->filter()->values();
       
        $updateNeed->each(function ($challenge) {
            $challenge['attention_type'] = 'update_need';
            $challenge['attention_deadline'] = Carbon::parse($challenge->date_end)->toIso8601String();
        });
        $final = $data->concat($updateNeed)->sortBy('date_start')->values();

        return $niceReminders->concat($final)->values();

    }
    private function latestReachedProgressCheckpoint(Carbon $start, Carbon $end, Carbon $now): ?int
    {
        
        foreach ([75, 50] as $checkpoint) {
            if ($now->greaterThanOrEqualTo($this->progressCheckpointDate($start, $end, $checkpoint))) {
                return $checkpoint;
            }
        }

        return null;
    }
    private function progressCheckpointDate(Carbon $start, Carbon $end, int $checkpoint): Carbon
    {
        $startDay = $start->copy()->startOfDay();
        $endDay = $end->copy()->startOfDay();
        $totalDays = max(1, $startDay->diffInDays($endDay));
        $checkpointDays = (int) floor($totalDays * ($checkpoint / 100));

        return $startDay->copy()->addDays($checkpointDays);
    }
    private function niceFollowUpReminders(int $userId, Carbon $now)
    {
        $niceReminderStartDate = Carbon::create(2026, 4, 1)->startOfDay();

        $receivedNicePosts = PostRecord::query()
            ->where('app_type', 0)
            ->where('user_id', '!=', $userId)
            ->where('created_at', '>=', $niceReminderStartDate)
            ->whereHas('to_users', function ($q) use ($userId) {
                $q->where('users.id', $userId);
            })
            ->with(['user', 'to_users'])
            ->orderByDesc('created_at')
            ->get();

        if ($receivedNicePosts->isEmpty()) {
            return collect();
        }

        $sentNicePosts = PostRecord::query()
            ->where('app_type', 0)
            ->where('user_id', $userId)
            ->orderBy('created_at')
            ->get(['id', 'created_at']);

        return $receivedNicePosts
            ->filter(function ($post) use ($sentNicePosts) {
                return !$sentNicePosts->contains(function ($sentPost) use ($post) {
                    return $sentPost->created_at->gt($post->created_at);
                });
            })
            ->map(function ($post) use ($now) {
                $deadline = Carbon::parse($post->created_at)->addWeek();
                $post['attention_type'] = 'nice_follow_up';
                $post['attention_deadline'] = $deadline->toIso8601String();
                $post['attention_is_overdue'] = $now->gt($deadline);

                return $post;
            })
            ->sortByDesc(function ($post) {
                return optional($post->created_at)->timestamp ?? 0;
            })
            ->sortByDesc(function ($post) {
                return $post['attention_is_overdue'] ? 1 : 0;
            })
            ->values();
    }
    public function departuresReportUsers($badge = false) {
        if(!in_array(Auth::id(), [833,832])){
            return [];
        }
        $target_users = User::where('position_id', 15)->where('retire' , 0)->whereNotNull('email')
        ->whereHas('related_projects', function ($query) {
            $query->whereIn('project_records.id', [34, 36, 56]);
        })
        ->whereHas('shift_records', function ($query) use ($badge) {
            $query->when($badge, fn($q) => $q->whereNull('departure_report'))
                  ->where('shift_day', Carbon::now()->toDateString())
                  ->where('shift_type', 1);
        })->with(['shift_records' => function ($query) {
            $query->where('shift_day', Carbon::now()->toDateString())->where('shift_type', 1)->select('user_id', 'id', 'departure_report');
        }])->select('id', 'name', 'icon_path', 'icon_bg')->get();

        return $target_users;
    }
    public function assets(){
        $active_user = $this->active_user();
        $target_assets = AssetRecord::where('user_id', $active_user->id)
        ->with(['confirm_logs' => fn($q) => $q->whereYear('created_at', now()->year) ])
        ->orderBy('created_at', 'desc')
        ->get();
        $waiting_approval = [];
        if($active_user->id === 610 || $active_user->id === 608){
            $waiting_approval = AssetRecord::whereHas('requests')->with(
                [
                    'requests' => function ($query) {
                        $query->with(['recieve_user', 'send_user', 'files', 'steps' => function ($query) {
                            $query->with(['approver', 'creator'])->orderBy('value', 'desc');
                        }])->orderBy('created_at', 'desc');
                    }
                ]
            )->get();
        }
        return [
            "in_use" => $target_assets,
            "waiting_approval" => $waiting_approval
        ];
    }
    public function pendingPlannedLeaves(){
        $notificationUser = User::select('name', 'id', 'icon_path', 'icon_bg')->findOrFail(610);
        $date = Carbon::now();
        $year = $date->year;
        $active_user = $this->active_user();
        $user_code = $active_user->user_code;
        $tempData = workTemp::where('user_code', $user_code)
                    ->where(function ($query) use ($year) {
                        $query->whereYear('date', $year - 1)
                              ->orWhereYear('date', $year)
                              ->orWhereYear('date', $year + 1);
                    })
                    ->get();
        $list = [];
        foreach($tempData as $temp) {
            $startDate = $temp->date;
           $endDate = Carbon::parse($startDate)
            ->addYear()
            ->subDay()
            ->format('Y-m-d');
            $planned_year = Carbon::createFromFormat('Y-m-d', $startDate)->year;
            $temp['notification_user'] = $notificationUser;
            $temp['endDate'] = $endDate;
            $planned_shifts = shiftRecord::where('planned_year', $planned_year)->where('shift_type', 3)->where('user_id', Auth::id())->count();
            $plannedDateCarbon = Carbon::createFromFormat('Y-m-d', $startDate);
            $remaining_days = $planned_year === 2023 ? 0 : $temp->planned_days - $planned_shifts;
            if($remaining_days > 0){
                $list[] = [
                    "shift_count" => $planned_shifts,
                    "tempData" => $temp,
                    "remaining_days" => $remaining_days,
                ];
                
            }
        }
        return $list;
    }
    public function pendingGoalsUserForHR() {
        $user = Auth::user();
        if ($user->id === 631) {
            $members = $this->getAdminMembers();
            return $members;
        } 
        return [];
        // elseif ($user->position_id == 6) {
        //     $members = $this->getUserMembers($user->id);
        // } elseif ($user->position_id < 6) {
        //     $members = $this->getUserManagers($user->id);
        // } else {
        //     $members = $this->getUserMentors($user->id);
        // }
        
        // $data = [
        //     "remind_project_not_approved" => $members,
        //     "not_approved_increases" => $user->id === 604 || $user->id === 631 ? $this->not_approved_increases() : []
        // ];
        // return response()->json($data);
    }

    private function personnelEvaluation(){
        $evaluations = EvaluationRecord::where('status', 2)
            ->where('created_at', '>', Carbon::now()->subMonths(3))
            ->with('user.positions', 'checklist', 'candidate', 'mentor')
            ->get();

        $assigns = ProjectAssignRecord::whereIn('status', ['人事対応中', '本人取り下げ'])
            ->whereNull('confirmed_at')
            ->with(['user.positions', 'projectRecord' => fn($query) => $query->select(['id', 'name']), 'createdUser'])
            ->select('id', 'user_id', 'project_record_id', 'status', 'created_at', 'updated_at', 'confirmed_at', 'score', 'support_level')
            ->get();
        return [
            'pendingEvaluations' => $evaluations,
            'pendingAssignments' => $assigns,
        ];
    }
    private function getAdminMembers() {
        return User::whereHas('outcome_goals', function ($query) {
            $query->where('status', 3)
                ->orWhereHas('salaryIssue', function ($query) {
                    $query->where(function ($subQuery) {
                        $subQuery->where('status', 3)
                            ->orWhere('status', 9);
                    });
                });
        })
        ->orWhereHas('salary_issues', function ($query) {
            $query->whereHas('project_goal')->where('status', 3)
                ->orWhere('status', 9);
        })
        ->with([
            'outcome_goals' => function ($query) {
                $query->where('status', 3)
                    ->orWhereHas('salaryIssue', function ($query) {
                        $query->where(function ($subQuery) {
                            $subQuery->where('status', 3)
                                ->orWhere('status', 9);
                        });
                    })
                    ->with([
                        'project.manager',
                        'project.members',
                        'reports.user',
                        'salaryIssue' => function ($si) {
                            $si->with(['reports.user']);
                        },
                    ]);
            },
            'salary_issues' => function ($query) {
                $query->where('status', 3)
                        ->orWhere('status', 9);
            }
        ])->select('id', 'name', 'icon_path', 'icon_bg')->get();
    }

    public function notices()
    {
        $user = $this->active_user();
        $userId = $user->id;
        $userCreatedAt = $user->joined_date;
        if(!$userCreatedAt){
            return []; // 安全策: ユーザーの作成日時が不明な場合は空を返す
        }
        // 1. このユーザーにとって「既読」とみなせる最新の通知を1件取得
        $unreadNotices = NoticeRecord::
            where('deleted_flag', 0)
            ->where('created_at', '>=', $userCreatedAt)
            ->where('created_at', '>=', '2026-04-01')
            ->whereDoesntHave('readers', function ($query) use ($userId) {
                $query->where('users.id', $userId);
            })
            ->orderBy('created_at', 'desc')
            ->select('id', 'title')
            ->withExists([
                'readers as read' => function ($query) use ($userId) {
                    $query->where('users.id', $userId);
                },
            ])
            ->get();

        return $unreadNotices;
    }
    private function incidentQuery(bool $withDetail = true)
    {
        $columns = [
            'id',
            'title',
            'description',
            'caused_by',
            'incident_category_id',
            'project_record_id',
            'status',
            'occurred_date',
            'created_at',
        ];

        if ($withDetail) {
            $columns = array_merge($columns, [
                'reported_by',
                'incident_punishment_id',
                'reason',
                'prevention',
                'instruction',
                'resolution',
                'occured_location',
                'memo',
                'instruction_date',
                'related_parties',
                'amount_of_damage',
                'risk_level',
                'severity_level',
                'private_notes',
                'committee_members',
                'committee_decision',
                'committee_decision_date',
                'updated_at',
            ]);
        }

        return Incident::query()
            ->select(array_values(array_unique($columns)))
            ->with([
                'reportedByUser',
                'causedByUser',
                'category',
                'punishment',
                'projectRecord:id,name,date_start,date_end,category',
                'projectRecord.manager',
            ]);
    }

    public function getIncidents(Request $request)
    {
        return $this->incidentQuery()
            ->orderByRaw('occurred_date is null')
            ->orderByDesc('occurred_date')
            ->orderByDesc('created_at')
            ->paginate((int) $request->input('per_page', 50));
    }

    private function incidents() {
        $activeUser = $this->active_user();

        return [
            'attention' => $this->incidentQuery(false)
            ->where(function ($query) use ($activeUser) {
                $query->where('caused_by', $activeUser->id)
                    ->orWhereHas('projectRecord.manager', function ($managerQuery) use ($activeUser) {
                        $managerQuery->where('users.id', $activeUser->id);
                    });
            })
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', '!=', '完了');
            })
            ->orderByRaw('occurred_date is null')
            ->orderByDesc('occurred_date')
            ->orderByDesc('created_at')
            ->get(),
        ];
    }
    public function systemUpdates() {
        return [];
        $activeUser = $this->active_user();
        $target_positions = [
            6, //執行役員,
            16, //プロジェクトリーダー,
            11, //正社員,
            12 //契約社員
        ];
        if (!in_array($activeUser->position_id, $target_positions, true)) {
            return 0;
        }
        $thresholdDate = Carbon::parse('2026-05-01');
        $count = SystemUpdateRecord::where('must_read', true)
        ->where('created_at', '>=', $thresholdDate)
        ->whereDoesntHave('systemUpdateChecks', function ($query) use ($activeUser) {
            $query->where('user_id', $activeUser->id);
        })->pluck('id')->toArray();
        return $count;
    }

}
