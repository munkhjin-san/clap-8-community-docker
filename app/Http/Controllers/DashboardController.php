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
use App\Models\PostRelay;
use App\Models\AssetRecord;
use App\Models\workTemp;
use App\Models\timecardRecord;
use App\Models\shiftRecord;
use App\Models\attendanceRecord;
use App\Models\NoticeRecord;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\IncidentPunishment;
use App\Models\IncidentStatus;
use App\Models\FileRecord;
use App\Models\SystemUpdateCheck;
use App\Models\SystemUpdateRecord;
use App\Models\UserLeaveRecord;
use Illuminate\Support\Facades\DB;


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

        $projectReportBadges = $this->badgeService->getProjectUnreadCount($activeUser);
        $financeCommentBadges = $this->badgeService->financeComment($activeUser);

        $commentProjectIds = collect($projectReportBadges['records'] ?? [])
            ->pluck('project_record_id')
            ->merge(collect($financeCommentBadges['projects'] ?? [])->pluck('project_id'))
            ->filter()
            ->unique()
            ->values();

        $projectNames = $commentProjectIds->isEmpty()
            ? collect()
            : ProjectRecord::whereIn('id', $commentProjectIds)->pluck('name', 'id');

        $comments = collect($projectReportBadges['records'] ?? [])
            ->flatMap(function ($record) use ($projectNames) {
                return collect($record['types'] ?? [])->map(function ($type) use ($record, $projectNames) {
                    $projectId = (int) ($record['project_record_id'] ?? 0);
                    $section = $type['type'] ?? '詳細';

                    return [
                        'type' => $section === '詳細' ? 'project_detail' : 'confirmation_item',
                        'project_id' => $projectId,
                        'project_name' => $projectNames[$projectId] ?? 'プロジェクト',
                        'section' => $section,
                        'count' => (int) ($type['unread_count'] ?? 0),
                    ];
                });
            })
            ->merge(
                collect($financeCommentBadges['projects'] ?? [])->flatMap(function ($project) use ($projectNames) {
                    $projectId = (int) ($project['project_id'] ?? 0);

                    return collect($project['period_counts'] ?? [])
                        ->filter(fn ($count, $period) => is_string($period) && preg_match('/^\d{4}-\d{2}$/', $period))
                        ->map(function ($count, $period) use ($projectId, $projectNames) {
                            $monthLabel = Carbon::createFromFormat('Y-m', $period)->format('Y年n月');

                            return [
                                'type' => 'finance',
                                'project_id' => $projectId,
                                'project_name' => $projectNames[$projectId] ?? 'プロジェクト',
                                'period' => $period,
                                'month_label' => $monthLabel,
                                'count' => (int) $count,
                            ];
                        })
                        ->values();
                })
            )
            ->filter(fn ($comment) => ($comment['count'] ?? 0) > 0)
            ->values();
        
        return [
            'officer_approval_waiting' => $officer_approval_waiting,
            'assign_approval_waiting' => $assign_approval_waiting,
            'comments' => $comments,
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
        $today = $date->copy()->format('Y-m-d');
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
                        ])
                        ->select('id', 'name', 'icon_path', 'icon_bg')
                        ->withExists([
                            'time_card_records as has_pending_timecards' => function ($q) use ($today, $workGroupIds, $prev_month_start) {
                                $q->where('day', '>=', $prev_month_start)
                                    ->where('day', '<', $today)
                                    ->where('status_flag', 1)
                                    ->whereIn('work_group_id', $workGroupIds);
                            },
                        ])
                        ->get();
        foreach($user_list as $user){
            $timeCardsCount = $user->time_card_records;
            $overtimeRequests = $user->shift_overtime->count();
            $shiftCount = $user->shift_records;
            $hasPendingTimecards = (bool) $user->has_pending_timecards;
            
             $d = [
                "user" => $user,
                "timecard" => $timeCardsCount,
                "has_pending_timecards" => $hasPendingTimecards,
                "overtime" => $overtimeRequests,
                "shift" => $shiftCount,
            ];
            if($hasPendingTimecards || $timeCardsCount->count() || $overtimeRequests || $shiftCount->count()){                    
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
        $challengeRelays = $this->challengeRelayReminders($active_user->id, $now);
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

        return $challengeRelays->concat($niceReminders)->concat($final)->values();

    }
    private function challengeRelayReminders(int $userId, Carbon $now)
    {
        $pendingRelays = PostRelay::query()
            ->where('relay_type', PostRelay::TYPE_CHALLENGE)
            ->where('status', PostRelay::STATUS_PENDING)
            ->where('to_user_id', $userId)
            ->with(['fromUser', 'toUser', 'sourcePost'])
            ->orderBy('deadline_at')
            ->get()
            ->filter(function (PostRelay $relay) use ($userId) {
                return !PostRecord::query()
                    ->where('app_type', 2)
                    ->where('user_id', $userId)
                    ->where('created_at', '>=', $relay->assigned_at ?? $relay->created_at)
                    ->exists();
            })
            ->map(function (PostRelay $relay) use ($now) {
                return [
                    'id' => "challenge-relay-{$relay->id}",
                    'relay_id' => $relay->id,
                    'title' => 'チャレンジリレー',
                    'attention_type' => 'challenge_relay_received',
                    'attention_deadline' => optional($relay->deadline_at)->toIso8601String(),
                    'attention_is_overdue' => $relay->deadline_at ? $now->gt($relay->deadline_at) : false,
                    'user' => $relay->fromUser,
                    'to_user' => $relay->toUser,
                    'source_post_id' => $relay->source_post_id,
                    'source_post_title' => optional($relay->sourcePost)->title,
                    'created_at' => optional($relay->assigned_at)->toIso8601String(),
                ];
            });

        $returnedRelays = PostRelay::query()
            ->where('relay_type', PostRelay::TYPE_CHALLENGE)
            ->where('status', PostRelay::STATUS_DECLINED)
            ->where('from_user_id', $userId)
            ->whereNull('closed_at')
            ->with(['declinedByUser', 'toUser', 'sourcePost'])
            ->orderByDesc('declined_at')
            ->get()
            ->map(function (PostRelay $relay) {
                return [
                    'id' => "challenge-relay-returned-{$relay->id}",
                    'relay_id' => $relay->id,
                    'title' => 'チャレンジリレー',
                    'attention_type' => 'challenge_relay_returned',
                    'attention_deadline' => null,
                    'attention_is_overdue' => false,
                    'user' => $relay->toUser,
                    'declined_by_user' => $relay->declinedByUser,
                    'source_post_id' => $relay->source_post_id,
                    'source_post_title' => optional($relay->sourcePost)->title,
                    'created_at' => optional($relay->declined_at)->toIso8601String(),
                ];
            });

        return $pendingRelays->concat($returnedRelays)->values();
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
        if (in_array($userId, PostRelay::EXCLUDED_USER_IDS, true)) {
            return collect();
        }

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
            ->map(fn ($post) => $this->firstOrCreateNiceRelay($post, $userId))
            ->filter(fn (PostRelay $relay) => $relay->status === PostRelay::STATUS_PENDING)
            ->filter(function (PostRelay $relay) use ($sentNicePosts) {
                $sentPost = $sentNicePosts->first(function ($sentPost) use ($relay) {
                    return $sentPost->created_at->gt($relay->sourcePost->created_at);
                });

                if ($sentPost) {
                    $relay->update([
                        'status' => PostRelay::STATUS_COMPLETED,
                        'accepted_post_id' => $sentPost->id,
                        'closed_by_user_id' => $relay->to_user_id,
                        'closed_at' => $sentPost->created_at,
                    ]);
                    $this->closePendingNiceRelaySiblings($relay, $relay->to_user_id, $sentPost->created_at);
                    return false;
                }

                return true;
            })
            ->map(function (PostRelay $relay) use ($now) {
                $post = $relay->sourcePost;
                $deadline = $relay->deadline_at ?? Carbon::parse($post->created_at)->addWeek();
                $post['attention_type'] = 'nice_follow_up';
                $post['relay_id'] = $relay->id;
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
    private function closePendingNiceRelaySiblings(PostRelay $relay, int $closedByUserId, $closedAt): void
    {
        PostRelay::where('relay_type', PostRelay::TYPE_NICE)
            ->where('source_post_id', $relay->source_post_id)
            ->where('status', PostRelay::STATUS_PENDING)
            ->where('id', '!=', $relay->id)
            ->update([
                'status' => PostRelay::STATUS_CLOSED,
                'closed_by_user_id' => $closedByUserId,
                'closed_at' => $closedAt,
            ]);
    }
    private function firstOrCreateNiceRelay(PostRecord $post, int $userId): PostRelay
    {
        return PostRelay::firstOrCreate(
            [
                'relay_type' => PostRelay::TYPE_NICE,
                'source_post_id' => $post->id,
                'from_user_id' => $post->user_id,
                'to_user_id' => $userId,
            ],
            [
                'status' => PostRelay::STATUS_PENDING,
                'assigned_at' => $post->created_at,
                'deadline_at' => Carbon::parse($post->created_at)->addWeek(),
            ]
        )->loadMissing(['sourcePost.user', 'sourcePost.to_users']);
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
            ->with('files')
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
                'prevention_apply_status',
                'instruction',
                'resolution',
                'occured_location',
                'memo',
                'aftermath_comment',
                'instruction_date',
                'related_parties',
                'amount_of_damage',
                'payee',
                'expense_details',
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
            ->withCount('comments')
            ->with([
                'reportedByUser',
                'causedByUser',
                'category',
                'punishment',
                'projectRecord:id,name,date_start,date_end,category',
                'projectRecord.manager',
                'files',
            ]);
    }

    public function getIncidents(Request $request)
    {
        $activeUser = $this->active_user();

        if (!$this->canViewIncidentList($activeUser)) {
            abort(403);
        }

        $query = $this->incidentListQuery($activeUser);
        $this->applyIncidentFilters($query, $request);

        return $this->orderIncidentList($query)
            ->paginate((int) $request->input('per_page', 50));
    }

    public function getIncidentPage(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:incidents,id'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $activeUser = $this->active_user();

        if (!$this->canViewIncidentList($activeUser)) {
            abort(403);
        }

        $perPage = (int) ($validated['per_page'] ?? 50);
        $query = $this->incidentListQuery($activeUser);
        $this->applyIncidentFilters($query, $request);

        $ids = $this->orderIncidentList($query)
            ->pluck('id')
            ->values();
        $index = $ids->search((int) $validated['id']);

        if ($index === false) {
            abort(404);
        }

        return response()->json([
            'id' => (int) $validated['id'],
            'page' => (int) floor($index / $perPage) + 1,
        ]);
    }

    public function getIncidentOptions()
    {
        $activeUser = $this->active_user();
        $canManage = $this->canManageIncidentAdministration($activeUser);
        $canView = $this->canViewIncidentList($activeUser);
        $filterQuery = $this->incidentListQuery($activeUser);
        $filterUserIds = (clone $filterQuery)->pluck('caused_by')
            ->merge((clone $filterQuery)->pluck('reported_by'))
            ->filter()
            ->unique()
            ->values();
        $filterProjectIds = (clone $filterQuery)->pluck('project_record_id')
            ->filter()
            ->unique()
            ->values();
        $filterStatuses = (clone $filterQuery)
            ->whereNotNull('status')
            ->select('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status')
            ->values();

        return response()->json([
            'categories' => IncidentCategory::query()
                ->select('id', 'name', 'description', 'sort_order')
                ->orderByRaw('sort_order is null')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'punishments' => IncidentPunishment::query()
                ->select('id', 'name', 'description', 'sort_order')
                ->orderByRaw('sort_order is null')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'users' => User::query()
                ->select('id', 'name', 'icon_path', 'icon_bg', 'position_id')
                ->where('retire', 0)
                ->where('hide_flag', 0)
                ->orderByRaw('users.id = ? desc', [$activeUser->id])
                ->orderBy('name')
                ->get(),
            'projects' => ProjectRecord::query()
                ->select('id', 'name', 'date_start', 'date_end', 'category')
                ->orderByRaw(
                    'exists (select 1 from project_members where project_members.project_id = project_records.id and project_members.user_id = ?) desc',
                    [$activeUser->id]
                )
                ->orderByDesc('created_at')
                ->get(),
            'filter_users' => User::query()
                ->select('id', 'name', 'icon_path', 'icon_bg', 'position_id')
                ->whereIn('id', $filterUserIds)
                ->orderBy('name')
                ->get(),
            'filter_projects' => ProjectRecord::query()
                ->select('id', 'name', 'date_start', 'date_end', 'category')
                ->whereIn('id', $filterProjectIds)
                ->orderByDesc('created_at')
                ->get(),
            'statuses' => $this->incidentStatusNames($filterStatuses),
            'can_manage' => $canManage,
            'can_view' => $canView,
        ]);
    }

    public function getIncidentSettings()
    {
        $this->ensureCanManageIncidentSettings();

        return response()->json([
            'categories' => $this->incidentCategoryList(),
            'statuses' => $this->incidentStatusList(),
            'punishments' => $this->incidentPunishmentList(),
        ]);
    }

    public function createIncidentCategory(Request $request)
    {
        $this->ensureCanManageIncidentSettings();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:incident_categories,name'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $category = IncidentCategory::create([
            ...$validated,
            'sort_order' => $this->nextSortOrder(IncidentCategory::query()),
        ]);

        return response()->json($category);
    }

    public function updateIncidentCategory(Request $request)
    {
        $this->ensureCanManageIncidentSettings();

        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:incident_categories,id'],
            'name' => ['required', 'string', 'max:255', 'unique:incident_categories,name,' . $request->input('id')],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $category = IncidentCategory::findOrFail($validated['id']);
        $category->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json($category);
    }

    public function deleteIncidentCategory(Request $request)
    {
        $this->ensureCanManageIncidentSettings();

        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:incident_categories,id'],
        ]);

        $category = IncidentCategory::findOrFail($validated['id']);
        abort_if($category->incidents()->exists(), 422, '利用中の区分は削除できません。');
        $category->delete();

        return response()->json(['deleted' => true]);
    }

    public function reorderIncidentCategories(Request $request)
    {
        $this->ensureCanManageIncidentSettings();

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'distinct', 'exists:incident_categories,id'],
        ]);

        $this->persistSortOrder(IncidentCategory::class, $validated['ids']);

        return response()->json(['updated' => true]);
    }

    public function createIncidentStatus(Request $request)
    {
        $this->ensureCanManageIncidentSettings();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:incident_statuses,name'],
        ]);

        $status = IncidentStatus::create([
            'name' => $validated['name'],
            'sort_order' => $this->nextSortOrder(IncidentStatus::query()),
        ]);

        return response()->json($status);
    }

    public function updateIncidentStatus(Request $request)
    {
        $this->ensureCanManageIncidentSettings();

        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:incident_statuses,id'],
            'name' => ['required', 'string', 'max:255', 'unique:incident_statuses,name,' . $request->input('id')],
        ]);

        $status = IncidentStatus::findOrFail($validated['id']);
        DB::transaction(function () use ($status, $validated) {
            $oldName = $status->name;
            $status->update(['name' => $validated['name']]);

            if ($oldName !== $validated['name']) {
                Incident::where('status', $oldName)->update(['status' => $validated['name']]);
            }
        });

        return response()->json($status->fresh());
    }

    public function deleteIncidentStatus(Request $request)
    {
        $this->ensureCanManageIncidentSettings();

        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:incident_statuses,id'],
        ]);

        $status = IncidentStatus::findOrFail($validated['id']);
        abort_if(Incident::where('status', $status->name)->exists(), 422, '利用中のステータスは削除できません。');
        $status->delete();

        return response()->json(['deleted' => true]);
    }

    public function reorderIncidentStatuses(Request $request)
    {
        $this->ensureCanManageIncidentSettings();

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'distinct', 'exists:incident_statuses,id'],
        ]);

        $this->persistSortOrder(IncidentStatus::class, $validated['ids']);

        return response()->json(['updated' => true]);
    }

    public function createIncidentPunishment(Request $request)
    {
        $this->ensureCanManageIncidentSettings();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:incident_punishments,name'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $punishment = IncidentPunishment::create([
            ...$validated,
            'sort_order' => $this->nextSortOrder(IncidentPunishment::query()),
        ]);

        return response()->json($punishment);
    }

    public function updateIncidentPunishment(Request $request)
    {
        $this->ensureCanManageIncidentSettings();

        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:incident_punishments,id'],
            'name' => ['required', 'string', 'max:255', 'unique:incident_punishments,name,' . $request->input('id')],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $punishment = IncidentPunishment::findOrFail($validated['id']);
        $punishment->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json($punishment);
    }

    public function deleteIncidentPunishment(Request $request)
    {
        $this->ensureCanManageIncidentSettings();

        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:incident_punishments,id'],
        ]);

        $punishment = IncidentPunishment::findOrFail($validated['id']);
        abort_if($punishment->incidents()->exists(), 422, '利用中の懲罰区分は削除できません。');
        $punishment->delete();

        return response()->json(['deleted' => true]);
    }

    public function reorderIncidentPunishments(Request $request)
    {
        $this->ensureCanManageIncidentSettings();

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'distinct', 'exists:incident_punishments,id'],
        ]);

        $this->persistSortOrder(IncidentPunishment::class, $validated['ids']);

        return response()->json(['updated' => true]);
    }

    public function getIncidentLogs(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:incidents,id'],
        ]);

        $incident = Incident::findOrFail($validated['id']);
        $activeUser = $this->active_user();

        if (!$this->canAccessIncident($incident, $activeUser)) {
            abort(403);
        }

        return $this->resolveIncidentLogDisplayValues($incident->logs()->get());
    }

    public function createIncidentRecord(Request $request)
    {
        $activeUser = $this->active_user();

        $validated = $request->validate([
            'title' => ['sometimes', 'nullable', 'string'],
            'description' => ['sometimes', 'nullable', 'string'],
            'caused_by' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'incident_category_id' => ['sometimes', 'nullable', 'integer', 'exists:incident_categories,id'],
            'incident_punishment_id' => ['sometimes', 'nullable', 'integer', 'exists:incident_punishments,id'],
            'reason' => ['sometimes', 'nullable', 'string'],
            'prevention' => ['sometimes', 'nullable', 'string'],
            'prevention_apply_status' => ['sometimes', 'nullable', 'string'],
            'instruction' => ['sometimes', 'nullable', 'string'],
            'resolution' => ['sometimes', 'nullable', 'string'],
            'occured_location' => ['sometimes', 'nullable', 'string'],
            'memo' => ['sometimes', 'nullable', 'string'],
            'aftermath_comment' => ['sometimes', 'nullable', 'string'],
            'occurred_date' => ['sometimes', 'nullable', 'date'],
            'instruction_date' => ['sometimes', 'nullable', 'date'],
            'related_parties' => ['sometimes', 'nullable', 'string'],
            'project_record_id' => ['sometimes', 'nullable', 'integer', 'exists:project_records,id'],
            'status' => ['sometimes', 'nullable', 'string'],
            'amount_of_damage' => ['sometimes', 'nullable', 'numeric'],
            'payee' => ['sometimes', 'nullable', 'string'],
            'expense_details' => ['sometimes', 'nullable', 'string'],
            'risk_level' => ['sometimes', 'nullable', 'integer'],
            'severity_level' => ['sometimes', 'nullable', 'integer'],
            'private_notes' => ['sometimes', 'nullable', 'string'],
            'committee_members' => ['sometimes', 'nullable', 'string'],
            'committee_decision' => ['sometimes', 'nullable', 'string'],
            'committee_decision_date' => ['sometimes', 'nullable', 'date'],
            'file_ids' => ['sometimes', 'array'],
            'file_ids.*' => ['integer', 'distinct', 'exists:file_records,id'],
        ]);

        $fileIds = $validated['file_ids'] ?? [];
        unset($validated['file_ids']);

        if (!$this->canViewIncidentList($activeUser)) {
            abort(403);
        }

        if (
            !$this->canManageIncidentAdministration($activeUser)
            && array_intersect(array_keys($validated), $this->incidentManagementFields())
        ) {
            abort(403);
        }

        $createdIncident = DB::transaction(function () use ($validated, $fileIds, $activeUser) {
            $incident = Incident::create([
                ...$validated,
                'reported_by' => $activeUser->id,
                'status' => $validated['status'] ?? '報告済み',
            ]);

            if (!empty($fileIds)) {
                $incident->files()->syncWithPivotValues($fileIds, [
                    'attachable_type' => Incident::class,
                    'collection' => 'attachments',
                ]);
            }

            $changes = collect($validated)
                ->filter(fn ($value) => $value !== null && $value !== '')
                ->map(fn ($value) => ['old' => null, 'new' => $value])
                ->all();

            if (!empty($fileIds)) {
                $changes['files'] = ['old' => [], 'new' => $fileIds];
            }
            if (!array_key_exists('status', $changes) && $incident->status) {
                $changes['status'] = ['old' => null, 'new' => $incident->status];
            }

            $incident->logs()->create([
                'user_id' => $activeUser->id,
                'action' => 'created',
                'changes' => $changes,
            ]);

            return $this->incidentQuery()->whereKey($incident->id)->firstOrFail();
        });

        return response()->json([
            'incident' => $createdIncident,
            'created' => true,
        ]);
    }

    public function updateIncidentRecord(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:incidents,id'],
            'title' => ['sometimes', 'nullable', 'string'],
            'description' => ['sometimes', 'nullable', 'string'],
            'reported_by' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'caused_by' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'incident_category_id' => ['sometimes', 'nullable', 'integer', 'exists:incident_categories,id'],
            'incident_punishment_id' => ['sometimes', 'nullable', 'integer', 'exists:incident_punishments,id'],
            'reason' => ['sometimes', 'nullable', 'string'],
            'prevention' => ['sometimes', 'nullable', 'string'],
            'prevention_apply_status' => ['sometimes', 'nullable', 'string'],
            'instruction' => ['sometimes', 'nullable', 'string'],
            'resolution' => ['sometimes', 'nullable', 'string'],
            'occured_location' => ['sometimes', 'nullable', 'string'],
            'memo' => ['sometimes', 'nullable', 'string'],
            'aftermath_comment' => ['sometimes', 'nullable', 'string'],
            'occurred_date' => ['sometimes', 'nullable', 'date'],
            'instruction_date' => ['sometimes', 'nullable', 'date'],
            'related_parties' => ['sometimes', 'nullable', 'string'],
            'project_record_id' => ['sometimes', 'nullable', 'integer', 'exists:project_records,id'],
            'status' => ['sometimes', 'nullable', 'string'],
            'amount_of_damage' => ['sometimes', 'nullable', 'numeric'],
            'payee' => ['sometimes', 'nullable', 'string'],
            'expense_details' => ['sometimes', 'nullable', 'string'],
            'risk_level' => ['sometimes', 'nullable', 'integer'],
            'severity_level' => ['sometimes', 'nullable', 'integer'],
            'private_notes' => ['sometimes', 'nullable', 'string'],
            'committee_members' => ['sometimes', 'nullable', 'string'],
            'committee_decision' => ['sometimes', 'nullable', 'string'],
            'committee_decision_date' => ['sometimes', 'nullable', 'date'],
            'file_ids' => ['sometimes', 'array'],
            'file_ids.*' => ['integer', 'distinct', 'exists:file_records,id'],
        ]);

        $incidentId = $validated['id'];
        unset($validated['id']);
        $fileIds = $validated['file_ids'] ?? null;
        unset($validated['file_ids']);

        if (empty($validated) && $fileIds === null) {
            return response()->json([
                'incident' => $this->incidentQuery()->whereKey($incidentId)->firstOrFail(),
                'updated' => false,
            ]);
        }

        $activeUser = $this->active_user();

        if (
            !$this->canManageIncidentAdministration($activeUser)
            && array_intersect(array_keys($validated), $this->incidentManagementFields())
        ) {
            abort(403);
        }

        $updatedIncident = DB::transaction(function () use ($incidentId, $validated, $fileIds, $activeUser) {
            $incident = Incident::lockForUpdate()->findOrFail($incidentId);

            if (!$this->canAccessIncident($incident, $activeUser)) {
                abort(403);
            }

            $changes = [];

            foreach ($validated as $field => $newValue) {
                $oldValue = $incident->getAttribute($field);

                if ($oldValue == $newValue) {
                    continue;
                }

                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];

                $incident->setAttribute($field, $newValue);
            }

            if ($fileIds !== null) {
                $currentFileIds = $incident->files()->pluck('file_records.id')->sort()->values()->all();
                $nextFileIds = collect($fileIds)->map(fn ($id) => (int) $id)->sort()->values()->all();

                if ($currentFileIds !== $nextFileIds) {
                    $changes['files'] = [
                        'old' => $currentFileIds,
                        'new' => $nextFileIds,
                    ];
                }
            }

            if (empty($changes)) {
                return $this->incidentQuery()->whereKey($incidentId)->firstOrFail();
            }

            $incident->save();

            if ($fileIds !== null) {
                $incident->files()->syncWithPivotValues($fileIds, [
                    'attachable_type' => Incident::class,
                    'collection' => 'attachments',
                ]);
            }

            $singleField = count($changes) === 1 ? array_key_first($changes) : null;

            $incident->logs()->create([
                'user_id' => $activeUser->id,
                'action' => $singleField === 'status' ? 'status_changed' : 'updated',
                'field' => $singleField,
                'old_value' => $singleField ? $changes[$singleField]['old'] : null,
                'new_value' => $singleField ? $changes[$singleField]['new'] : null,
                'changes' => $changes,
            ]);

            return $this->incidentQuery()->whereKey($incident->id)->firstOrFail();
        });

        return response()->json([
            'incident' => $updatedIncident,
            'updated' => true,
        ]);
    }

    public function deleteIncidentRecord(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:incidents,id'],
        ]);

        $activeUser = $this->active_user();

        if (!$this->canManageIncidentAdministration($activeUser)) {
            abort(403);
        }

        DB::transaction(function () use ($validated, $activeUser) {
            $incident = Incident::lockForUpdate()->findOrFail($validated['id']);

            if (!$this->canAccessIncident($incident, $activeUser)) {
                abort(403);
            }

            $incident->logs()->create([
                'user_id' => $activeUser->id,
                'action' => 'deleted',
                'changes' => [
                    'deleted_at' => [
                        'old' => null,
                        'new' => now()->toDateTimeString(),
                    ],
                ],
            ]);

            $incident->delete();
        });

        return response()->json([
            'deleted' => true,
            'id' => $validated['id'],
        ]);
    }

    private function incidents() {
        $activeUser = $this->active_user();

        $query = $this->incidentQuery()->where(function ($statusQuery) {
            $statusQuery->whereNull('status')
                ->orWhere('status', '!=', '完了');
        });
        $isPM = $activeUser->position_id == 6;
        $isBoss = $activeUser->position_id && $activeUser->position_id < 6;
        $isAdmin = in_array($activeUser->id, [610, 608], true);

        if (!$isBoss && !$isAdmin) {
            if (!$isPM) {
                return ['attention' => collect()];
            }

            $query->whereHas('projectRecord.manager', function ($managerQuery) use ($activeUser) {
                $managerQuery->where('users.id', $activeUser->id);
            });
        }
        $query->orderByDesc('created_at')
        ->get();
        

        return [
            'attention' => $query->get(),
        ];
    }

    private function canAccessIncident(Incident $incident, User $user): bool
    {
        $isPM = $user->position_id == 6;
        $isBoss = $user->position_id && $user->position_id < 6;
        $isAdmin = in_array($user->id, [610, 608], true);

        if ($isBoss || $isAdmin) {
            return true;
        }

        if ($isPM) {
            return $incident->projectRecord()
                ->whereHas('manager', function ($managerQuery) use ($user) {
                    $managerQuery->where('users.id', $user->id);
                })
                ->exists();
        }

        return false;
    }

    private function canManageIncidentAdministration(User $user): bool
    {
        return ($user->position_id && $user->position_id < 6) || in_array($user->id, [608, 610], true);
    }

    private function incidentManagementFields(): array
    {
        return [
            'risk_level',
            'severity_level',
            'amount_of_damage',
            'payee',
            'expense_details',
            'committee_decision_date',
            'committee_members',
            'committee_decision',
            'memo',
            'aftermath_comment',
            'private_notes',
        ];
    }

    private function canViewIncidentList(User $user): bool
    {
        if ($this->canManageIncidentAdministration($user)) {
            return true;
        }

        if ($user->position_id != 6) {
            return false;
        }

        return ProjectRecord::query()
            ->whereHas('manager', function ($managerQuery) use ($user) {
                $managerQuery->where('users.id', $user->id);
            })
            ->exists();
    }

    private function ensureCanManageIncidentSettings(): void
    {
        abort_unless($this->canManageIncidentAdministration($this->active_user()), 403);
    }

    private function incidentCategoryList()
    {
        return IncidentCategory::query()
            ->select('id', 'name', 'description', 'sort_order')
            ->orderByRaw('sort_order is null')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function incidentStatusList()
    {
        return IncidentStatus::query()
            ->select('id', 'name', 'sort_order')
            ->orderByRaw('sort_order is null')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function incidentPunishmentList()
    {
        return IncidentPunishment::query()
            ->select('id', 'name', 'description', 'sort_order')
            ->orderByRaw('sort_order is null')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function resolveIncidentLogDisplayValues($logs)
    {
        $relationshipFields = [
            'caused_by' => ['model' => User::class, 'label' => 'name'],
            'reported_by' => ['model' => User::class, 'label' => 'name'],
            'project_record_id' => ['model' => ProjectRecord::class, 'label' => 'name'],
            'incident_category_id' => ['model' => IncidentCategory::class, 'label' => 'name'],
            'incident_punishment_id' => ['model' => IncidentPunishment::class, 'label' => 'name'],
            'files' => ['model' => FileRecord::class, 'label' => 'name'],
        ];

        $idsByField = [];
        foreach ($logs as $log) {
            foreach (($log->changes ?? []) as $field => $change) {
                if (!isset($relationshipFields[$field])) {
                    continue;
                }

                foreach (['old', 'new'] as $key) {
                    $value = $change[$key] ?? null;
                    if (is_array($value)) {
                        foreach ($value as $id) {
                            if ($id !== null && $id !== '') {
                                $idsByField[$field][] = (int) $id;
                            }
                        }
                    } elseif ($value !== null && $value !== '') {
                        $idsByField[$field][] = (int) $value;
                    }
                }
            }
        }

        $labelsByField = [];
        foreach ($idsByField as $field => $ids) {
            $config = $relationshipFields[$field];
            $labelsByField[$field] = $config['model']::query()
                ->whereIn('id', array_values(array_unique($ids)))
                ->pluck($config['label'], 'id')
                ->all();
        }

        return $logs->map(function ($log) use ($labelsByField) {
            $displayChanges = [];

            foreach (($log->changes ?? []) as $field => $change) {
                $displayChanges[$field] = [
                    'old' => $change['old'] ?? null,
                    'new' => $change['new'] ?? null,
                    'display_old' => $this->resolveIncidentLogDisplayValue($field, $change['old'] ?? null, $labelsByField),
                    'display_new' => $this->resolveIncidentLogDisplayValue($field, $change['new'] ?? null, $labelsByField),
                ];
            }

            $log->setAttribute('display_changes', $displayChanges);

            return $log;
        });
    }

    private function resolveIncidentLogDisplayValue(string $field, mixed $value, array $labelsByField): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (!isset($labelsByField[$field])) {
            return $value;
        }

        if (is_array($value)) {
            return collect($value)
                ->map(fn ($id) => $labelsByField[$field][(int) $id] ?? $id)
                ->values()
                ->all();
        }

        return $labelsByField[$field][(int) $value] ?? $value;
    }

    private function incidentStatusNames($fallbackStatuses)
    {
        $statuses = $this->incidentStatusList()->pluck('name')->values();

        return $statuses->isNotEmpty() ? $statuses : $fallbackStatuses;
    }

    private function nextSortOrder($query): int
    {
        $maxSort = (clone $query)->max('sort_order');

        return is_numeric($maxSort) ? ((int) $maxSort + 1) : 1;
    }

    private function persistSortOrder(string $modelClass, array $ids): void
    {
        DB::transaction(function () use ($modelClass, $ids) {
            foreach (array_values($ids) as $index => $id) {
                $modelClass::where('id', $id)->update([
                    'sort_order' => $index + 1,
                ]);
            }
        });
    }

    private function incidentListQuery(User $user)
    {
        $isPM = $user->position_id == 6;
        $isBoss = $user->position_id && $user->position_id < 6;
        $isAdmin = in_array($user->id, [610, 608], true);
        $query = $this->incidentQuery();

        if (!$isBoss && !$isAdmin) {
            if (!$isPM) {
                return $query->whereRaw('1 = 0');
            }

            $query->whereHas('projectRecord.manager', function ($managerQuery) use ($user) {
                $managerQuery->where('users.id', $user->id);
            });
        }

        return $query;
    }

    private function applyIncidentFilters($query, Request $request): void
    {
        $keyword = trim((string) $request->input('keyword', ''));

        if ($keyword !== '') {
            $query->where(function ($keywordQuery) use ($keyword) {
                $keywordQuery->where('title', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%")
                    ->orWhere('reason', 'like', "%{$keyword}%")
                    ->orWhere('prevention', 'like', "%{$keyword}%")
                    ->orWhere('prevention_apply_status', 'like', "%{$keyword}%")
                    ->orWhere('instruction', 'like', "%{$keyword}%")
                    ->orWhere('resolution', 'like', "%{$keyword}%")
                    ->orWhere('occured_location', 'like', "%{$keyword}%")
                    ->orWhere('memo', 'like', "%{$keyword}%")
                    ->orWhere('aftermath_comment', 'like', "%{$keyword}%")
                    ->orWhere('related_parties', 'like', "%{$keyword}%")
                    ->orWhere('payee', 'like', "%{$keyword}%")
                    ->orWhere('expense_details', 'like', "%{$keyword}%")
                    ->orWhere('status', 'like', "%{$keyword}%")
                    ->orWhereHas('causedByUser', function ($userQuery) use ($keyword) {
                        $userQuery->where('name', 'like', "%{$keyword}%");
                    })
                    ->orWhereHas('reportedByUser', function ($userQuery) use ($keyword) {
                        $userQuery->where('name', 'like', "%{$keyword}%");
                    })
                    ->orWhereHas('projectRecord', function ($projectQuery) use ($keyword) {
                        $projectQuery->where('name', 'like', "%{$keyword}%");
                    })
                    ->orWhereHas('category', function ($categoryQuery) use ($keyword) {
                        $categoryQuery->where('name', 'like', "%{$keyword}%");
                    });
            });
        }

        $this->applyWhereInFilter($query, 'caused_by', $request->input('caused_by'));
        $this->applyWhereInFilter($query, 'reported_by', $request->input('reported_by'));
        $this->applyWhereInFilter($query, 'project_record_id', $request->input('project_record_id'));
        $this->applyWhereInFilter($query, 'incident_category_id', $request->input('incident_category_id'));
        $this->applyWhereInFilter($query, 'status', $request->input('status'));

        if ($request->filled('occurred_from')) {
            $query->whereDate('occurred_date', '>=', $request->input('occurred_from'));
        }

        if ($request->filled('occurred_to')) {
            $query->whereDate('occurred_date', '<=', $request->input('occurred_to'));
        }

        if ($request->filled('point_value')) {
            $operators = [
                'gt' => '>',
                'gte' => '>=',
                'eq' => '=',
                'lte' => '<=',
                'lt' => '<',
            ];
            $operator = $operators[$request->input('point_operator', 'gte')] ?? '>=';

            $query->whereRaw(
                "(COALESCE(risk_level, 0) * COALESCE(severity_level, 0)) {$operator} ?",
                [(int) $request->input('point_value')]
            );
        }
    }

    private function applyWhereInFilter($query, string $column, mixed $values): void
    {
        $values = collect(is_array($values) ? $values : ($values !== null && $values !== '' ? [$values] : []))
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->values();

        if ($values->isNotEmpty()) {
            $query->whereIn($column, $values->all());
        }
    }

    private function orderIncidentList($query)
    {
        return $query->orderByDesc('created_at');
    }
    public function systemUpdates() {
        $activeUser = $this->active_user();
        $target_positions = [
            6, //執行役員,
            16, //プロジェクトリーダー,
            11, //正社員,
            12 //契約社員
        ];
        $userLeaves = UserLeaveRecord::where('user_id', $activeUser->id)->where('active', 1)->exists();
        if (!in_array($activeUser->position_id, $target_positions, true) || $userLeaves) {
            return [];
        }
        $otherUserLeaves = UserLeaveRecord::where('user_id', $activeUser->id)->whereNotNull('leave_start')->whereNotNull('leave_end')->get();
        $thresholdDate = Carbon::parse('2026-05-01');
        $count = SystemUpdateRecord::
        where(function ($query) use ($otherUserLeaves) {
            foreach ($otherUserLeaves as $leave) {
                $query->whereNotBetween('created_at', [$leave->leave_start, $leave->leave_end]);
            }
        })
        ->where('must_read', true)
        ->where('created_at', '>=', $thresholdDate)
        ->whereDoesntHave('systemUpdateChecks', function ($query) use ($activeUser) {
            $query->where('user_id', $activeUser->id);
        })->pluck('id')->toArray();
        return $count;
    }

}
