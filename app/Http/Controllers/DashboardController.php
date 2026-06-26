<?php

namespace App\Http\Controllers;

use App\Ai\Agents\IncidentAdvisor;
use App\Ai\Agents\IncidentConclusionAdvisor;
use App\Enums\ApplicationStatus;
use App\Exports\IncidentData;
use Illuminate\Http\Request;
use App\Services\BadgeService;
use App\Services\ReminderMessageService;
use App\Services\RemindTaskService;
use App\Services\IncidentService;
use App\Services\PaidLeaveLedgerService;
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
use App\Models\timecardRecord;
use App\Models\TimecardProjectSegment;
use App\Models\shiftRecord;
use App\Models\ShiftOvertimeRequest;
use App\Models\attendanceRecord;
use App\Models\NoticeRecord;
use App\Models\EmergencyContact;
use App\Models\Incident;
use App\Models\IncidentAdvice;
use App\Models\IncidentAssignee;
use App\Models\IncidentCategory;
use App\Models\IncidentPunishment;
use App\Models\IncidentReport;
use App\Models\IncidentStatus;
use App\Models\FileRecord;
use App\Models\UserReadHistory;
use App\Models\SystemUpdateCheck;
use App\Models\SystemUpdateRecord;
use App\Models\UserLeaveRecord;
use App\Models\EmployeeChangeApplication;
use App\Models\PlannedLeaveChangeRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Responses\StreamedAgentResponse;
use Maatwebsite\Excel\Facades\Excel;


class DashboardController extends Controller
{
    private const TIMESHEET_ADMIN_IDS = [608, 610];

    public function __construct(
        protected BadgeService $badgeService,
        protected ReminderMessageService $reminderMessageService,
        protected RemindTaskService $remindTaskService,
        protected IncidentService $incidentService,
        protected PaidLeaveLedgerService $paidLeaveLedger,
    ){

    }
    private function active_user(){
        return Auth::user();
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
            ->where('status', 0)
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
        $autoApprovedTimesheets = $this->autoApprovedDailyReports();
        $departuresReportUsers = $this->departuresReportUsers();
        $pendingPlannedLeaves = $this->pendingPlannedLeaves();
        $pendingAttendance = $this->pendingAttendance();
        $pendingPlannedLeaveChangeRequests = $this->pendingPlannedLeaveChangeRequests();
        return [
            "pendingTimesheets" => $pendingTimesheets,
            "autoApprovedTimesheets" => $autoApprovedTimesheets,
            "departuresReportUsers" => $departuresReportUsers,
            "pendingPlannedLeaves" => $pendingPlannedLeaves,
            "pendingAttendance" => $pendingAttendance,
            "pendingPlannedLeaveChangeRequests" => $pendingPlannedLeaveChangeRequests,
        ];
    }
    public function pendingPlannedLeaveChangeRequests(){
        $user = $this->active_user();
        $requests = PlannedLeaveChangeRequest::where('status', 'pending')
            ->when(in_array($user->id, [608, 610], true), function ($q) {
                return $q->where(function ($query) {
                    $query->where('pm_approval_required', false)
                        ->orWhereNotNull('pm_id');
                });
            }, function ($q) use ($user) {
                return $q
                    ->where('pm_approval_required', true)
                    ->whereNull('pm_id')
                    ->whereHas('project_record.manager', function($q2) use ($user){
                        $q2->where('users.id', $user->id);
                    });
            })
            ->with(['user', 'project_record:id,name', 'pmApprover:id,name'])
            ->get();
        return $requests;
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
        [$active_user, $isTimesheetAdmin, $target_users, $workGroupIds] = $this->timesheetApprovalScope();
        $list = [];
        $today = $date->copy()->format('Y-m-d');

        if (empty($target_users) || (!$isTimesheetAdmin && empty($workGroupIds))) {
            return [];
        }

        $timecardRows = timecardRecord::query()
            ->join('timecard_project_segments', 'timecard_project_segments.timecard_record_id', '=', 'timecard_records.id')
            ->whereNull('timecard_records.deleted_at')
            ->whereIn('timecard_records.user_id', $target_users)
            ->where('timecard_records.day', '>=', $prev_month_start)
            ->where('timecard_records.status_flag', timecardRecord::STATUS_SUBMITTED)
            ->where('timecard_project_segments.status', TimecardProjectSegment::STATUS_SUBMITTED)
            ->when(!$isTimesheetAdmin, fn ($query) => $query->whereIn('timecard_project_segments.project_id', $workGroupIds))
            ->selectRaw('MONTH(timecard_records.day) as month, COUNT(DISTINCT timecard_records.id) as count, timecard_records.user_id')
            ->groupByRaw('MONTH(timecard_records.day), timecard_records.user_id')
            ->get();

        $legacyTimecardRows = timecardRecord::query()
            ->whereDoesntHave('project_segments')
            ->whereIn('user_id', $target_users)
            ->where('day', '>=', $prev_month_start)
            ->where('status_flag', timecardRecord::STATUS_SUBMITTED)
            ->when(!$isTimesheetAdmin, fn ($query) => $query->whereIn('work_group_id', $workGroupIds))
            ->selectRaw('MONTH(day) as month, COUNT(*) as count, user_id')
            ->groupByRaw('MONTH(day), user_id')
            ->get();

        $timecardRows = $timecardRows
            ->concat($legacyTimecardRows)
            ->groupBy(fn ($row) => $row->user_id . '-' . $row->month)
            ->map(function ($rows) {
                $first = $rows->first();
                return (object) [
                    'month' => (int) $first->month,
                    'count' => (int) $rows->sum('count'),
                    'user_id' => (int) $first->user_id,
                ];
            })
            ->values()
            ->groupBy('user_id');

        $hasPendingTimecards = timecardRecord::query()
            ->join('timecard_project_segments', 'timecard_project_segments.timecard_record_id', '=', 'timecard_records.id')
            ->whereNull('timecard_records.deleted_at')
            ->whereIn('timecard_records.user_id', $target_users)
            ->where('timecard_records.day', '>=', $prev_month_start)
            ->where('timecard_records.day', '<', $today)
            ->where('timecard_records.status_flag', timecardRecord::STATUS_SUBMITTED)
            ->where('timecard_project_segments.status', TimecardProjectSegment::STATUS_SUBMITTED)
            ->when(!$isTimesheetAdmin, fn ($query) => $query->whereIn('timecard_project_segments.project_id', $workGroupIds))
            ->select('timecard_records.user_id')
            ->distinct()
            ->pluck('timecard_records.user_id')
            ->map(fn ($id) => (int) $id);

        $legacyPendingTimecards = timecardRecord::query()
            ->whereDoesntHave('project_segments')
            ->whereIn('user_id', $target_users)
            ->where('day', '>=', $prev_month_start)
            ->where('day', '<', $today)
            ->where('status_flag', timecardRecord::STATUS_SUBMITTED)
            ->when(!$isTimesheetAdmin, fn ($query) => $query->whereIn('work_group_id', $workGroupIds))
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id);

        $hasPendingTimecards = $hasPendingTimecards
            ->concat($legacyPendingTimecards)
            ->unique()
            ->values();

        $overtimeRequests = ShiftOvertimeRequest::query()
            ->leftJoin('shift_records', 'shift_records.id', '=', 'shift_overtime_requests.record_id')
            ->whereNull('shift_overtime_requests.deleted_at')
            ->whereIn('shift_overtime_requests.user_id', $target_users)
            ->whereYear('shift_overtime_requests.overtime_day', $year)
            ->whereMonth('shift_overtime_requests.overtime_day', $month)
            ->select([
                'shift_overtime_requests.id',
                'shift_overtime_requests.user_id',
                'shift_overtime_requests.status',
                'shift_overtime_requests.project_segments',
                'shift_records.department_id as shift_department_id',
            ])
            ->get()
            ->filter(function ($request) use ($workGroupIds, $isTimesheetAdmin) {
                $segments = is_array($request->project_segments)
                    ? $request->project_segments
                    : (is_string($request->project_segments) ? json_decode($request->project_segments, true) : []);
                if (!empty($segments)) {
                    return collect($segments)->contains(function ($segment) use ($workGroupIds, $isTimesheetAdmin) {
                        return (int) ($segment['status'] ?? 1) === 1
                            && ($isTimesheetAdmin || in_array((int) ($segment['project_id'] ?? 0), $workGroupIds, true));
                    });
                }

                return (int) $request->status === 1
                    && ($isTimesheetAdmin || in_array((int) $request->shift_department_id, $workGroupIds, true));
            })
            ->groupBy('user_id')
            ->map->count();

        $shiftRows = shiftRecord::query()
            ->whereIn('user_id', $target_users)
            ->whereYear('shift_day', $year)
            ->where('status_flag', 2)
            ->when(!$isTimesheetAdmin, fn ($query) => $query->whereIn('department_id', $workGroupIds))
            ->where(function ($innerQuery) use ($month, $prev_month, $shift_month) {
                $innerQuery->whereMonth('shift_day', $month)
                    ->orWhereMonth('shift_day', $prev_month)
                    ->orWhereMonth('shift_day', $shift_month);
            })
            ->selectRaw('MONTH(shift_day) as month, COUNT(*) as count, user_id')
            ->groupByRaw('MONTH(shift_day), user_id')
            ->get()
            ->groupBy('user_id');

        $usersWithRequests = collect($target_users)
            ->filter(function ($userId) use ($timecardRows, $overtimeRequests, $shiftRows, $hasPendingTimecards) {
                return $hasPendingTimecards->contains((int) $userId)
                    || $timecardRows->has($userId)
                    || $overtimeRequests->has($userId)
                    || $shiftRows->has($userId);
            })
            ->values()
            ->all();

        $user_list = User::whereIn('id', $usersWithRequests)
                        ->select('id', 'name', 'icon_path', 'icon_bg')
                        ->get();
        foreach($user_list as $user){
            $timeCardsCount = $timecardRows->get($user->id, collect())->values();
            $overtimeRequestsCount = (int) ($overtimeRequests->get($user->id) ?? 0);
            $shiftCount = $shiftRows->get($user->id, collect())->values();
            $hasPendingTimecardsForUser = $hasPendingTimecards->contains((int) $user->id);

             $d = [
                "user" => $user,
                "timecard" => $timeCardsCount,
                "has_pending_timecards" => $hasPendingTimecardsForUser,
                "overtime" => $overtimeRequestsCount,
                "shift" => $shiftCount,
            ];
            if($hasPendingTimecardsForUser || $timeCardsCount->count() || $overtimeRequestsCount || $shiftCount->count()){
                $list[] = $d;
            }

        }
        // $data = [
        //     "remind_timesheet" => $list
        // ];
        // return response()->json($data);
        return $list;
    }

    public function markAutoApprovedDailyReportsRead(Request $request)
    {
        $validated = $request->validate([
            'segment_ids' => ['required', 'array'],
            'segment_ids.*' => ['integer'],
        ]);

        [$activeUser, $isTimesheetAdmin, $targetUsers, $workGroupIds] = $this->timesheetApprovalScope();
        $segmentIds = collect($validated['segment_ids'])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($segmentIds->isEmpty() || empty($targetUsers) || (!$isTimesheetAdmin && empty($workGroupIds))) {
            return response()->json(['read_segment_ids' => []]);
        }

        $visibleSegmentIds = TimecardProjectSegment::query()
            ->join('timecard_records', 'timecard_records.id', '=', 'timecard_project_segments.timecard_record_id')
            ->whereNull('timecard_records.deleted_at')
            ->whereIn('timecard_project_segments.id', $segmentIds)
            ->whereIn('timecard_records.user_id', $targetUsers)
            ->where('timecard_records.status_flag', timecardRecord::STATUS_APPROVED)
            ->where('timecard_project_segments.status', TimecardProjectSegment::STATUS_APPROVED)
            ->where('timecard_project_segments.segment_type', TimecardProjectSegment::TYPE_WORK)
            ->where('timecard_project_segments.approval_source', TimecardProjectSegment::APPROVAL_SOURCE_AUTO)
            ->whereNull('timecard_project_segments.approved_by')
            ->whereNotNull('timecard_project_segments.approved_at')
            ->when(!$isTimesheetAdmin, fn ($query) => $query->whereIn('timecard_project_segments.project_id', $workGroupIds))
            ->pluck('timecard_project_segments.id');

        $visibleSegmentIds->each(function ($segmentId) use ($activeUser) {
            UserReadHistory::updateOrCreate(
                [
                    'readable_type' => TimecardProjectSegment::class,
                    'readable_id' => (int) $segmentId,
                    'user_id' => $activeUser->id,
                ],
                [
                    'last_read_at' => now(),
                ],
            );
        });

        return response()->json(['read_segment_ids' => $visibleSegmentIds->values()]);
    }

    private function autoApprovedDailyReports(): array
    {
        $date = Carbon::now();
        $prevMonthStart = $date->clone()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d');
        [$activeUser, $isTimesheetAdmin, $targetUsers, $workGroupIds] = $this->timesheetApprovalScope();

        if (empty($targetUsers) || (!$isTimesheetAdmin && empty($workGroupIds))) {
            return [];
        }

        $rows = TimecardProjectSegment::query()
            ->join('timecard_records', 'timecard_records.id', '=', 'timecard_project_segments.timecard_record_id')
            ->join('users', 'users.id', '=', 'timecard_records.user_id')
            ->join('project_records', 'project_records.id', '=', 'timecard_project_segments.project_id')
            ->leftJoin('custom_field_data_records as weather', function ($join) {
                $join->on('weather.user_id', '=', 'timecard_records.user_id')
                    ->on('weather.date', '=', 'timecard_records.day')
                    ->where('weather.type_id', 43)
                    ->whereNull('weather.deleted_at');
            })
            ->leftJoin('user_read_histories', function ($join) use ($activeUser) {
                $join->on('user_read_histories.readable_id', '=', 'timecard_project_segments.id')
                    ->where('user_read_histories.readable_type', TimecardProjectSegment::class)
                    ->where('user_read_histories.user_id', $activeUser->id);
            })
            ->whereNull('timecard_records.deleted_at')
            ->whereNull('user_read_histories.id')
            ->whereIn('timecard_records.user_id', $targetUsers)
            ->where('timecard_records.day', '>=', $prevMonthStart)
            ->where('timecard_records.status_flag', timecardRecord::STATUS_APPROVED)
            ->where('timecard_project_segments.status', TimecardProjectSegment::STATUS_APPROVED)
            ->where('timecard_project_segments.segment_type', TimecardProjectSegment::TYPE_WORK)
            ->where('timecard_project_segments.approval_source', TimecardProjectSegment::APPROVAL_SOURCE_AUTO)
            ->whereNull('timecard_project_segments.approved_by')
            ->whereNotNull('timecard_project_segments.approved_at')
            ->when(!$isTimesheetAdmin, fn ($query) => $query->whereIn('timecard_project_segments.project_id', $workGroupIds))
            ->orderByDesc('timecard_project_segments.approved_at')
            ->orderByDesc('timecard_records.day')
            ->select([
                'timecard_project_segments.id as segment_id',
                'timecard_project_segments.timecard_record_id',
                'timecard_project_segments.project_id',
                'timecard_project_segments.start_time',
                'timecard_project_segments.end_time',
                'timecard_project_segments.comment',
                'timecard_project_segments.approved_at',
                'timecard_records.day',
                'timecard_records.user_id',
                'users.name as user_name',
                'users.icon_path as user_icon_path',
                'users.icon_bg as user_icon_bg',
                'project_records.name as project_name',
                'weather.value_int as weather',
            ])
            ->get();

        return $rows
            ->groupBy('user_id')
            ->map(function ($userRows) {
                $first = $userRows->first();

                return [
                    'user' => [
                        'id' => (int) $first->user_id,
                        'name' => $first->user_name,
                        'icon_path' => $first->user_icon_path,
                        'icon_bg' => $first->user_icon_bg,
                    ],
                    'read' => false,
                    'records' => $userRows->map(fn ($row) => [
                        'segment_id' => (int) $row->segment_id,
                        'timecard_record_id' => (int) $row->timecard_record_id,
                        'project_id' => (int) $row->project_id,
                        'project_name' => $row->project_name,
                        'day' => $row->day,
                        'start_time' => $row->start_time,
                        'end_time' => $row->end_time,
                        'comment' => $row->comment,
                        'weather' => $row->weather === null ? null : (int) $row->weather,
                        'approved_at' => $row->approved_at,
                    ])->values(),
                ];
            })
            ->values()
            ->all();
    }

    private function timesheetApprovalScope(): array
    {
        $activeUser = $this->active_user();
        $targetUsers = [];
        $workGroupIds = [];
        $headquartersIds = [];
        $hqProject = ProjectRecord::where('id', 20)->first();
        $isTimesheetAdmin = in_array((int) $activeUser->id, self::TIMESHEET_ADMIN_IDS, true);

        if($hqProject){
            $headquartersIds = $hqProject->members()->pluck('users.id')->toArray();
        }
        if($isTimesheetAdmin){
            $targetUsers = User::where('retire', 0)
                ->where('partner_flag', 0)
                ->where('deleted_flag', 0)
                ->where('on_leave', 0)
                ->where(function ($query) use ($headquartersIds) {
                    $query->where('position_id', 6)
                        ->orWhereIn('id', $headquartersIds);
                })
                ->pluck('id')
                ->toArray();
            $workGroupIds = ProjectRecord::pluck('id')->unique()->values()->toArray();
        }
        if($activeUser->position_id == 6){
            $workGroups = ProjectRecord::whereHas('manager', function ($q) use($activeUser) {
                $q->where('users.id', $activeUser->id)->whereNotIn('users.id', self::TIMESHEET_ADMIN_IDS);
            })->with('members')->get();

            $workGroupIds = $workGroups->unique()->pluck('id')->toArray();

            $projectMembers = $workGroups->flatMap(function ($workGroup) {
                return $workGroup->members;
            })->unique('id')->values()->pluck('id')->toArray();
            $targetUsers = array_merge($targetUsers, $projectMembers);
        }

        $targetUsers = collect($targetUsers)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0 && $id !== (int) $activeUser->id)
            ->unique()
            ->values()
            ->all();

        return [$activeUser, $isTimesheetAdmin, $targetUsers, $workGroupIds];
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
            ->where('date_start', '<=', $now->copy()->endOfDay())
            ->where('date_end', '>=', $now->copy()->startOfDay())
            ->get();

        $updateNeed = (clone $challengesQuery)
            ->whereIn('status_flag', [0, 5])
            ->where('date_end', '<', $now->copy()->startOfDay())
            ->get();
        $data = $progressNeed->map(function ($challenge) use ($now) {
            $start = Carbon::parse($challenge->date_start)->startOfDay();
            $end = Carbon::parse($challenge->date_end)->endOfDay();

            if (!$start->isValid() || !$end->isValid() || $end->lte($start)) {
                return null;
            }

            if ($now->lt($start) || $now->gt($end)) {
                return null;
            }

            $checkpoint = $this->latestReachedProgressCheckpoint($start, $end, $now);

            if (!$checkpoint) {
                return null;
            }

            $elapsed = $start->diffInSeconds($now);
            $total = max(1, $start->diffInSeconds($end));

            $pct = (int) round(($elapsed / $total) * 100);
            $pct = max(0, min(100, $pct));

            $checkpointDate = $this->progressCheckpointDate($start, $end, $checkpoint);

            $latestProgressReport = optional($challenge->progressReports)
                ->sortByDesc('created_at')
                ->first();

            if (
                $latestProgressReport &&
                Carbon::parse($latestProgressReport->created_at)->gte($checkpointDate)
            ) {
                return null;
            }

            $challenge['attention_type'] = 'progress_need';
            $challenge['attention_checkpoint'] = $checkpoint;
            $challenge['attention_progress_percent'] = $pct;
            $challenge['attention_deadline'] = $end->toIso8601String();

            return $challenge;
        })->filter()->values();

        $updateNeed->each(function ($challenge) {
            $end = Carbon::parse($challenge->date_end)->endOfDay();

            $challenge['attention_type'] = 'update_need';
            $challenge['attention_deadline'] = $end->toIso8601String();
        });

        $final = $data
            ->concat($updateNeed)
            ->sortBy('date_start')
            ->values();

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
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();

        $totalSeconds = max(1, $start->diffInSeconds($end));
        $checkpointSeconds = (int) round($totalSeconds * ($checkpoint / 100));

        return $start->copy()->addSeconds($checkpointSeconds);
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
        $active_user = $this->active_user();
        if ($active_user->position_id < 6 || $active_user->position_id === 14) return [];

        $notificationUser = User::select('name', 'id', 'icon_path', 'icon_bg')->findOrFail(610);

        return $this->paidLeaveLedger
            ->plannedLeaveReminderPeriodsForUser($active_user)
            ->map(function (array $item) use ($notificationUser) {
                $item['tempData']['notification_user'] = $notificationUser;

                return $item;
            })
            ->values()
            ->all();
    }
    public function pendingGoalsUserForHR() {
        $user = Auth::user();
        if ($user->canHrApprove()) {
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
        $changeRequests = EmployeeChangeApplication::where('status', ApplicationStatus::Submitted->value)
            ->with([
                'user:id,name,icon_path,icon_bg,position_id',
            ])
            ->get();
        return [
            'pendingEvaluations' => $evaluations,
            'pendingAssignments' => $assigns,
            'pendingChangeRequests' => $changeRequests,
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
    private function incidents() {
        $activeUser = $this->active_user();

        $query = $this->incidentService->incidentQuery(true, $activeUser->id)
            ->with([
                'readHistories' => function ($readQuery) use ($activeUser) {
                    $readQuery->where('user_id', $activeUser->id);
                },
            ])
            ->withCount([
                'logs as unread_update_logs_count' => function ($logQuery) use ($activeUser) {
                    $logQuery
                        ->where(function ($ownerQuery) use ($activeUser) {
                            $ownerQuery->whereNull('update_logs.user_id')
                                ->orWhere('update_logs.user_id', '!=', $activeUser->id);
                        })
                        ->whereRaw(
                            'update_logs.created_at > COALESCE((
                            SELECT user_read_histories.last_read_at
                            FROM user_read_histories
                            WHERE user_read_histories.readable_type = ?
                                AND user_read_histories.readable_id = update_logs.loggable_id
                                AND user_read_histories.user_id = ?
                            LIMIT 1
                        ), ?)',
                            [Incident::class, $activeUser->id, '1970-01-01 00:00:00'],
                        );
                },
            ])
            ->where(function ($statusQuery) {
                $statusQuery->whereNull('status')
                    ->orWhere('status', '!=', '完了');
            });
        $isPM = $activeUser->position_id == 6;
        $isBoss = $activeUser->position_id && $activeUser->position_id < 6;
        $isAdmin = in_array($activeUser->id, [610, 608], true);
        $emergencyContacts = collect();

        if (!$isBoss && !$isAdmin) {
            $query->where(function ($scopeQuery) use ($activeUser, $isPM) {
                $scopeQuery
                    ->where('caused_by', $activeUser->id)
                    ->orWhere('reported_by', $activeUser->id);

                $this->incidentService->orWhereActiveIncidentAssignee($scopeQuery, $activeUser);

                if ($isPM) {
                    $scopeQuery->orWhereHas('projectRecord.manager', function ($managerQuery) use ($activeUser) {
                        $managerQuery->where('users.id', $activeUser->id);
                    });
                }
            });
        } else {
            $emergencyContacts = EmergencyContact::query()
                ->with([
                    'user' => fn ($userQuery) => $userQuery->select('id', 'name', 'icon_path', 'icon_bg'),
                ])
                ->withCount('actions')
                ->where(function ($contactQuery) {
                    $contactQuery->whereNull('status')
                        ->orWhere('status', '!=', EmergencyContact::STATUS_COMPLETE);
                })
                ->orderByDesc('created_at')
                ->get();
        }
        return [
            'emergency_contacts' => $emergencyContacts,
            'attention' => $query->orderByDesc('created_at')->get(),
        ];
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
