<?php

namespace App\Http\Controllers;
use App\Models\ProjectRecord;
use App\Models\timecardBreakRecord;
use App\Models\timecardIncentive;
use App\Models\User;

use App\Models\shiftType;
use App\Models\shiftRecord;

use App\Models\timecardRecord;
use App\Models\timecardCostRecord;
use App\Models\customFieldDataRecord;
use App\Models\customFieldPartsRecord;
use App\Models\timecardVehicle;
use App\Models\workGroup;
use App\Models\workTemp;
use App\Models\attendanceRecord;
use App\Models\ShiftOvertimeRequest;
use App\Models\ProjectCase;
use App\Models\TimecardProjectSegment;
use App\Models\TimecardCostOcrRun;
use App\Models\PlannedLeaveChangeRequest;
use App\Services\SharedService;
use App\Services\TimeSheet\TimecardAuditLogService;
use App\Services\TimeSheet\TimecardReceiptStorageService;
use App\Services\TimeSheet\WorkReceiptOcrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Infrastructure\Kintone\KintoneClient;
use App\Services\TimeSheet\AutoAttendanceConfirm;
use App\Services\TimeSheet\ShiftService;
use App\Services\TimeSheet\WorkReportTimeService;
use App\Services\PaidLeaveLedgerService;
class WorkController extends Controller
{
    protected $sharedService;
    public function __construct(
        SharedService $sharedService, 
        private readonly KintoneClient $kintone,
        private readonly AutoAttendanceConfirm $attendanceService,
        private readonly TimecardAuditLogService $timecardAuditLogService,
        private readonly TimecardReceiptStorageService $timecardReceiptStorageService,
        private readonly WorkReceiptOcrService $workReceiptOcrService,
        private readonly ShiftService $shiftService,
        private readonly WorkReportTimeService $workReportTimeService,
        private readonly PaidLeaveLedgerService $paidLeaveLedger
    ) {
        $this->sharedService = $sharedService;
    }
    private function active_user(){
        $sub = Auth::user()->linked()->where('main_id', Auth::id())->wherePivot('active', 1)->first();
        if($sub){
            return $sub;
        }else{
            return Auth::user();
        }
    }
    private function canExportWorkCsv(User $user): bool
    {
        return $user->isAdmin() || (int) $user->position_id === 6;
    }
    //
    public function index(Request $request){
        
    }
    public function getWorkData(Request $request) {
        $active_user = $this->active_user();
        
        $users_list = $request->work_group ?? [];
        $current_date = $request->current_date ?? Carbon::now()->format('Y-m');
        [$currentYear, $currentMonth] = explode('-', $current_date);

        $time_card_record = timecardRecord::selectRaw(
            'user_id,
            SUM(over_time) as total_over_time,
            SUM(work_time) as total_work_time,
            SUM(car_mileage) as total_car_mileage'
        )
        ->whereYear('day', $currentYear)
        ->whereMonth('day', $currentMonth)
        ->whereIn('user_id', $users_list)
        ->where('deleted_flag', 0)
        ->groupBy('user_id')
        ->orderBy('user_id')
        ->get();

        $month_over_time = $time_card_record->pluck('total_over_time', 'user_id');

        $month_work_time = $time_card_record->pluck('total_work_time', 'user_id');

        $month_mileage = $time_card_record->pluck('total_car_mileage', 'user_id');

        $month_training_minutes = TimecardProjectSegment::query()
            ->join('timecard_records', 'timecard_project_segments.timecard_record_id', '=', 'timecard_records.id')
            ->selectRaw('timecard_records.user_id as user_id, SUM(timecard_project_segments.minutes) as total_training_minutes')
            ->whereYear('timecard_records.day', $currentYear)
            ->whereMonth('timecard_records.day', $currentMonth)
            ->whereIn('timecard_records.user_id', $users_list)
            ->where('timecard_records.deleted_flag', 0)
            ->where('timecard_project_segments.segment_type', TimecardProjectSegment::TYPE_TRAINING)
            ->groupBy('timecard_records.user_id')
            ->pluck('total_training_minutes', 'user_id');

    
        $user_record = User::whereIn('id', $users_list)
                            ->select('name', 'id', 'work_type', 'work_time_day', 'work_authority', 'icon_path', 'icon_bg', 'position_id', 'user_code')
                            ->get();

        $custom_weather_data = customFieldDataRecord::selectRaw(
            'user_id,
            value_int,
            COUNT(*) as count'
        )
        ->whereIn('user_id', $users_list)
        ->whereYear('date', $currentYear)
        ->whereMonth('date', $currentMonth)
        ->where('type_id', 43)
        ->groupBy('user_id', 'value_int')
        ->orderBy('user_id')
        ->orderBy('count', 'desc')
        ->get();
        $custom_achievement_data = customFieldDataRecord::selectRaw(
            'user_id,
            label,
            COUNT(*) as count'
        )
        ->whereIn('user_id', $users_list)
        ->whereYear('date', $currentYear)
        ->whereMonth('date', $currentMonth)
        ->where('type_id', 41)
        ->groupBy('user_id', 'label')
        ->orderBy('user_id')
        ->orderBy('count', 'desc')
        ->get();

        $mostCommonAchievementPerUser = $custom_achievement_data->groupBy('user_id')->map(function ($userRecords) {
            return $userRecords->first()->label;
        });

        $mostCommonWeatherPerUser = $custom_weather_data->groupBy('user_id')->map(function ($userRecords) {
            return $userRecords->first()->value_int;
        });

        $shift_record = shiftRecord::selectRaw(
            'user_id,
            SUM(CASE WHEN shift_types.full_day = 0 THEN shift_types.value ELSE 0 END) as total_shift_value,
            SUM(CASE WHEN shift_types.full_day = 2 THEN 1 ELSE 0 END) as full_day_count,
            SUM(CASE WHEN shift_types.full_day = 1 THEN 1 ELSE 0 END) as half_day_count'
        )
            ->join('shift_types', 'shift_records.shift_type', '=', 'shift_types.id')
            ->whereYear('shift_day', $currentYear)
            ->whereMonth('shift_day', $currentMonth)
            ->whereIn('user_id', $users_list)
            ->whereNotIn('shift_type', [18])
            ->groupBy('user_id')
            ->orderBy('user_id')
            ->get();              
        $annual_leave = $shift_record->pluck('total_shift_value', 'user_id')->map(fn($value) => $value ?? 0);
        $annual_full = $shift_record->pluck('full_day_count', 'user_id')->map(fn($value) => $value ?? 0);
        $annual_half = $shift_record->pluck('half_day_count', 'user_id')->map(fn($value) => $value ?? 0);
        $attendance_flag = attendanceRecord::where('date_year_month', $request->current_date)
                            ->whereIn('user_id', $users_list)
                            ->exists();
        $month_average_data = [];
        $lastDay = Carbon::create($currentYear, $currentMonth, 1)->endOfMonth()->day;

        $annual_costs = timecardCostRecord::selectRaw(
            'user_id,
            SUM(expenses) as total_expenses'
        )
        ->whereIn('user_id', $users_list)
        ->where('date_month', $request->current_date)
        ->groupBy('user_id')
        ->orderBy('user_id')
        ->get()
        ->pluck('total_expenses', 'user_id');
        $annual_incentive = timecardIncentive::selectRaw(
            'user_id,
            SUM(count) as total_incentives'
        )
        ->whereIn('user_id', $users_list)
        ->where('date_month', $request->current_date)
        ->groupBy('user_id')
        ->orderBy('user_id')
        ->get()
        ->pluck('total_incentives', 'user_id');
        $monthly_result = ProjectCase::query()
        ->selectRaw("
            project_cases.user_id,
            project_records.unit_id,
            project_records.custom_unit_label,
            SUM(project_cases.amount) as total_amount
        ")
        ->join('project_records', 'project_records.id', '=', 'project_cases.project_record_id')
        ->whereIn('project_cases.user_id', $users_list)
        ->whereYear('project_cases.report_date', $currentYear)
        ->whereMonth('project_cases.report_date', $currentMonth)
        ->whereHas('timecardRecord', function ($query) use ($currentYear, $currentMonth) {
            $query->whereYear('day', $currentYear)
                  ->whereMonth('day', $currentMonth);
        })
        ->groupBy(
            'project_cases.user_id',
            'project_records.unit_id',
            'project_records.custom_unit_label'
        )
        ->orderBy('project_cases.user_id')
        ->get()
        ->groupBy('user_id');
        $annual_calc = [];
        foreach($user_record as $user){
            
            $monthNum = (int)$currentMonth;
            $yearNum = (int)$currentYear;

            $userWorkTimeData = $this->sharedService->work_days_calculator($yearNum, $monthNum, $user);
            $workdayNum = $userWorkTimeData['days'];
            $shift_work_hours = $userWorkTimeData['work_minutes'];
            $full_shifts = $annual_full->get($user->id, 0);
            $half_shifts = $annual_half->get($user->id, 0);
            $leave_value = $annual_leave->get($user->id, 0);
            $annual_calc[$user->id] = $full_shifts * $user->work_time_day + $half_shifts * ($user->work_time_day / 2);
            $annual_leave[$user->id] = $leave_value + $annual_calc[$user->id];
            if (isset($annual_leave[$user->id])  && isset($month_work_time[$user->id])) {
                $month_work_time[$user->id] += $annual_leave[$user->id];
            }
            if ($shift_work_hours < (($month_work_time[$user->id] ?? 0) - ($month_over_time[$user->id] ?? 0))) {
                $month_over_time[$user->id] = ($month_work_time[$user->id] ?? 0) - $shift_work_hours;
            }

            $month_average_data[] = [
                'month_over_time' => (isset($month_over_time[$user->id]) && $month_over_time[$user->id] >= 0) ? $month_over_time[$user->id] : null,
                'month_work_time' => $month_work_time[$user->id] ?? null,
                'month_training_minutes' => (isset($month_training_minutes[$user->id]) && $month_training_minutes[$user->id] >= 0) ? (int) $month_training_minutes[$user->id] : null,
                'month_weather_average' => $mostCommonWeatherPerUser[$user->id] ?? null,
                'month_achievement_average' => $mostCommonAchievementPerUser[$user->id] ?? null,
                'month_should_work_time' => $shift_work_hours,
                'month_annual_leave' => $annual_leave[$user->id] ?? null,
                'month_total_costs' => $annual_costs[$user->id] ?? null,
                'month_total_incentive' => $annual_incentive[$user->id] ?? null,
                'month_total_results' => ($monthly_result[$user->id] ?? collect())
                ->map(function ($row) {
                    return [
                        'total_amount' => $row->total_amount,
                        'unit_id' => $row->unit_id,
                        'unit_label' => $row->unit_id === 'CUSTOM'
                            ? $row->custom_unit_label
                            : $row->unit_id,
                    ];
                })
                ->values(),
                'user_name' => $user->name,
                'user_id' => $user->id,
                'work_type' => $user->work_type,
                'access_csv' => $this->canExportWorkCsv($active_user),
                'shift_work_hours' => $shift_work_hours,
                'workdayNum' => $workdayNum,
                'month_mileage' => (isset($month_mileage[$user->id]) && $month_mileage[$user->id] >= 0) ? (int) $month_mileage[$user->id] : null,
            ];
        }
        $responseArray = [
            'user_data' => $user_record,
            'month_average' => $month_average_data,
            "attendance_flag" => $attendance_flag,
        ];

        return response()->json($responseArray);
    }
    public function get_shift_data_table(Request $request){
        $requestDateString = $request->current_date;
        $active_user = $this->active_user();
        $users_list = $request->work_group ?? [];
        if (($key = array_search($active_user->id, $users_list)) !== false) {
            unset($users_list[$key]);
        
            array_unshift($users_list, $active_user->id);
        }
        [$year, $month] = explode("-", $requestDateString);
        $vehicleType = $request->vehicles ?? [];
        $users = User::where(function ($query) use ($users_list, $vehicleType, $year, $month) {
            $query->whereIn('id', $users_list) // Condition 1: From $users_list
                ->orWhereHas('time_card_records', function ($q) use ($year, $month, $vehicleType) {
                    $q->whereYear('day', $year)
                    ->whereMonth('day', $month)
                    ->whereHas('vehicle_records', function ($subQuery) use ($vehicleType) {
                        $subQuery->whereIn('vehicle', $vehicleType);
                    });
                });
        })
        ->with([
            'time_card_records' => function ($q) use ($year, $month, $vehicleType) {
                $q->whereYear('day', $year)
                  ->whereMonth('day', $month);
                if (!empty($vehicleType)) {
                    $q->whereHas('vehicle_records', function ($subQuery) use ($vehicleType) {
                        $subQuery->whereIn('vehicle', $vehicleType);
                    });
                }
                $q->with([
                    'custom_field_data_records' => function ($q) {
                        $q->whereIn('type_id', [37, 40, 39, 41, 42, 44])
                        ->orderBy('created_at', 'desc')
                        ->select('id', 'table_record_id', 'type_id', 'value_text', 'value_int', 'date', 'label', 'user_id');
                    },
                    'timecard_costs' => function ($q) {
                        $q->with('file')
                        ->select(
                            'content',
                            'type',
                            'transport_type',
                            'departure_place',
                            'arrival_place',
                            'expenses',
                            'record_id',
                            'file_path',
                            'receipt_file_id',
                            'id',
                            'department',
                            'project_id',
                            'timecard_project_segment_id',
                            'draft_uuid',
                            'receipt_date',
                            'merchant_name',
                            'currency',
                            'receipt_source_type',
                            'file_original_name',
                            'file_mime_type',
                            'file_size_bytes',
                            'file_sha256',
                            'file_uploaded_at',
                            'scan_dpi',
                            'scan_color_depth',
                            'scan_color_mode',
                            'document_size',
                            'image_width_px',
                            'image_height_px'
                        );
                    },
                    'timecard_incentives' => function ($q) {
                        $q->with('file')
                        ->select('count', 'id', 'record_id');
                    },
                    'total_break_time',
                    'approver:id,name,icon_path,icon_bg',
                    'department' => function ($q) {
                        $q->select('id', 'name', 'unit_id', 'custom_unit_label');
                    },
                    'vehicle_data' => function ($q) {
                        $q->with('before_user')->with('after_user');
                    },
                    'vehicle_records' => function ($q) {
                        $q->with('before_user')->with('after_user');
                    },
                    'car_project' => function ($q) {
                        $q->select('id', 'name');
                    },
                    'project_case' => function ($q) {
                        $q->with('project:id,name,unit_id,custom_unit_label,has_actual_func,actual_statuses')
                            ->select('id', 'project_record_id', 'amount', 'status', 'timecard_record_id', 'meta');
                    },
                    'project_segments' => function ($q) {
                        $q->with([
                            'approver:id,name,icon_path,icon_bg',
                            'project' => function ($query) {
                                $query->select('id', 'name', 'unit_id', 'custom_unit_label', 'has_actual_func', 'actual_statuses')->with('manager:id,name,icon_path,icon_bg');
                            },
                        ]);
                    },
                ]);
            },
            'shift_records' => function ($q) use ($year, $month) {
                $q->whereYear('shift_day', $year)
                  ->whereMonth('shift_day', $month)
                  ->with([
                      'shiftType' => function ($query) {
                          $query->select('id', 'name', 'abbreviation', 'value');
                      },
                      'overtime_request',
                      'department',
                  ])
                  ->select('id', 'shift_day', 'shift_type', 'user_id', 'start_time', 'end_time', 'status_flag', 'department_id');
            },
            'custom_field_data_records' => function ($q) use ($year, $month) {
                $q->whereYear('date', $year)
                  ->whereMonth('date', $month)
                  ->where('type_id', 43);
            },
            'attendance_records' => function ($q) use ($requestDateString) {
                $q->where('date_year_month', $requestDateString);
            }
        ]);
        if (!empty($users_list)) {
            $users->orderByRaw("FIELD(id, " . implode(',', $users_list) . ")"); // Preserve order of $users_list
        }
        $users = $users->get();
        $lastIndex = !empty($users) ? count($users) - 1 : null;
        $adminApprovers = User::where('id', 610)->get(['id', 'name', 'icon_path', 'icon_bg'])->values();
        $recordList = [];
        $managedProjectIds = $this->managedShiftProjectIds($active_user);
        $timeCardRecords = $users->flatMap->time_card_records->groupBy('user_id');
        $shiftRecords = $users->flatMap->shift_records->groupBy('user_id')->map->keyBy('shift_day');
        $overtimeRequests = ShiftOvertimeRequest::query()
            ->whereYear('overtime_day', $year)
            ->whereMonth('overtime_day', $month)
            ->whereIn('user_id', $users_list)
            ->get()
            ->keyBy(fn (ShiftOvertimeRequest $request) => (int) $request->user_id . '|' . Carbon::parse($request->overtime_day)->format('Y-m-d'));
        $customFieldData = $users->flatMap->custom_field_data_records->groupBy('user_id')->map->keyBy('date');
        $attendanceRecords = $users->flatMap->attendance_records->groupBy('user_id')->map->keyBy('date_year_month');
        for ($day = 1; $day <= cal_days_in_month(CAL_GREGORIAN, $month, $year); $day++) {
            $date = Carbon::create($year, $month, $day);
            $targetShiftDay = $date->format('Y-m-d');
            $targetShiftMonth = $date->format('Y-m');
            foreach ($users as $index => $user) {
                $userId = $user->id;
                $attendance = $attendanceRecords[$userId][$targetShiftMonth]->id ?? false;
                $time_card = isset($timeCardRecords[$userId])
                            ? $timeCardRecords[$userId]->firstWhere('day', $targetShiftDay)
                            : null;
                $shift = $shiftRecords[$userId][$targetShiftDay] ?? null;
                $overtimeRequestForDay = $overtimeRequests->get($userId . '|' . $targetShiftDay);
                if ($shift && $overtimeRequestForDay && !$shift->overtime_request) {
                    $shift->setRelation('overtime_request', $overtimeRequestForDay);
                }
                $authority = $this->hasTimesheetManagerAuthority($active_user, $user, $time_card, $shift, $managedProjectIds);
                
                
                
                $hasProjectSegmentsForDetails = $time_card?->project_segments?->isNotEmpty() ?? false;
                $overtime_reason = $time_card && !$hasProjectSegmentsForDetails ? $time_card->custom_field_data_records->firstWhere('type_id', 42) : '';
                $comment = $time_card && !$hasProjectSegmentsForDetails ? $time_card->custom_field_data_records->firstWhere('type_id', 39) : '';
                $allowances = $time_card && !$hasProjectSegmentsForDetails ? $time_card->custom_field_data_records->where('type_id', 37)->pluck('label')->toArray() : [];
                $allowances_value = implode(" ", $allowances);
                $incident = $time_card && !$hasProjectSegmentsForDetails ? $time_card->custom_field_data_records->firstWhere('type_id', 40) : '';
                $satisfy = $time_card ? $time_card->custom_field_data_records->firstWhere('type_id', 41) : '';

                $daily_report_ability = $this->has_daily_report($shift, $time_card, $date, $user, $active_user, $attendance, $authority);
                $overtime_ability = $shift ? $this->has_overtime_access($shift, $user, $time_card, $date, $active_user, $attendance) : false;
                $approve_ability = $this->has_approve_access($shift, $time_card, $authority, $attendance, $active_user);
                $department_creation = $this->has_department_create($shift, $time_card, $date, $active_user, $attendance, $user);
                if ($hasProjectSegmentsForDetails) {
                    $time_card->project_segments->each(function (TimecardProjectSegment $segment) use ($active_user, $user, $attendance, $daily_report_ability) {
                        $segment->setAttribute('ability', $this->projectSegmentAbility($segment, $active_user, $user, $attendance, (bool) $daily_report_ability[1]));
                    });
                }
                $recordList[] = [
                    'day_full' => $date->format('Y-m-d'),
                    'day_show' => $index == 0 ? $date->format('Y-m-d') : '',
                    'user_name' => $user->name,
                    'user_id' => $userId,
                    'user_code' => $user->user_code,
                    'work_authority' => $user->work_authority,
                    'work_time_day' => $user->work_time_day,
                    'work_type' => $user->work_type,
                    'flex' => $user->work_type == 0,
                    'last' => end($users_list) == $userId || $lastIndex === $index,
                    'position_id' => $user->position_id,
                    'overtime_reason' => $overtime_reason ? $overtime_reason->value_text : '',
                    'comment' => $comment ? $comment->value_text : '',
                    'incident' => $incident ? ($incident->value_text ?? $incident->label) : '',
                    'satisfy' => $satisfy ? $satisfy->label : '',
                    'allowances' => $allowances_value,
                    'attendance' => $attendance,
                    'shift' => $shift,
                    'time_card' => $time_card,
                    'weather' => $customFieldData[$userId][$targetShiftDay]->value_int ?? null,
                    'authority' => $authority,
                    'force_authority' => $active_user->isAdmin(),
                    'admin_approvers' => $adminApprovers,
                    'total_break_time' => $time_card?->total_break_time->first()->total_break_minute ?? 0,
                    'ability' => [
                        'overtime_request' => $overtime_ability,
                        'daily_report_create' => $daily_report_ability[0],
                        'daily_report_modify' => !$hasProjectSegmentsForDetails && $daily_report_ability[1],
                        'daily_report_delete' => !$hasProjectSegmentsForDetails && $daily_report_ability[1] && !$this->hasLockedProjectSegments($time_card, $active_user),
                        'start_stamp' => $daily_report_ability[2],
                        'end_stamp' => $daily_report_ability[3],
                        'break_stamp' => $daily_report_ability[4],
                        'daily_report_approve' => !$hasProjectSegmentsForDetails && $approve_ability[0],
                        'daily_report_cancel' => !$hasProjectSegmentsForDetails && $approve_ability[1],
                        'overtime_approve' => $approve_ability[2],
                        'overtime_cancel' => $approve_ability[3],
                        // 'department_creation' => $department_creation,
                    ]
                ];
            }
        }
        
        return response()->json($recordList);
    }
    private function has_approve_access($shift, $time_card, $authority, $has_attendance, $active_user){
        $force = $active_user->isAdmin();
        $dailyReportStatus = $time_card->status_flag ?? -1;
        $overtimeRequest = $shift?->overtime_request;
        $overtimeStatus = $overtimeRequest ? $overtimeRequest->status : -1;
        $hasProjectSegments = $time_card
            ? ($time_card->relationLoaded('project_segments')
                ? $time_card->project_segments->isNotEmpty()
                : $time_card->project_segments()->exists())
            : false;
        $hasOvertimeProjectSegments = $overtimeRequest && is_array($overtimeRequest->project_segments) && count($overtimeRequest->project_segments) > 0;
        $dailyReportApproveOrDeny = !$hasProjectSegments && $dailyReportStatus == timecardRecord::STATUS_SUBMITTED && ($authority || $force) && !$has_attendance;
        $dailyReportCancel = !$hasProjectSegments && $dailyReportStatus == timecardRecord::STATUS_APPROVED && ($authority || $force) && !$has_attendance;
        $overtimeApproveOrDeny = !$hasOvertimeProjectSegments && $overtimeStatus == 1 && ($authority || $force) && !$has_attendance;
        $overtimeCancel = !$hasOvertimeProjectSegments && $overtimeStatus == 2 && ($authority || $force) && !$has_attendance;
        return [
            $dailyReportApproveOrDeny,
            $dailyReportCancel,
            $overtimeApproveOrDeny,
            $overtimeCancel
        ];
    }

    private function canManageProjectSegment(User $activeUser, TimecardProjectSegment $segment, User $targetUser): bool
    {
        if (!$activeUser->isAdmin() && (int) $targetUser->id === (int) $activeUser->id) {
            return false;
        }

        return $activeUser->isAdmin()
            || (int) $activeUser->work_authority === 1
            || $activeUser->isProjectManager($segment->project_id);
    }

    private function projectSegmentAbility(TimecardProjectSegment $segment, User $activeUser, User $targetUser, bool $hasAttendance, bool $dailyReportCanModify): array
    {
        $status = $segment->status ?? TimecardProjectSegment::STATUS_DRAFT;
        $canManage = !$hasAttendance && $this->canManageProjectSegment($activeUser, $segment, $targetUser);

        return [
            'edit' => !$hasAttendance && $dailyReportCanModify && in_array($status, $this->editableProjectSegmentStatuses($activeUser), true),
            'approve' => $canManage && $status === TimecardProjectSegment::STATUS_SUBMITTED,
            'reject' => $canManage && $status === TimecardProjectSegment::STATUS_SUBMITTED,
            'cancel' => $canManage && $status === TimecardProjectSegment::STATUS_APPROVED,
        ];
    }
    private function hasApprovedProjectSegments(?timecardRecord $timecard): bool
    {
        if (!$timecard) {
            return false;
        }

        return $timecard->relationLoaded('project_segments')
            ? $timecard->project_segments->contains(fn ($segment) => $segment->status === TimecardProjectSegment::STATUS_APPROVED)
            : $timecard->project_segments()->where('status', TimecardProjectSegment::STATUS_APPROVED)->exists();
    }
    private function hasTimesheetManagerAuthority(User $activeUser, User $targetUser, ?timecardRecord $timecard, ?shiftRecord $shift, ?array $managedProjectIds, array $incomingProjectIds = []): bool
    {
        if ((int) $activeUser->id === (int) $targetUser->id) {
            return false;
        }
        if ($activeUser->isAdmin()) {
            return true;
        }
        if ((int) $activeUser->work_authority === 1) {
            return true;
        }
        if (empty($managedProjectIds)) {
            return false;
        }

        $managedProjectLookup = array_flip(array_map('intval', $managedProjectIds));
        $projectIds = collect([
            $shift?->department_id,
            $timecard?->work_group_id,
        ])->merge($incomingProjectIds);

        if ($timecard) {
            $segments = $timecard->relationLoaded('project_segments')
                ? $timecard->project_segments
                : $timecard->project_segments()->get(['project_id']);

            $projectIds = $projectIds->merge($segments->pluck('project_id'));
        }

        return $projectIds
            ->filter()
            ->unique()
            ->contains(fn ($projectId) => isset($managedProjectLookup[(int) $projectId]));
    }
    private function incomingProjectIdsFromRequest(Request $request): array
    {
        return collect($request->input('project_time_entries', []))
            ->filter(fn ($entry) => is_array($entry))
            ->map(fn ($entry) => (int) ($entry['project_id'] ?? 0))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
    private function ensureCanModifyTimecardForTarget(User $activeUser, User $targetUser, ?timecardRecord $timecard, ?shiftRecord $shift, array $incomingProjectIds = []): void
    {
        if ((int) $activeUser->id === (int) $targetUser->id) {
            return;
        }
        if ($activeUser->isAdmin() || (int) $activeUser->work_authority === 1) {
            return;
        }
        if ($this->hasTimesheetManagerAuthority($activeUser, $targetUser, $timecard, $shift, $this->managedShiftProjectIds($activeUser), $incomingProjectIds)) {
            return;
        }

        abort(403, 'この日報を操作する権限がありません。');
    }
    private function ensureCanApproveWholeTimecard(User $activeUser, timecardRecord $timecard): void
    {
        if (!$activeUser->isAdmin() && (int) $timecard->user_id === (int) $activeUser->id) {
            abort(403, '自分の日報は承認できません。');
        }
        if ($activeUser->isAdmin() || (int) $activeUser->work_authority === 1) {
            return;
        }

        $targetUser = $timecard->relationLoaded('user')
            ? $timecard->user
            : User::findOrFail($timecard->user_id);
        $shift = shiftRecord::where('shift_day', $timecard->day)
            ->where('user_id', $timecard->user_id)
            ->first();
        $managedProjectIds = $this->managedShiftProjectIds($activeUser);
        $segments = $timecard->relationLoaded('project_segments')
            ? $timecard->project_segments
            : $timecard->project_segments()->get(['project_id']);

        if ($segments->isNotEmpty()) {
            $managedProjectLookup = array_flip(array_map('intval', $managedProjectIds ?? []));
            $canManageAllSegments = $segments
                ->pluck('project_id')
                ->filter()
                ->unique()
                ->every(fn ($projectId) => isset($managedProjectLookup[(int) $projectId]));

            if ($canManageAllSegments) {
                return;
            }

            abort(403, 'この日報を承認する権限がありません。');
        }

        if ($this->hasTimesheetManagerAuthority($activeUser, $targetUser, $timecard, $shift, $managedProjectIds)) {
            return;
        }

        abort(403, 'この日報を承認する権限がありません。');
    }
    private function lockedProjectSegmentStatuses(?User $user = null): array
    {
        $user ??= $this->active_user();
        if ($user->isAdmin()) {
            return [TimecardProjectSegment::STATUS_APPROVED];
        }

        return [TimecardProjectSegment::STATUS_SUBMITTED, TimecardProjectSegment::STATUS_APPROVED];
    }
    private function editableProjectSegmentStatuses(?User $user = null): array
    {
        $user ??= $this->active_user();
        if ($user->isAdmin()) {
            return [TimecardProjectSegment::STATUS_DRAFT, TimecardProjectSegment::STATUS_REJECTED, TimecardProjectSegment::STATUS_SUBMITTED];
        }

        return [TimecardProjectSegment::STATUS_DRAFT, TimecardProjectSegment::STATUS_REJECTED];
    }
    private function hasLockedProjectSegments(?timecardRecord $timecard, ?User $user = null): bool
    {
        if (!$timecard) {
            return false;
        }

        $lockedStatuses = $this->lockedProjectSegmentStatuses($user);
        return $timecard->relationLoaded('project_segments')
            ? $timecard->project_segments->contains(fn ($segment) => in_array($segment->status, $lockedStatuses, true))
            : $timecard->project_segments()->whereIn('status', $lockedStatuses)->exists();
    }
    private function has_overtime_access($shift, $user, $time_card, $date, $active_user, $has_attendance = false){
        $today_or_future = empty($shift) ? false : $date->format('Y-m-d') >= date('Y-m-d');
        $possibleTypes = [1,6,7,8,9,10,11,12,13];
        $userMatch = $user->id == $active_user->id;       
        $timeCardCheck = empty($time_card)
            || (int) $time_card->status_flag === timecardRecord::STATUS_REJECTED
            || (int) $time_card->status_flag === timecardRecord::STATUS_DRAFT;
        $overtimeRequestEditable = !$shift?->overtime_request || (int) $shift->overtime_request->status === 0;
        return !$has_attendance && $today_or_future && in_array($shift->shiftType->id, $possibleTypes) && $userMatch && $timeCardCheck && $active_user->position_id !== 15 && $overtimeRequestEditable;
    }
    private function has_daily_report($shift, $time_card, $day, $user, $active_user, $has_attendace, $authority){
        $timecardExist = $time_card !== null;
        $valid_shift = (!empty($shift) && $shift->shiftType->id !== 3) || $user->position_id == 15 || $user->position_id < 6;
        $isToday = date('Y-m-d') == $day->format('Y-m-d');
        $isTodayOrPast = date('Y-m-d') >= $day->format('Y-m-d');
        $overtimePendingApproval = $shift?->overtime_request && (int) $shift->overtime_request->status === 1;
        $managerOrSelfAccess = $user->id == $active_user->id || $authority || $active_user->isAdmin();
        $create = !$timecardExist && !$has_attendace && !$overtimePendingApproval && $valid_shift && $isTodayOrPast && $managerOrSelfAccess;
        $status = $time_card->status_flag ?? -1;
        $ownEditable = in_array((int) $status, [timecardRecord::STATUS_DRAFT, timecardRecord::STATUS_REJECTED], true) && ($user->id == $active_user->id || $authority);
        $adminEditable = $active_user->isAdmin() && (int) $status !== timecardRecord::STATUS_APPROVED && !$overtimePendingApproval;
        $modify = $timecardExist && !$has_attendace && ($ownEditable || $adminEditable);
        $start_stamp = !$timecardExist && !$has_attendace && $valid_shift && $isToday && $user->id == $active_user->id; 
        $end_stamp = $timecardExist && !$has_attendace && ($time_card->stamp_flag == 0 || $time_card->stamp_flag == 2) && $valid_shift && $isToday && $user->id == $active_user->id;
        $break_stamp = $timecardExist && !$has_attendace && ($time_card->stamp_flag == 0 || $time_card->stamp_flag == 2) && $user->id == $active_user->id; 
        return [$create ,$modify, $start_stamp, $end_stamp, $break_stamp];
    }
    private function has_department_create($shift, $time_card, $day, $active_user, $has_attendace, $user){
        $valid_shift = !empty($shift) && $shift->shiftType->id !== 0 && $shift->shiftType->id !== 1;
        $timecardExist = $time_card !== null;
        $isTodayOrPast = date('Y-m-d') >= $day->format('Y-m-d');
        $access = $user->id == $active_user->id || $active_user->isAdmin();
        return $valid_shift && !$timecardExist && $isTodayOrPast && $access && !$has_attendace;
    }
    // Shift Functions
    public function get_shift_data(Request $request)
    {
        $request->validate([
            'current_date' => ['required', 'date_format:Y-m-d'],
            'shift_type' => ['nullable', 'integer'],
            'work_group' => ['nullable', 'array'],
        ]);

        $activeUser = $this->active_user();
        $userIds = $request->work_group ?? [$activeUser->id];
        $targetUserId = $userIds[0];

        [$year, $month] = array_map('intval', explode('-', $request->current_date));

        $data = $this->shiftService->getShiftData(
            userId: $targetUserId,
            year: $year,
            month: $month,
            requestedShiftType: (int) $request->shift_type
        );

        return response()->json($data);
    }
    public function get_work_temp(Request $request) {
        $data = $request->validate([
            'planned_year' => 'required',
            'user_code'    => 'required',
            'user_id'      => 'required',
        ]);
        $planned_year = $data['planned_year'];
        $user_code = $data['user_code'];
        $user_id = $data['user_id'];
        $ledgerWindow = $this->paidLeaveLedger->plannedLeaveWindowForUser((int) $user_id, (int) $planned_year);
        if ($ledgerWindow) {
            return response()->json($ledgerWindow);
        }

        $work_temp = workTemp::where('user_code', $user_code)
                            ->where(function ($query) use ($planned_year) {
                                
                                $query->whereYear('date', $planned_year);
                                
                            })->first();
        $consumed_days = 0;
        $remaining_days = 0;
        if($work_temp){
            $work_temp_date = $work_temp->date;
            $until_next = Carbon::parse($work_temp_date)->addYear()->format('Y-m-d');
            $consumed_days = shiftRecord::where('planned_year', $planned_year)->where('shift_type', 3)->where('user_id', $user_id)->count();
            $plannedDateCarbon = Carbon::createFromFormat('Y-m-d', $work_temp_date);
            $remaining_days = $plannedDateCarbon->year === 2023 ? 0 : $work_temp->planned_days - $consumed_days;
        }

        $data = [
            "workTemp" => $work_temp ?? null,
            "consumed_days" => $consumed_days > 0 ? $consumed_days : 0,
            "remaining_days" => $remaining_days > 0 ? $remaining_days : 0,
        ];
        return response()->json($data);

    }
    public function get_shift_with_work_group(Request $request){
        [$year, $month] = explode('-', $request->current_date);
        $user = $this->active_user();
        $authenticatedUserId = $user->id;
        if ($user->isAdmin()) {
            $workGroups = ProjectRecord::whereHas('members', function ($q) use ($year, $month){
                                            $q->whereHas('shift_records', function ($q) use($year, $month) {
                                                $q->whereYear('shift_day', $year)
                                                    ->whereMonth('shift_day', $month);
                                            });
                                    })->orWhereHas('manager', function ($q) use ($year, $month){
                                        $q->whereHas('shift_records', function ($q) use($year, $month) {
                                            $q->whereYear('shift_day', $year)
                                                ->whereMonth('shift_day', $month);
                                        });
                                    })
                                    ->with(['manager' => function ($q) use ($year, $month) {
                                        $q->whereHas('shift_records', function ($q) use($year, $month) {
                                                $q->whereYear('shift_day', $year)
                                                    ->whereMonth('shift_day', $month);
                                            });
                                    }])
                                    ->with(['members' => function ($q) use ($year, $month) {
                                        $q->whereHas('shift_records', function ($q) use($year, $month) {
                                                $q->whereYear('shift_day', $year)
                                                    ->whereMonth('shift_day', $month);
                                            });
                                    }])->get();
        } else {
            $workGroups = ProjectRecord::whereHas('members', function ($q) use($year, $month, $user){
                $q->whereNot('users.id', $user->id)->whereHas('shift_records', function ($q) use($year, $month) {
                    $q->whereYear('shift_day', $year)
                        ->whereMonth('shift_day', $month);
                });
            })->whereHas('manager', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })->with(['members' => function ($q) use ($year, $month, $user) {
                $q->whereNot('users.id', $user->id)->whereHas('shift_records', function ($q) use($year, $month) {
                        $q->whereYear('shift_day', $year)
                            ->whereMonth('shift_day', $month);
                    });
            }])->get();
            
        }
        $members = $workGroups->flatMap(function ($work_group_list_value) {
            return $work_group_list_value->members;
        })->unique('id')->values();
        $manager = $workGroups->flatMap(function ($work_group_list_value) {
            return $work_group_list_value->manager;
        })->unique('id')->values();
        $work_group_users = $members->merge($manager)->unique('id')->values()->all();
        usort($work_group_users, function ($a, $b) use ($authenticatedUserId) {
            if ($a->id == $authenticatedUserId) {
                return -1;
            } elseif ($b->id == $authenticatedUserId) {
                return 1;
            } else {
                return $a->id - $b->id;
            }
        });
        $user_ids = collect($work_group_users)->pluck('id')->toArray();
        $userShifts = shiftRecord::whereIn('user_id', $user_ids)
                        ->whereYear('shift_day', $year)
                        ->whereMonth('shift_day', $month)
                        ->with([
                            'shiftType' => function ($query) {
                                $query->select('id', 'name', 'abbreviation', 'value', 'full_day');
                            },
                            'department:id,name',
                            'old_shift' => function ($query) {
                                $query->whereNot('status_flag', 1)->withTrashed()->select('id', 'shift_day', 'shift_type', 'department_id');
                                $query->with([
                                    'shiftType' => function ($subQuery) {
                                        $subQuery->select('id', 'name', 'abbreviation', 'value', 'full_day');
                                    },
                                    'department:id,name',
                                ]);
                            }
                        ])
                        ->orderBy('shift_day', 'asc')
                        ->get();
        $work_group_users = collect($work_group_users);
        $work_group_users = $work_group_users->map(function ($user) use($userShifts, $year, $month) {
            $user_shift_records = $userShifts->where('user_id', $user->id)->whereIn('shift_type', [0, 18, 19, 20, 21, 22, 23, 24, 25, 26]);
            $user_work_minutes_per_day = $user->work_time_day;
            $userWorkTimeData = $this->sharedService->work_days_calculator($year, $month, $user);
            $userPlannedTimeData = $this->sharedService->planned_shift_calculator($userShifts->where('user_id', $user->id));
            $workdayNum = $userWorkTimeData['days'];
            $shift_work_hours = $userWorkTimeData['work_minutes'];
            $total_holidays = $user_shift_records->sum(function ($shift) use ($user_work_minutes_per_day) {
                $is_full_day = $shift->shiftType->full_day == 2 || $shift->shiftType->id == 0;
                $is_half_day = $shift->shiftType->full_day == 1;
                if($is_full_day){
                    return $user_work_minutes_per_day;
                } elseif($is_half_day) {
                    return $user_work_minutes_per_day / 2;
                } else {
                    return $shift->shiftType->value;
                }
            });
            $user['holiday_shifts'] = $total_holidays;
            $user['work_day_num'] = $workdayNum;
            $user['should_work_hours'] = $shift_work_hours;
            $user['planned_shift_data'] = $userPlannedTimeData;
            return $user;
        });
        $shift_records = $userShifts->groupBy('shift_day')->map(function ($shifts) {
            return $shifts->keyBy('user_id');
        });
        $data = [
            'work_users' => $work_group_users,
            'shift_records' => $shift_records,
            'work_groups' => $workGroups
        ];
        return response()->json($data);
    }   
    public function shift_approve_all(Request $request){
        $user = $this->active_user();
        $request->validate([
            'user_ids' => 'required',
            'year_month' => 'required'
        ]);
        [$year, $month] = explode('-', $request->year_month);
        $query = shiftRecord::whereIn('user_id', $request->user_ids)
            ->whereYear('shift_day', $year)
            ->whereMonth('shift_day', $month)
            ->whereNot('status_flag', 1)
            ->whereNot('user_id', $user->id);

        $this->scopeShiftApprovalToUserProjects($query, $user);

        $shifts = $query->update([
            "status_flag" => 3,
            "approved_by" => $user->id
        ]);
        return response()->json([
            'data' => $shifts ?? null
        ]);
    }
    public function shift_approve(Request $request){
        $user = $this->active_user();
        $request->validate([
            'shift_id' => 'required'
        ]);
        $shiftRecord = shiftRecord::findOrFail($request->shift_id);
        abort_unless($this->canApproveShiftRecord($shiftRecord, $user), 403);

        if($request->status){
            $shift = $shiftRecord->update([
                "status_flag" => $request->status,
                "approved_by" => $user->id
            ]);
        } else {
            $shift = $shiftRecord;
            if ($shift->overtime_request) {
                $shift->overtime_request->delete();
            }
            $shift->delete();
        }
        
        return response()->json([
            'data' => $shift ?? null
        ]);
    }
    private function managedShiftProjectIds($user): ?array
    {
        if ($user->isAdmin()) {
            return null;
        }

        return ProjectRecord::whereHas('manager', function ($q) use ($user) {
            $q->where('users.id', $user->id);
        })->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
    private function scopeShiftApprovalToUserProjects($query, $user): void
    {
        $projectIds = $this->managedShiftProjectIds($user);
        if ($projectIds === null) {
            return;
        }

        $query->whereIn('department_id', $projectIds);
    }
    private function canApproveShiftRecord(shiftRecord $shift, $user): bool
    {
        if ((int) $shift->user_id === (int) $user->id) {
            return false;
        }

        $projectIds = $this->managedShiftProjectIds($user);
        if ($projectIds === null) {
            return true;
        }

        return $shift->department_id && in_array((int) $shift->department_id, $projectIds, true);
    }
    private function shiftNetWorkMinutes(?string $startTime, ?string $endTime): int
    {
        if (!$startTime || !$endTime) {
            return 0;
        }

        [$startHour, $startMinute] = array_map('intval', explode(':', $startTime));
        [$endHour, $endMinute] = array_map('intval', explode(':', $endTime));
        $start = ($startHour * 60) + $startMinute;
        $end = ($endHour * 60) + $endMinute;
        if ($end < $start) {
            $end += 24 * 60;
        }

        $gross = max(0, $end - $start);
        $break = $gross > 360 ? 60 : ($gross >= 180 ? 30 : 0);

        return max(0, $gross - $break);
    }
    private function shiftTypeHasWorkTime(?shiftType $type, int $workMinutesPerDay): bool
    {
        if (!$type) {
            return false;
        }

        if (in_array((int) $type->id, [0, 18], true) || (int) $type->full_day === 2) {
            return false;
        }

        if ($type->value === null || $type->value === '') {
            return true;
        }

        return max(0, $workMinutesPerDay - (int) $type->value) > 0;
    }
    public function shiftAdd(Request $request)
    {
        $user         = $this->active_user();
        $user_id      = $request->userId;
        $position_id  = (int) $request->position_id;
        $shift_array  = $request->shift_array;
        $start_time   = $request->shiftTimeStart;
        $end_time     = $request->shiftEndStart;
        [$year, $month] = explode('-', $request->yearMonth);

        $shift_days = collect($shift_array)->pluck('date')->unique()->values()->all(); // ['2025-10-01', ...]
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end   = (clone $start)->endOfMonth()->endOfDay();

        // Special behavior for position 15 on other employees:
        $isSpecial = ($position_id === 15 && $user_id !== $user->id);

        DB::transaction(function () use (
            $isSpecial, $user_id, $shift_days, $start, $end,
            $shift_array, $start_time, $end_time, $position_id, $request,
            $year, $month, $user
        ) {
            if ($isSpecial) {
                // 1) Delete all OTHER shifts in the month not in shift_days
                shiftRecord::where('user_id', $user_id)
                    ->whereBetween('shift_day', [$start, $end])
                    ->when(!empty($shift_days), fn ($q) => $q->whereNotIn('shift_day', $shift_days))
                    ->delete();
            }

            // 2) Your normal validations (kept as-is)
            $holidayTypes = [0, 2, 3, 5, 14, 15, 16, 17];

            $holidays = collect($shift_array)->whereIn('type', $holidayTypes)->pluck('date')->all();
            $overtimeCheck = shiftRecord::where('user_id', $user_id)
                ->whereIn('shift_day', $holidays)
                ->whereHas('overtime_request')
                ->exists();

            $nonWorkDays = collect($shift_array)->reject(fn($s) => $s['type'] === 0)->pluck('date')->all();
            $waitingAllowanceCheck = timecardRecord::where('user_id', $user_id)
                ->whereIn('day', $nonWorkDays)
                ->with([
                    'project_segments:id,timecard_record_id,detail_values',
                    'custom_field_data_records' => fn ($q) => $q->where('type_id', 37)->select('id', 'table_record_id', 'type_id', 'value_int'),
                ])
                ->get(['id', 'user_id', 'day'])
                ->contains(fn (timecardRecord $timecard) => $this->timecardAllowanceCount($timecard, 2) > 0);

            if ($waitingAllowanceCheck) {
                throw ValidationException::withMessages(['message' => '「待機手当」は休日のみ支給されます。']);
            }
            if ($overtimeCheck) {
                throw ValidationException::withMessages(['message' => '残業申請の日をお休みにすることができません。もう一回確認ください。']);
            }

            // 3) Upsert for provided days
            $existing = shiftRecord::where('user_id', $user_id)
                ->whereIn('shift_day', $shift_days)
                ->get()
                ->keyBy('shift_day');
            $shiftTypesById = shiftType::whereIn('id', collect($shift_array)->pluck('type')->unique())->get()->keyBy('id');
            $workMinutesPerDay = $this->shiftNetWorkMinutes($start_time, $end_time);

            $newRows = [];

            foreach ($shift_array as $shift) {
                $date = $shift['date'];
                $type = $shift['type'];
                $planned_year = $shift['planned_year'];
                $needsProject = $this->shiftTypeHasWorkTime($shiftTypesById->get($type), $workMinutesPerDay);
                $departmentId = $needsProject ? ($shift['department_id'] ?? null) : null;
                if ($needsProject && empty($departmentId)) {
                    throw ValidationException::withMessages(['message' => '勤務が含まれる日はプロジェクトを選択してください。']);
                }
                // status_flag rule preserved; if you want special behavior for 15, tweak here
                $status_flag = ($type === 3) || ($isSpecial) ? 1 : 2;
                // $planned_year = $type === 3 ? $request->planned_year : $request->year;

                if ($existing->has($date)) {
                    $rec = $existing[$date];

                    // if type changed, replace record
                    if ($rec->shift_type !== $type) {
                        $newRows[] = [
                            'user_id'       => $rec->user_id,
                            'shift_day'     => $date,
                            'shift_type'    => $type,
                            'start_time'    => $start_time,
                            'end_time'      => $end_time,
                            'department_id' => $departmentId,
                            'status_flag'   => $status_flag,
                            'planned_year'  => $type === 3 ? $planned_year : $rec->planned_year,
                            'descendant_of' => $rec->id,
                            'created_at'    => now(),
                            'updated_at'    => now(),
                        ];
                        $rec->delete();
                    } else {
                        // same type, just time update if needed
                        if ($rec->start_time !== $start_time || $rec->end_time !== $end_time || (int) $rec->department_id !== (int) $departmentId) {
                            $rec->update([
                                'start_time' => $start_time,
                                'end_time' => $end_time,
                                'department_id' => $departmentId,
                                'status_flag' => $status_flag,
                                'approved_by' => null,
                            ]);
                        }
                    }
                } else {
                    // brand new for that day
                    $newRows[] = [
                        'user_id'       => $user_id,
                        'shift_day'     => $date,
                        'shift_type'    => $type,
                        'start_time'    => $start_time,
                        'end_time'      => $end_time,
                        'department_id' => $departmentId,
                        'status_flag'   => $status_flag,
                        'planned_year'  => $planned_year,
                        'descendant_of' => null,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ];
                }
            }

            if (!empty($newRows)) {
                shiftRecord::insert($newRows);
            }

            $this->paidLeaveLedger->reconcileShiftUsagesForUserMonth((int) $user_id, (int) $year, (int) $month, (int) $user->id);
        });

        // 4) Calendar sync after the database and paid-leave ledger transaction succeeds.
        $this->sharedService->syncShiftToCalendar($user_id, $year, $month);

        return response()->json(['ok' => true]);
    }
    
    public function getWorkGroup(Request $request){
        $user = $this->active_user();
        $auth_user_id = $user->id;
        $ids = User::ADMIN_USER_IDS;
        if ($user->isAdmin()) {
            $work_group_users = ProjectRecord::where('status', 'running')
                ->where(function ($q) {
                    $q->whereHas('members')
                        ->orWhereHas('manager');
                })
                ->with(['members' => function($q) use($ids) {
                $q->whereNotIn('users.id', $ids)
                    ->where('users.partner_flag', 0)
                    ->where('users.retire', 0)
                    ->orWhere('users.retire_date', '>=', Carbon::now())
                    ->select([
                        'users.id as id', 
                        'users.name',
                        'users.icon_path', 
                        'users.icon_bg',
                        'users.name_kana', 
                        'users.work_authority', 
                        'users.position_id',
                        'users.on_leave'
                    ]);
            }])->with('manager', 'director')
            ->get();
        }else{
            $work_group_users = ProjectRecord::query()
                            ->where('status', 'running')
                            ->where(function ($q) use ($auth_user_id) {
                                $q->whereHas('members', function($q) use($auth_user_id) {
                                    $q->whereIn('users.id', [$auth_user_id]);
                                })->orWhereHas('manager', function($q) use($auth_user_id) {
                                    $q->whereIn('users.id', [$auth_user_id]);
                                })->orWhere('director_id', $auth_user_id);
                            })
                            ->with([
                                'members' => function($q) use($ids) {
                                    $q->whereNotIn('users.id', $ids)
                                        ->where('users.partner_flag', 0)
                                        ->where('users.retire', 0)
                                        ->select([
                                            'users.id as id', 
                                            'users.name',
                                            'users.icon_path', 
                                            'users.icon_bg',
                                            'users.name_kana', 
                                            'users.work_authority', 
                                            'users.position_id',
                                            'users.on_leave'
                                        ]);
                                },
                                'manager',
                                'director',
                            ])
                            ->get();
        }   
        
        

        return response()->json($work_group_users);
    }
    public function dailyReportAdd(Request $request){
        $exist_timecard = timecardRecord::where('day', $request->day)->where('user_id', Auth::id())->first();
        if($exist_timecard && !empty($exist_timecard->start_time)){
            $exist_timecard->end_time = $request->end_time;
            $exist_timecard->stamp_flag = 1;
            $exist_timecard->save();
            return response()->json($exist_timecard);
        }else{
            $timecard = new timecardRecord;
            $timecard->user_id = Auth::id();
            $timecard->day = $request->day;
            $timecard->start_time = $request->start_time;
            $timecard->stamp_flag = 0;
            $timecard->save();
            return response()->json($timecard);
        }
    }
    public function daily_report_break(Request $request){
        $breakTime = $request->break_start;
        $time_card = $request->record;
        $exist_timecard = timecardRecord::find($time_card['id']);
        $timecard_break = $exist_timecard->timecard_break_records()->where('break_flag', 1)->first();
        if(!empty($timecard_break)){                       
             
            $start = Carbon::createFromFormat('H:i:s', $timecard_break->start_time);
            $end = Carbon::createFromFormat('H:i:s', $breakTime);
            $diffinMinutes = (int) $start->diffInMinutes($end, true);
            
            $timecard_break->update([
                'break_by_minute' => ceil($diffinMinutes / 15) * 15,
                'end_time' => $breakTime,
                'break_flag' => 2
            ]);
            $exist_timecard->update(['stamp_flag' => 0]);
            return response()->json($timecard_break);
             
            
        }        
        $exist_timecard->timecard_break_records()->create([
            'user_id' => $time_card['user_id'],
            'day' => $time_card['day'],
            'start_time' => $breakTime,
        ]);
        $exist_timecard->update(['stamp_flag' => 2]);
        return response()->json($timecard_break);
        
        
    }

    public function check_break_time(){
        $active_user = $this->active_user();
        $today = Carbon::now()->format('Y-m-d');
        $inBreak = timecardBreakRecord::where('break_flag', 1)
                            ->where('user_id', $active_user->id)
                            ->where('day', $today)
                            ->exists();
        return response()->json($inBreak);
    }
    private function breakTimeCheck($request){
        $attendanceMode = $request->attendance_mode ?? 'work_only';
        $startTime = $request->start_time;
        $endTime = $request->end_time;
        $breakTime = (int) ($request->breakTime ?? 0);
        
        if ($attendanceMode === 'training_only' || empty($startTime) || empty($endTime)) {
            return;
        }
        $trainingOverlapMinutes = $attendanceMode === 'work_and_training'
            ? $this->trainingOverlapMinutesFromRequest($request, $startTime, $endTime)
            : 0;
        $workTimeMinutes = max(0, $this->workReportTimeService->minutesBetweenTimes($startTime, $endTime) - $trainingOverlapMinutes - $breakTime);

        if ($workTimeMinutes >= 360 && $breakTime < 60) {
            throw ValidationException::withMessages(['message' => '6時間以上の勤務の場合、最低でも60分間の休憩を取る必要があります。']);
        } elseif ($workTimeMinutes >= 180 && $workTimeMinutes < 360 && $breakTime < 30) {
            throw ValidationException::withMessages(['message' => '3時間以上の勤務の場合、最低でも30分間の休憩を取る必要があります。']);
        }
    }
    private function sanitizeOvertimeProjectSegments(Request $request, shiftRecord $shift): array
    {
        $defaultContent = trim((string) ($request->overtime_content ?? ''));
        $segments = collect($request->input('project_segments', []))
            ->filter(fn ($segment) => is_array($segment))
            ->map(function ($segment) use ($defaultContent) {
                $content = trim((string) ($segment['content'] ?? $defaultContent));

                return [
                    'project_id' => (int) ($segment['project_id'] ?? 0),
                    'minutes' => max(0, (int) ($segment['minutes'] ?? 0)),
                    'content' => substr($content, 0, 2000),
                    'status' => 1,
                ];
            })
            ->filter(fn ($segment) => $segment['project_id'] > 0 && $segment['minutes'] > 0)
            ->values()
            ->all();

        if (empty($segments) && (int) ($request->minutes ?? 0) > 0 && $shift->department_id) {
            return $this->combineOvertimeProjectSegments([[
                'project_id' => (int) $shift->department_id,
                'minutes' => (int) $request->minutes,
                'content' => substr($defaultContent, 0, 2000),
                'status' => 1,
            ]]);
        }

        return $this->combineOvertimeProjectSegments($segments);
    }

    private function combineOvertimeProjectSegments(array $segments, int $fallbackStatus = 1): array
    {
        return collect($segments)
            ->filter(fn ($segment) => is_array($segment))
            ->map(function ($segment) use ($fallbackStatus) {
                return [
                    'project_id' => (int) ($segment['project_id'] ?? 0),
                    'minutes' => max(0, (int) ($segment['minutes'] ?? 0)),
                    'content' => trim((string) ($segment['content'] ?? '')),
                    'status' => $this->overtimeSegmentStatus($segment['status'] ?? $fallbackStatus, $fallbackStatus),
                ];
            })
            ->filter(fn ($segment) => $segment['project_id'] > 0 && $segment['minutes'] > 0)
            ->groupBy('project_id')
            ->map(function ($projectSegments) use ($fallbackStatus) {
                $contents = $projectSegments
                    ->pluck('content')
                    ->map(fn ($content) => trim((string) $content))
                    ->filter()
                    ->unique()
                    ->values()
                    ->implode("\n");

                return [
                    'project_id' => (int) $projectSegments->first()['project_id'],
                    'minutes' => (int) $projectSegments->sum('minutes'),
                    'content' => substr($contents, 0, 2000),
                    'status' => $this->deriveOvertimeRequestStatus($projectSegments->all(), $fallbackStatus),
                ];
            })
            ->values()
            ->all();
    }

    private function overtimeRequestContentSummary(array $projectSegments, ?string $fallbackContent = null): string
    {
        $contents = collect($projectSegments)
            ->map(fn ($segment) => trim((string) ($segment['content'] ?? '')))
            ->filter()
            ->unique()
            ->values();

        if ($contents->isNotEmpty()) {
            return $contents->implode("\n");
        }

        return trim((string) $fallbackContent);
    }

    private function overtimeProjectContentByProject(array $projectSegments, ?string $fallbackContent = null): array
    {
        $fallback = trim((string) $fallbackContent);

        return collect($projectSegments)
            ->filter(fn ($segment) => is_array($segment))
            ->groupBy(fn ($segment) => (int) ($segment['project_id'] ?? 0))
            ->map(function ($segments) use ($fallback) {
                $content = $segments
                    ->map(fn ($segment) => trim((string) ($segment['content'] ?? '')))
                    ->first(fn ($value) => $value !== '');

                return $content ?: $fallback;
            })
            ->filter()
            ->all();
    }

    private function overtimeProjectSegmentsFromTimecardSegments(array $projectSegments, int $regularMinutes, array $contentByProject = [], ?string $fallbackContent = null): array
    {
        $elapsedWorkMinutes = 0;
        $overtimeSegments = [];
        $fallbackContent = trim((string) $fallbackContent);

        foreach ($projectSegments as $segment) {
            if (($segment['segment_type'] ?? TimecardProjectSegment::TYPE_WORK) !== TimecardProjectSegment::TYPE_WORK) {
                continue;
            }

            $segmentMinutes = max(0, (int) ($segment['minutes'] ?? 0));
            $projectId = (int) ($segment['project_id'] ?? 0);
            if ($segmentMinutes <= 0 || $projectId <= 0) {
                continue;
            }

            $previousWorkMinutes = $elapsedWorkMinutes;
            $elapsedWorkMinutes += $segmentMinutes;
            if ($elapsedWorkMinutes <= $regularMinutes) {
                continue;
            }

            $overtimeMinutes = $elapsedWorkMinutes - max($regularMinutes, $previousWorkMinutes);
            if ($overtimeMinutes > 0) {
                $content = trim((string) ($segment['comment'] ?? ''))
                    ?: ($contentByProject[$projectId] ?? '')
                    ?: $fallbackContent;

                $overtimeSegment = [
                    'project_id' => $projectId,
                    'minutes' => $overtimeMinutes,
                ];
                if ($content !== '') {
                    $overtimeSegment['content'] = substr($content, 0, 2000);
                }

                $overtimeSegments[] = $overtimeSegment;
            }
        }

        return $this->combineOvertimeProjectSegments($overtimeSegments);
    }

    private function overtimeProjectSegmentsExceedApproval(array $actualSegments, mixed $approvedSegments): bool
    {
        if (empty($actualSegments) || !is_array($approvedSegments) || empty($approvedSegments)) {
            return false;
        }

        $approvedByProject = collect($approvedSegments)
            ->filter(fn ($segment) => is_array($segment))
            ->groupBy(fn ($segment) => (int) ($segment['project_id'] ?? 0))
            ->map(fn ($segments) => $segments->sum(fn ($segment) => max(0, (int) ($segment['minutes'] ?? 0))))
            ->all();

        if (empty($approvedByProject)) {
            return false;
        }

        $actualByProject = collect($actualSegments)
            ->groupBy(fn ($segment) => (int) ($segment['project_id'] ?? 0))
            ->map(fn ($segments) => $segments->sum(fn ($segment) => max(0, (int) ($segment['minutes'] ?? 0))))
            ->all();

        foreach ($actualByProject as $projectId => $minutes) {
            if ($projectId <= 0 || $minutes <= 0) {
                continue;
            }

            if (!array_key_exists($projectId, $approvedByProject) || $minutes > $approvedByProject[$projectId]) {
                return true;
            }
        }

        return false;
    }

    private function overtimeSegmentStatus(mixed $status, int $fallback = 1): int
    {
        $status = is_numeric($status) ? (int) $status : $fallback;

        return in_array($status, [0, 1, 2], true) ? $status : $fallback;
    }

    private function overtimeSegmentWithStatus(array $segment, int $fallbackStatus = 1): array
    {
        $segment['status'] = $this->overtimeSegmentStatus($segment['status'] ?? $fallbackStatus, $fallbackStatus);

        return $segment;
    }

    private function deriveOvertimeRequestStatus(array $segments, int $fallbackStatus = 1): int
    {
        $segments = collect($segments)
            ->filter(fn ($segment) => is_array($segment) && max(0, (int) ($segment['minutes'] ?? 0)) > 0)
            ->values();

        if ($segments->isEmpty()) {
            return $this->overtimeSegmentStatus($fallbackStatus, 1);
        }

        if ($segments->contains(fn ($segment) => $this->overtimeSegmentStatus($segment['status'] ?? $fallbackStatus, $fallbackStatus) === 0)) {
            return 0;
        }

        if ($segments->every(fn ($segment) => $this->overtimeSegmentStatus($segment['status'] ?? $fallbackStatus, $fallbackStatus) === 2)) {
            return 2;
        }

        return 1;
    }

    private function mergeOvertimeProjectSegmentApprovalMetadata(array $actualSegments, mixed $existingSegments, int $fallbackStatus = 1): array
    {
        $existingByProject = collect(is_array($existingSegments) ? $existingSegments : [])
            ->filter(fn ($segment) => is_array($segment) && (int) ($segment['project_id'] ?? 0) > 0)
            ->groupBy(fn ($segment) => (int) ($segment['project_id'] ?? 0))
            ->map(fn ($segments) => $segments->values())
            ->all();

        return array_map(function (array $segment) use (&$existingByProject, $fallbackStatus) {
            $projectId = (int) ($segment['project_id'] ?? 0);
            $existingSegment = null;

            if ($projectId > 0 && isset($existingByProject[$projectId]) && $existingByProject[$projectId]->isNotEmpty()) {
                $existingSegment = $existingByProject[$projectId]->shift();
            }

            $segment['status'] = $this->overtimeSegmentStatus($existingSegment['status'] ?? $fallbackStatus, $fallbackStatus);

            foreach (['approved_by', 'approved_at', 'rejected_by', 'rejected_at'] as $key) {
                if (is_array($existingSegment) && array_key_exists($key, $existingSegment)) {
                    $segment[$key] = $existingSegment[$key];
                }
            }

            return $segment;
        }, $actualSegments);
    }

    private function ensureOvertimeProjectSegmentApprover(User $user, ShiftOvertimeRequest $overtimeRequest, int $projectId): void
    {
        $isAdmin = $user->isAdmin();

        if (!$isAdmin && (int) $overtimeRequest->user_id === (int) $user->id) {
            abort(403, '自分の残業申請は承認できません。');
        }

        if ($isAdmin || (int) $user->work_authority === 1 || $user->isProjectManager($projectId)) {
            return;
        }

        abort(403, 'このプロジェクトの残業申請を承認する権限がありません。');
    }

    private function overTimeCheck($request, $calculatedMinute, array $projectSegments = [], int $regularMinutes = 0){
        $overTimeRequest = ShiftOvertimeRequest::where('overtime_day', $request->day)->where('user_id', $request->userId)->first();
        if (!$overTimeRequest) {
            return;
        }
        $existingProjectSegments = $overTimeRequest->project_segments ?? [];
        $contentByProject = $this->overtimeProjectContentByProject($overTimeRequest->project_segments ?? [], $overTimeRequest->content);
        $fallbackContent = $this->overtimeReasonFromRequest($request) ?: $overTimeRequest->content;
        $overtimeProjectSegments = $calculatedMinute > 0
            ? $this->overtimeProjectSegmentsFromTimecardSegments($projectSegments, $regularMinutes, $contentByProject, $fallbackContent)
            : [];
        $requiresProjectSegmentSync = $calculatedMinute > 0
            && empty($existingProjectSegments)
            && !empty($overtimeProjectSegments);
        $requiresReapproval = $calculatedMinute > (int) $overTimeRequest->minutes
            || $this->overtimeProjectSegmentsExceedApproval($overtimeProjectSegments, $overTimeRequest->project_segments);

        if (!$requiresReapproval && !$requiresProjectSegmentSync) {
            return;
        }

        $nextStatus = $requiresReapproval ? 1 : (int) $overTimeRequest->status;
        $overTimeRequest->status = $nextStatus;
        $overtimeProjectSegments = $this->mergeOvertimeProjectSegmentApprovalMetadata(
            $overtimeProjectSegments,
            $existingProjectSegments,
            $nextStatus
        );
        if ($requiresReapproval) {
            $overTimeRequest->minutes = $calculatedMinute;
        }
        $overTimeRequest->project_segments = $overtimeProjectSegments;
        $overTimeRequest->status = $this->deriveOvertimeRequestStatus($overtimeProjectSegments, (int) $overTimeRequest->status);
        $overTimeRequest->save();
    }
    private function calcNightSeconds(string $startTime, string $endTime, int $breakMinutes = 0): int
    {
            // Anchor both times to an arbitrary date (today). If end < start, it crosses midnight.
        $start = Carbon::createFromFormat('H:i', $startTime);
        $end   = Carbon::createFromFormat('H:i', $endTime);
        if ($end->lessThan($start)) {
            $end->addDay();
        }

        // Build the night window that surrounds the start time:
        // if start is before 05:00, the night started yesterday at 22:00,
        // otherwise it starts today at 22:00 and ends next day 05:00.
        $nightStart = $start->copy()->setTime(22, 0);
        if ($start->hour < 5) {
            $nightStart->subDay();
        }
        $nightEnd = $nightStart->copy()->addHours(7); // 22:00 → +7h = 05:00 next day

        // Overlap between [start, end] and [nightStart, nightEnd]
        $nightSeconds = $this->overlapSeconds($start, $end, $nightStart, $nightEnd);
        // Subtract break time from the night portion, but don’t go negative
        if ($breakMinutes > 0 && $start->gte($nightStart) && $end->lte($nightEnd)) {
            $nightSeconds = max(0, $nightSeconds - $breakMinutes * 60);
        }

        return $nightSeconds;
    }
    private function overlapSeconds(Carbon $aStart, Carbon $aEnd, Carbon $bStart, Carbon $bEnd): int
    {
        $A0 = $aStart->copy();
        $A1 = $aEnd->copy();
        $B0 = $bStart->copy();
        $B1 = $bEnd->copy();
        
        if ($A1->lte($A0) || $B1->lte($B0)) {
            return 0;
        }

        // Optional: align TZs to avoid weirdness from mixed zones
        $tz = $A0->getTimezone();
        foreach ([$A0, $A1, $B0, $B1] as $d) {
            $d->setTimezone($tz);
        }

        $startTs = max($A0->getTimestamp(), $B0->getTimestamp());
        $endTs   = min($A1->getTimestamp(), $B1->getTimestamp());

        return max(0, $endTs - $startTs);
    }
    private function overlapMinutesForTimes(?string $firstStartTime, ?string $firstEndTime, ?string $secondStartTime, ?string $secondEndTime): int
    {
        $firstStart = $this->workReportTimeService->timeToMinutes($firstStartTime);
        $firstEnd = $this->workReportTimeService->timeToMinutes($firstEndTime);
        $secondStart = $this->workReportTimeService->timeToMinutes($secondStartTime);
        $secondEnd = $this->workReportTimeService->timeToMinutes($secondEndTime);

        if ($firstStart === null || $firstEnd === null || $secondStart === null || $secondEnd === null) {
            return 0;
        }

        $firstEnd = $firstEnd >= $firstStart ? $firstEnd : $firstEnd + 1440;
        $secondEnd = $secondEnd >= $secondStart ? $secondEnd : $secondEnd + 1440;

        if ($secondEnd <= $firstStart) {
            $secondStart += 1440;
            $secondEnd += 1440;
        } elseif ($secondStart >= $firstEnd) {
            $secondStart -= 1440;
            $secondEnd -= 1440;
        }

        return max(0, min($firstEnd, $secondEnd) - max($firstStart, $secondStart));
    }
    private function trainingOverlapMinutesFromRequest(Request $request, ?string $workStartTime, ?string $workEndTime): int
    {
        $trainingSegments = collect($request->input('project_time_entries', []))
            ->filter(fn ($segment) => ($segment['segment_type'] ?? TimecardProjectSegment::TYPE_WORK) === TimecardProjectSegment::TYPE_TRAINING)
            ->filter(fn ($segment) => filled($segment['start_time'] ?? null) && filled($segment['end_time'] ?? null));

        if ($trainingSegments->isEmpty()) {
            return $this->overlapMinutesForTimes($workStartTime, $workEndTime, $request->training_start_time, $request->training_end_time);
        }

        return (int) $trainingSegments->sum(function ($segment) use ($workStartTime, $workEndTime) {
            return $this->overlapMinutesForTimes($workStartTime, $workEndTime, $segment['start_time'], $segment['end_time']);
        });
    }

    private function ensureProjectSegmentsDoNotOverlap(array $projectSegments): void
    {
        foreach ([
            TimecardProjectSegment::TYPE_WORK => '就業',
            TimecardProjectSegment::TYPE_TRAINING => '研修',
        ] as $segmentType => $typeLabel) {
            $segments = collect($projectSegments)
                ->filter(fn ($segment) => ($segment['segment_type'] ?? TimecardProjectSegment::TYPE_WORK) === $segmentType)
                ->filter(fn ($segment) => filled($segment['start_time'] ?? null) && filled($segment['end_time'] ?? null))
                ->values();

            for ($firstIndex = 0; $firstIndex < $segments->count(); $firstIndex++) {
                for ($secondIndex = $firstIndex + 1; $secondIndex < $segments->count(); $secondIndex++) {
                    $first = $segments[$firstIndex];
                    $second = $segments[$secondIndex];

                    if ($this->overlapMinutesForTimes($first['start_time'], $first['end_time'], $second['start_time'], $second['end_time']) <= 0) {
                        continue;
                    }

                    throw ValidationException::withMessages([
                        'message' => $this->projectSegmentOverlapMessage(
                            $typeLabel,
                            (int) ($first['project_id'] ?? 0) === (int) ($second['project_id'] ?? 0)
                        ),
                    ]);
                }
            }
        }
    }

    private function projectSegmentOverlapMessage(string $typeLabel, bool $sameProject): string
    {
        if ($sameProject) {
            return "{$typeLabel}プロジェクト時間が重複しています。同じプロジェクトの同じ時間帯は1つにまとめてください。";
        }

        return "{$typeLabel}プロジェクト時間が重複しています。同じ時間帯は1つのプロジェクトだけに入力してください。";
    }

    private function overtimeReasonFromRequest(Request $request): string
    {
        $customValues = $request->input('customValues', []);
        if (!is_array($customValues)) {
            $customValues = [];
        }
        $reason = $customValues[42] ?? $customValues['42'] ?? '';

        $reason = trim((string) $reason);
        if ($reason !== '') {
            return $reason;
        }

        return collect($request->input('project_time_entries', []))
            ->map(fn ($segment) => trim((string) data_get($segment, 'detail_values.overtime', '')))
            ->filter()
            ->unique()
            ->values()
            ->implode("\n");
    }

    private function ensureOvertimeReasonForRegularWork(Request $request, User $user, bool $hasWorkHours, int $overtimeMinutes): void
    {
        if ((int) $user->work_type !== 1 || !$hasWorkHours || $overtimeMinutes <= 0) {
            return;
        }

        if ($this->overtimeReasonFromRequest($request) !== '') {
            return;
        }

        $day = $request->day ?? $request->date;
        if ($day && ShiftOvertimeRequest::where('overtime_day', $day)->where('user_id', $request->userId)->exists()) {
            return;
        }

        throw ValidationException::withMessages([
            'message' => '時間外が発生しているため、時間外業務内容を入力してください。',
        ]);
    }

    private function applyOvertimeDetailToProjectSegments(array $projectSegments, User $user, bool $hasWorkHours, int $regularMinutes, int $overtimeMinutes, bool $hasOvertimeRequest = false, string $overtimeReason = '', ?User $actor = null): array
    {
        if ((int) $user->work_type !== 1 || !$hasWorkHours || $overtimeMinutes <= 0 || $hasOvertimeRequest) {
            return $projectSegments;
        }

        $elapsedWorkMinutes = 0;

        return array_map(function (array $segment) use (&$elapsedWorkMinutes, $regularMinutes, $overtimeReason, $actor) {
            if (($segment['segment_type'] ?? TimecardProjectSegment::TYPE_WORK) !== TimecardProjectSegment::TYPE_WORK) {
                return $segment;
            }

            $segmentMinutes = (int) ($segment['minutes'] ?? 0);
            $previousWorkMinutes = $elapsedWorkMinutes;
            $elapsedWorkMinutes += $segmentMinutes;

            if (
                !(filled($segment['id'] ?? null) && in_array($segment['status'] ?? null, $this->lockedProjectSegmentStatuses($actor), true))
                && $segmentMinutes > 0
                && $elapsedWorkMinutes > $regularMinutes
                && $previousWorkMinutes < $elapsedWorkMinutes
            ) {
                $details = $this->normalizedProjectDetails($segment['details'] ?? []);
                $details[] = 'overtime';
                $segment['details'] = array_values(array_unique($details));
                $detailValues = $this->normalizedProjectDetailValues($segment['detail_values'] ?? []);
                if (filled($overtimeReason) && blank($detailValues['overtime'] ?? null)) {
                    $detailValues['overtime'] = trim($overtimeReason);
                }
                $segment['detail_values'] = $detailValues;
            }

            return $segment;
        }, $projectSegments);
    }

    public function saveTimeCard(Request $request){
        $today = Carbon::now()->isoFormat('YYYY-MM-DD');
        $this->breakTimeCheck($request);
        $activeUser = $this->active_user();

        $user = User::select('work_time_day', 'work_type', 'id', 'name', 'position_id')->findOrFail($request->userId);
        $attendanceMode = $request->attendance_mode ?? 'work_only';
        $startTime = $request->start_time;
        $endTime = $request->end_time;
        $trainingStartTime = $request->training_start_time;
        $trainingEndTime = $request->training_end_time;

        $hasWorkHours = $attendanceMode !== 'training_only' && !empty($startTime) && !empty($endTime);
        $hasTrainingHours = $attendanceMode !== 'work_only' && !empty($trainingStartTime) && !empty($trainingEndTime);

        if (!$hasWorkHours && !$hasTrainingHours) {
            throw ValidationException::withMessages(['message' => 'Enter work hours, training hours, or both before saving.']);
        }
        if ($attendanceMode === 'work_and_training' && (!$hasWorkHours || !$hasTrainingHours)) {
            throw ValidationException::withMessages(['message' => '就業 + 研修では就業時間と研修時間の両方を入力してください。']);
        }

        $shiftForDay = shiftRecord::where('shift_day', $request->day)
            ->where('user_id', $request->userId)
            ->first();
        $incomingProjectIds = $this->incomingProjectIdsFromRequest($request);
        $authorizationTimecard = timecardRecord::with('project_segments')
            ->where('day', $request->day)
            ->where('user_id', $request->userId)
            ->first();
        $this->ensureCanModifyTimecardForTarget($activeUser, $user, $authorizationTimecard, $shiftForDay, $incomingProjectIds);

        $overtimeRequestForDay = ShiftOvertimeRequest::where('overtime_day', $request->day)
            ->where('user_id', $request->userId)
            ->first();
        if ($overtimeRequestForDay && (int) $overtimeRequestForDay->status !== 2 && (int) $request->status_flag === timecardRecord::STATUS_SUBMITTED) {
            throw ValidationException::withMessages([
                'message' => '残業申請の承認が完了してから日報を作成してください。',
            ]);
        }

        $shift_time_difference_seconds = ((int) ($user->work_time_day ?: 480)) * 60;
        $shift_time_difference_seconds = max(0, $shift_time_difference_seconds);
        $time_difference_seconds = 0;
        $night_difference_seconds = 0;
        $overtimeMinutes = 0;
        $workWindowMinutes = 0;

        if ($hasWorkHours) {
            $start = Carbon::createFromFormat('H:i', $startTime);
            $end = Carbon::createFromFormat('H:i', $endTime);
            if($end->lt($start)){
                $start->subDay();
            }

            $time_difference_seconds = (int) $start->diffInSeconds($end, true);
            if ($hasTrainingHours) {
                $time_difference_seconds -= $this->trainingOverlapMinutesFromRequest($request, $startTime, $endTime) * 60;
            }
            $time_difference_seconds -= $request->breakTime * 60;
            $time_difference_seconds = max(0, $time_difference_seconds);
            $workWindowMinutes = floor($time_difference_seconds / 60);

            $night_difference_seconds = $this->calcNightSeconds($startTime, $endTime, $request->breakTime);
        }

        $customValues = $request->input('customValues', []);
        if (!is_array($customValues)) {
            $customValues = [];
        }
        if (array_key_exists(37, $customValues)) {
            $remoteAllowance = $customValues[37] ?? [];
            $remoteAllowance = is_array($remoteAllowance)
                    ? array_values(array_unique(array_map('intval', $remoteAllowance)))
                    : [(int)$remoteAllowance];

            if(is_array($remoteAllowance)){
                if(!in_array(3, $remoteAllowance, true)){
                    $filteredRemoteWorkAllowance = array_filter($remoteAllowance, fn($value) => $value !== 4 && $value !== 5);
                    $remoteAllowance = $filteredRemoteWorkAllowance;
                }
            }
            $customValues[37] = $remoteAllowance;
        }
        $request->merge(['customValues' => $customValues]);

        if (is_array($customValues[37] ?? null) && in_array(2, $customValues[37], true)) {
            $this->checkWaitingAllowance($request);
        }

        $preflightProjectSegments = $this->workReportTimeService->buildProjectSegments($request, $hasWorkHours);
        $preflightProjectSegments = $this->workReportTimeService->sortProjectSegmentsByTime($preflightProjectSegments, $startTime, $endTime);
        $this->ensureProjectSegmentsDoNotOverlap($preflightProjectSegments);
        $preflightProjectWorkMinutes = (int) collect($preflightProjectSegments)
            ->where('segment_type', TimecardProjectSegment::TYPE_WORK)
            ->sum('minutes');
        $preflightWorkMinutesForOvertime = $preflightProjectWorkMinutes > 0 ? $preflightProjectWorkMinutes : (int) $workWindowMinutes;
        if ($user->work_type === 1 && $hasWorkHours) {
            $preflightOvertimeMinutes = max(0, $preflightWorkMinutesForOvertime - (int) floor($shift_time_difference_seconds / 60));
            $this->ensureOvertimeReasonForRegularWork($request, $user, $hasWorkHours, $preflightOvertimeMinutes);
        }

        DB::beginTransaction();
        try {
            $existingTimecard = timecardRecord::where('day', $request->day)
                ->where('user_id', $request->userId)
                ->first();
            $this->ensureCanModifyTimecardForTarget($activeUser, $user, $existingTimecard, $shiftForDay, $incomingProjectIds);
            $this->ensureEditableTimecard($existingTimecard);
            $beforeTimecardState = $this->timecardAuditLogService->trackedTimecardState($existingTimecard);
            $previousStatus = $existingTimecard?->status_flag;

            $is_exist = timecardRecord::firstOrCreate([
                'day' => $request->day,
                'user_id' => $request->userId
            ]);
            $is_exist->work_group_id = $request->department;
            $is_exist->start_time = $hasWorkHours ? $startTime : null;
            $is_exist->end_time = $hasWorkHours ? $endTime : null;
            $is_exist->training_start_time = $hasTrainingHours ? $trainingStartTime : null;
            $is_exist->training_end_time = $hasTrainingHours ? $trainingEndTime : null;
            $is_exist->over_time = 0;
            $is_exist->late_time = 0;
            if ($user->work_type === 1 && $hasWorkHours) {
                if ($time_difference_seconds >= $shift_time_difference_seconds) {                
                    $overtimeSeconds = $time_difference_seconds - $shift_time_difference_seconds;
                    $overtimeMinutes = floor($overtimeSeconds / 60);
                    $is_exist->over_time = $overtimeMinutes;
                } else {
                    $latetimeSeconds = $shift_time_difference_seconds - $time_difference_seconds;
                    $latetimeMinutes = floor($latetimeSeconds / 60);
                    $is_exist->late_time = $latetimeMinutes;
                }
            }
            if ($hasWorkHours && isset($night_difference_seconds) && $night_difference_seconds > 0) {
                $nighttimeMinutes = floor($night_difference_seconds / 60);
                $is_exist->night_over_time = $nighttimeMinutes;
            }else{
                $is_exist->night_over_time = 0;
            }
            $minutes = floor($time_difference_seconds / 60);
            $is_exist->work_time = $minutes;
            $is_exist->edit_start_time = $hasWorkHours ? $startTime : null;
            $is_exist->edit_end_time = $hasWorkHours ? $endTime : null;
            
            $is_exist->break_time = $hasWorkHours ? $request->breakTime : 0;
            $is_exist->stamp_flag = 1;
            $is_exist->status_flag = $request->status_flag;
            
            if($today != $request->day){
                $is_exist->work_time_edit_flag = 1;
            }
            if (!$hasWorkHours && $is_exist->project_segments()
                ->where('status', TimecardProjectSegment::STATUS_APPROVED)
                ->where('segment_type', TimecardProjectSegment::TYPE_WORK)
                ->exists()) {
                throw ValidationException::withMessages([
                    'message' => '承認済みのプロジェクト時間がある日報は研修のみに変更できません。',
                ]);
            }
            $projectSegments = $this->workReportTimeService->buildProjectSegments($request, $hasWorkHours);
            [$projectSegments, $projectSegmentsToCreate] = $this->splitProjectSegmentsForSave($is_exist, $projectSegments, $activeUser);
            $projectSegments = $this->workReportTimeService->sortProjectSegmentsByTime($projectSegments, $startTime, $endTime);
            $this->ensureProjectSegmentsDoNotOverlap($projectSegments);
            $regularWorkMinutes = (int) floor($shift_time_difference_seconds / 60);
            $projectWorkMinutesForOvertime = (int) collect($projectSegments)
                ->where('segment_type', TimecardProjectSegment::TYPE_WORK)
                ->sum('minutes');
            $workMinutesForOvertime = $projectWorkMinutesForOvertime > 0 ? $projectWorkMinutesForOvertime : (int) $workWindowMinutes;
            if ($user->work_type === 1 && $hasWorkHours) {
                $overtimeMinutes = max(0, $workMinutesForOvertime - $regularWorkMinutes);
            }
            $this->ensureOvertimeReasonForRegularWork($request, $user, $hasWorkHours, $overtimeMinutes);
            $projectSegments = $this->applyOvertimeDetailToProjectSegments(
                $projectSegments,
                $user,
                $hasWorkHours,
                $regularWorkMinutes,
                $overtimeMinutes,
                $overtimeRequestForDay !== null,
                $this->overtimeReasonFromRequest($request),
                $activeUser
            );
            $projectSegmentsToCreate = collect($projectSegments)
                ->reject(fn ($segment) => filled($segment['id'] ?? null) && in_array($segment['status'] ?? null, $this->lockedProjectSegmentStatuses($activeUser), true))
                ->map(fn ($segment) => Arr::except($segment, ['id']))
                ->values()
                ->all();
            $projectSegmentCollection = collect($projectSegments);
            $workProjectSegments = $projectSegmentCollection
                ->where('segment_type', TimecardProjectSegment::TYPE_WORK)
                ->values();
            $trainingProjectSegments = $projectSegmentCollection
                ->where('segment_type', TimecardProjectSegment::TYPE_TRAINING)
                ->values();
            $projectWorkMinutes = (int) $workProjectSegments->sum('minutes');
            if ($hasWorkHours && $workProjectSegments->isNotEmpty()) {
                $startTime = $workProjectSegments->first()['start_time'];
                $endTime = $workProjectSegments->last()['end_time'];
                $minutes = $projectWorkMinutes;
                $time_difference_seconds = $minutes * 60;
                $workWindowMinutes = $projectWorkMinutes;
                $night_difference_seconds = $this->calcNightSeconds($startTime, $endTime, $request->breakTime);
            }

            $is_exist->start_time = $hasWorkHours ? $startTime : null;
            $is_exist->end_time = $hasWorkHours ? $endTime : null;
            $is_exist->edit_start_time = $hasWorkHours ? $startTime : null;
            $is_exist->edit_end_time = $hasWorkHours ? $endTime : null;
            $is_exist->work_time = $hasWorkHours ? $minutes : 0;
            $is_exist->break_time = $hasWorkHours ? $request->breakTime : 0;
            $is_exist->over_time = 0;
            $is_exist->late_time = 0;

            if ($user->work_type === 1 && $hasWorkHours) {
                if ($time_difference_seconds >= $shift_time_difference_seconds) {
                    $overtimeSeconds = $time_difference_seconds - $shift_time_difference_seconds;
                    $overtimeMinutes = floor($overtimeSeconds / 60);
                    $is_exist->over_time = $overtimeMinutes;
                } else {
                    $latetimeSeconds = $shift_time_difference_seconds - $time_difference_seconds;
                    $latetimeMinutes = floor($latetimeSeconds / 60);
                    $is_exist->late_time = $latetimeMinutes;
                }
            }

            if ($hasWorkHours && isset($night_difference_seconds) && $night_difference_seconds > 0) {
                $nighttimeMinutes = floor($night_difference_seconds / 60);
                $is_exist->night_over_time = $nighttimeMinutes;
            }else{
                $is_exist->night_over_time = 0;
            }

            if ((int) $request->status_flag === timecardRecord::STATUS_SUBMITTED && $hasWorkHours) {
                if ($workProjectSegments->isEmpty()) {
                    throw ValidationException::withMessages([
                        'message' => '就業プロジェクト時間を入力してください。',
                    ]);
                }
                if ($projectWorkMinutes !== (int) $workWindowMinutes) {
                    throw ValidationException::withMessages([
                        'message' => 'プロジェクト別時間の合計が勤務時間と一致していません。',
                    ]);
                }
            }
            if ((int) $request->status_flag === timecardRecord::STATUS_SUBMITTED && $hasTrainingHours) {
                $projectTrainingMinutes = (int) $trainingProjectSegments->sum('minutes');
                if ($trainingProjectSegments->isEmpty() || $projectTrainingMinutes <= 0) {
                    throw ValidationException::withMessages([
                        'message' => '研修プロジェクト時間を入力してください。',
                    ]);
                }
            }

            $autoApprovedCleanSingleProject = $this->workReportTimeService->shouldAutoApproveCleanSingleProject(
                $request,
                $shiftForDay,
                $projectSegments,
                $customValues,
                $hasTrainingHours,
                $overtimeMinutes
            );

            if ($autoApprovedCleanSingleProject) {
                $is_exist->status_flag = timecardRecord::STATUS_APPROVED;
                $is_exist->approved_by = null;
                $projectSegments = array_map(function ($segment) {
                    $segment['status'] = TimecardProjectSegment::STATUS_APPROVED;
                    $segment['approved_by'] = null;
                    $segment['approved_at'] = now();
                    $segment['approval_source'] = TimecardProjectSegment::APPROVAL_SOURCE_AUTO;
                    return $segment;
                }, $projectSegments);
                $projectSegmentsToCreate = array_map(fn ($segment) => Arr::except($segment, ['id']), $projectSegments);
            } elseif ((int) $request->status_flag === timecardRecord::STATUS_APPROVED) {
                $is_exist->approved_by = $activeUser->id;
                $projectSegments = array_map(function ($segment) use ($activeUser) {
                    $segment['status'] = TimecardProjectSegment::STATUS_APPROVED;
                    $segment['approved_by'] = $activeUser->id;
                    $segment['approved_at'] = $segment['approved_at'] ?? now();
                    $segment['approval_source'] = TimecardProjectSegment::APPROVAL_SOURCE_USER;
                    return $segment;
                }, $projectSegments);
                $projectSegmentsToCreate = array_map(fn ($segment) => Arr::except($segment, ['id']), $projectSegments);
            }

            $is_exist->project_segments()
                ->where(function ($query) use ($activeUser) {
                    $query->whereIn('status', $this->editableProjectSegmentStatuses($activeUser))
                        ->orWhereNull('status');
                })
                ->delete();
            $is_exist->project_segments()->createMany($projectSegmentsToCreate);
            $this->syncVehicleDataFromProjectSegments($request, $is_exist, $projectSegments);
            $is_exist->car_mileage = $request->car_mileage ?? 0;

            if ($is_exist->car_mileage > 0) {
                $is_exist->car_used_project = $request->car_used_project;
                $is_exist->gas_full_price = $request->gas_full_price ?? 0;
            } else {
                $is_exist->car_used_project = null;
                $is_exist->gas_full_price = 0;
            }

            $is_exist->save();
            $this->syncActualCases($request, $is_exist->id);

            if($request->shiftType !== 0 && $request->shiftType !== 1){
                $this->checkDepartment($request->day, $request->userId);
            }
            $this->saveWorkCost($request, $is_exist);
            $this->saveWorkIncentive($user, $request, $is_exist);
            if($request->overTimeMinute){
                $this->overTimeCheck($request, $overtimeMinutes, $projectSegments, $regularWorkMinutes);
            }
            $afterTimecardState = $this->timecardAuditLogService->trackedTimecardState($is_exist->fresh());
            $timecardChanged = $this->statesDiffer($beforeTimecardState, $afterTimecardState);
            $isSubmitted = (int) $request->status_flag === timecardRecord::STATUS_SUBMITTED;
            $wasExistingTimecard = $existingTimecard !== null;

            if ($autoApprovedCleanSingleProject && $previousStatus !== timecardRecord::STATUS_APPROVED) {
                $this->timecardAuditLogService->logTimecardEvent(
                    'timecard_approved',
                    $is_exist,
                    (int) $request->userId,
                    $beforeTimecardState,
                    $afterTimecardState,
                    ['source' => 'clean_single_project_auto_approval']
                );
            } elseif ($isSubmitted && $previousStatus !== timecardRecord::STATUS_SUBMITTED) {
                $this->timecardAuditLogService->logTimecardEvent(
                    'timecard_submitted',
                    $is_exist,
                    (int) $request->userId,
                    $beforeTimecardState,
                    $afterTimecardState
                );
            } elseif ($timecardChanged) {
                $this->timecardAuditLogService->logTimecardEvent(
                    $wasExistingTimecard ? 'timecard_updated' : 'timecard_saved_draft',
                    $is_exist,
                    (int) $request->userId,
                    $beforeTimecardState,
                    $afterTimecardState
                );
            }
            DB::commit();
            return response()->json(['success' => 'success'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    private function splitProjectSegmentsForSave(timecardRecord $timecard, array $incomingSegments, ?User $actor = null): array
    {
        $lockedSegments = $timecard->project_segments()
            ->whereIn('status', $this->lockedProjectSegmentStatuses($actor))
            ->get()
            ->keyBy('id');

        if ($lockedSegments->isEmpty()) {
            $segmentsToCreate = array_map(fn ($segment) => Arr::except($segment, ['id']), $incomingSegments);

            return [$segmentsToCreate, $segmentsToCreate];
        }

        $editableSegments = [];

        foreach ($incomingSegments as $segment) {
            $segmentId = $segment['id'] ?? null;

            if ($segmentId && $lockedSegments->has($segmentId)) {
                $this->assertLockedProjectSegmentUnchanged($lockedSegments->get($segmentId), $segment);
                continue;
            }

            $editableSegments[] = Arr::except($segment, ['id']);
        }

        $preservedSegments = $lockedSegments
            ->map(fn (TimecardProjectSegment $segment) => [
                'id' => $segment->id,
                'project_id' => (int) $segment->project_id,
                'segment_type' => $segment->segment_type ?? TimecardProjectSegment::TYPE_WORK,
                'start_time' => $this->workReportTimeService->normalizeTime($segment->start_time),
                'end_time' => $this->workReportTimeService->normalizeTime($segment->end_time),
                'minutes' => (int) $segment->minutes,
                'details' => $this->normalizedProjectDetails($segment->details),
                'detail_values' => $this->normalizedProjectDetailValues($segment->detail_values),
                'comment' => $this->normalizedProjectComment($segment->comment),
                'status' => $segment->status,
                'approved_by' => $segment->approved_by,
                'approved_at' => $segment->approved_at,
                'approval_source' => $segment->approval_source,
            ])
            ->values()
            ->all();

        return [array_merge($preservedSegments, $editableSegments), $editableSegments];
    }

    private function assertLockedProjectSegmentUnchanged(TimecardProjectSegment $lockedSegment, array $incomingSegment): void
    {
        $lockedData = [
            'project_id' => (int) $lockedSegment->project_id,
            'segment_type' => $lockedSegment->segment_type ?? TimecardProjectSegment::TYPE_WORK,
            'start_time' => $this->workReportTimeService->normalizeTime($lockedSegment->start_time),
            'end_time' => $this->workReportTimeService->normalizeTime($lockedSegment->end_time),
            'minutes' => (int) $lockedSegment->minutes,
            'details' => $this->normalizedProjectDetails($lockedSegment->details),
            'detail_values' => $this->normalizedProjectDetailValues($lockedSegment->detail_values),
            'comment' => $this->normalizedProjectComment($lockedSegment->comment),
        ];

        $incomingData = [
            'project_id' => (int) ($incomingSegment['project_id'] ?? 0),
            'segment_type' => $incomingSegment['segment_type'] ?? TimecardProjectSegment::TYPE_WORK,
            'start_time' => $this->workReportTimeService->normalizeTime($incomingSegment['start_time'] ?? null),
            'end_time' => $this->workReportTimeService->normalizeTime($incomingSegment['end_time'] ?? null),
            'minutes' => (int) ($incomingSegment['minutes'] ?? 0),
            'details' => $this->normalizedProjectDetails($incomingSegment['details'] ?? []),
            'detail_values' => $this->normalizedProjectDetailValues($incomingSegment['detail_values'] ?? []),
            'comment' => $this->normalizedProjectComment($incomingSegment['comment'] ?? null),
        ];

        if ($lockedData !== $incomingData) {
            throw ValidationException::withMessages([
                'message' => '申請中または承認済みのプロジェクト時間は変更できません。差戻されたプロジェクトのみ修正してください。',
            ]);
        }
    }

    private function normalizedProjectDetails(mixed $details): array
    {
        if (!is_array($details)) {
            return [];
        }

        $details = array_values(array_unique($details));
        sort($details);

        return $details;
    }

    private function normalizedProjectComment(mixed $comment): ?string
    {
        if (blank($comment)) {
            return null;
        }

        return trim((string) $comment);
    }

    private function normalizedProjectDetailValues(mixed $values): array
    {
        if (!is_array($values)) {
            return [];
        }

        $normalized = [];

        if (isset($values['allowance']) && is_array($values['allowance'])) {
            $normalized['allowance'] = array_values(array_unique(array_map('intval', array_filter($values['allowance'], fn ($value) => filled($value)))));
            sort($normalized['allowance']);
        }

        if (isset($values['allowance_labels']) && is_array($values['allowance_labels'])) {
            $normalized['allowance_labels'] = array_values(array_unique(array_map(
                fn ($value) => trim((string) $value),
                array_filter($values['allowance_labels'], fn ($value) => filled($value))
            )));
            sort($normalized['allowance_labels']);
        }

        if (filled($values['incident'] ?? null)) {
            $normalized['incident'] = trim((string) $values['incident']);
        }

        if (filled($values['overtime'] ?? null)) {
            $normalized['overtime'] = trim((string) $values['overtime']);
        }

        if (isset($values['mileage']) && is_array($values['mileage'])) {
            $mileage = [
                'mileage' => (int) ($values['mileage']['mileage'] ?? 0),
                'gas_full_price' => (int) ($values['mileage']['gas_full_price'] ?? 0),
                'gas_consumption' => is_numeric($values['mileage']['gas_consumption'] ?? null) ? (float) $values['mileage']['gas_consumption'] : null,
                'gas_unit_price' => is_numeric($values['mileage']['gas_unit_price'] ?? null) ? (float) $values['mileage']['gas_unit_price'] : null,
            ];

            if ($mileage['mileage'] > 0 || $mileage['gas_full_price'] > 0) {
                $normalized['mileage'] = $mileage;
            }
        }

        if (isset($values['vehicle']) && is_array($values['vehicle'])) {
            $vehicle = $this->normalizedVehicleData($values['vehicle']);
            if ($vehicle !== null) {
                $normalized['vehicle'] = $vehicle;
            }
        }

        return $normalized;
    }

    private function normalizedVehicleData(mixed $values): ?array
    {
        if (!is_array($values) || !filled($values['vehicle'] ?? null)) {
            return null;
        }

        return [
            'id' => filled($values['id'] ?? null) ? (int) $values['id'] : null,
            'vehicle' => (int) $values['vehicle'],
            'alcohol_before_time' => $this->workReportTimeService->normalizeTime($values['alcohol_before_time'] ?? null),
            'alcohol_after_time' => $this->workReportTimeService->normalizeTime($values['alcohol_after_time'] ?? null),
            'alcohol_before_value' => is_numeric($values['alcohol_before_value'] ?? null) ? (float) $values['alcohol_before_value'] : null,
            'alcohol_after_value' => is_numeric($values['alcohol_after_value'] ?? null) ? (float) $values['alcohol_after_value'] : null,
            'confirm_before_user' => filled($values['confirm_before_user'] ?? null) ? (int) $values['confirm_before_user'] : null,
            'confirm_after_user' => filled($values['confirm_after_user'] ?? null) ? (int) $values['confirm_after_user'] : null,
        ];
    }

    private function syncActualCases(Request $request, int $timecardId)
    {
        $actualResults = collect($request->input('actual_results', []))
            ->filter(fn ($row) => is_array($row))
            ->map(function ($row) use ($request) {
                if (!filled($row['project_id'] ?? null) && filled($request->department)) {
                    $row['project_id'] = $request->department;
                }

                return $row;
            });
        $allowedActualProjectIds = $this->actualProjectIdsForIncomingProjectSegments($request);
        if ($allowedActualProjectIds !== null) {
            $actualResults = $actualResults
                ->filter(fn ($row) => in_array((int) ($row['project_id'] ?? 0), $allowedActualProjectIds, true))
                ->values();
        }

        // if nothing sent, wipe old and exit
        $hasAnyValue = $actualResults->contains(function ($row) {
            return isset($row['value']) && $row['value'] !== '' && $row['value'] !== null;
        });

        // remove all old ACTUAL records for this timecard
        ProjectCase::where('timecard_record_id', $timecardId)
            ->delete();

        if (!$hasAnyValue) {
            // user cleared everything → no cases for this timecard
            return;
        }

        foreach ($actualResults as $row) {
            $value = $row['value'] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            $statusLabel = $row['status'] ?? ($request->actual_status ?: '実績');
            $meta = isset($row['meta']) && is_array($row['meta']) ? $row['meta'] : null;
            $projectId = filled($row['project_id'] ?? null) ? $row['project_id'] : $request->department;

            ProjectCase::create([
                'project_record_id'  => $projectId,
                'timecard_record_id' => $timecardId,
                'user_id'            => $request->userId,
                'status'             => $statusLabel,
                'amount'             => $value,
                'report_date'        => $request->day,
                'state'              => 'submitted',
                'submitted_at'       => now(),
                'meta'               => $meta,
            ]);
        }
    }
    private function actualProjectIdsForIncomingProjectSegments(Request $request): ?array
    {
        $entries = collect($request->input('project_time_entries', []))
            ->filter(fn ($entry) => is_array($entry));

        if ($entries->isEmpty()) {
            return null;
        }

        return $entries
            ->filter(fn ($entry) => in_array('actual', (array) ($entry['details'] ?? []), true))
            ->map(fn ($entry) => (int) ($entry['project_id'] ?? 0))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }


    private function checkDepartment($day, $user_id){
        $shift = shiftRecord::where('shift_day', $day)
                            ->where('user_id', $user_id)
                            ->first();
        if($shift){
            $shift->department_id = null;
            $shift->save();
        }
    }
    private function saveWorkIncentive($user, $request, $is_exist){
        if($user->position_id === 15){
            [$currentYear, $currentMonth] = explode('-', $request->day);
            $yearMonth = $currentYear . '-' . $currentMonth;
            $filteredCosts = array_filter($request->incentiveValues ?? [], function ($incentive) {
                return is_array($incentive)
                    && array_key_exists('count', $incentive)
                    && $incentive['count'] !== null;
            });
            $existingIncentives = $is_exist->timecard_incentives()->get();
            $existingById = $existingIncentives->keyBy('id');
            $keptIds = [];

            foreach($filteredCosts as $incentive){
                $id = $incentive['id'] ?? null;
                $incentive_exist = ($id && $existingById->has($id)) ? $existingById->get($id) : new timecardIncentive;
                $incentive_exist->record_id = $is_exist->id;
                $incentive_exist->user_id = $request->userId;
                $incentive_exist->date_month = $yearMonth;
                $incentive_exist->count = $incentive['count'];
                $incentive_exist->save();
                $keptIds[] = $incentive_exist->id;
            }

            $existingIncentives
                ->filter(fn ($incentive) => !in_array($incentive->id, $keptIds, true))
                ->each
                ->delete();
        }
    }
    private function saveWorkCost($request, $timecard)
    {
        [$y, $m] = explode('-', $request->day);
        $yearMonth = $y . '-' . $m;

        $incomingCosts = $request->input('costsValues', []);
        if (!is_array($incomingCosts)) {
            $incomingCosts = [];
        }

        $filteredCosts = array_values(array_filter($incomingCosts, function ($cost) {
            if (!is_array($cost)) {
                return false;
            }

            return !(
                blank($cost['content'] ?? null) &&
                blank($cost['expenses'] ?? null) &&
                blank($cost['file_path'] ?? null) &&
                blank($cost['departure_place'] ?? null) &&
                blank($cost['arrival_place'] ?? null) && 
                blank($cost['merchant_name'] ?? null) &&
                blank($cost['receipt_date'] ?? null)
            );
        }));
        $allowedExpenseProjects = $this->expenseProjectsForIncomingProjectSegments($request);
        $projectNamesById = $allowedExpenseProjects['namesById'] ?? [];
        $projectNamesById += $this->projectNamesForIncomingCosts($filteredCosts);
        $filteredCosts = array_map(fn ($cost) => $this->normalizeIncomingCostProjectData($cost, $projectNamesById), $filteredCosts);
        $filteredCosts = $this->attachProjectSegmentIdsToIncomingCosts($filteredCosts, $timecard);

        if ($allowedExpenseProjects !== null) {
            $allowedExpenseProjectIds = $allowedExpenseProjects['ids'];
            $allowedExpenseDepartmentNames = $allowedExpenseProjects['names'];
            $filteredCosts = array_values(array_filter($filteredCosts, function ($cost) use ($allowedExpenseProjectIds, $allowedExpenseDepartmentNames) {
                $projectId = filled(Arr::get($cost, 'project_id')) ? (int) Arr::get($cost, 'project_id') : null;
                if ($projectId !== null) {
                    return in_array($projectId, $allowedExpenseProjectIds, true);
                }

                return in_array((string) Arr::get($cost, 'department'), $allowedExpenseDepartmentNames, true);
            }));
        }

        $this->validateCost($filteredCosts, (int) $request->status_flag);

        $existingCosts = $timecard->timecard_costs()->get();
        $existingById = $existingCosts->keyBy('id');
        $existingByDraftUuid = $existingCosts->filter(fn ($cost) => filled($cost->draft_uuid))->keyBy('draft_uuid');
        $keptIds = [];

        foreach ($filteredCosts as $cost) {
            $matchedCost = null;
            $incomingId = Arr::get($cost, 'id');
            $draftUuid = Arr::get($cost, 'draft_uuid');

            if ($incomingId && $existingById->has($incomingId)) {
                $matchedCost = $existingById->get($incomingId);
            } elseif ($draftUuid && $existingByDraftUuid->has($draftUuid)) {
                $matchedCost = $existingByDraftUuid->get($draftUuid);
            }

            $attributes = $this->mapIncomingCostAttributes($cost, $request, $timecard, $yearMonth);

            if ($matchedCost) {
                $beforeState = $this->timecardAuditLogService->trackedCostState($matchedCost);
                $matchedCost->fill($attributes);
                $matchedCost->save();
                $this->finalizeReceiptForCost($cost, $matchedCost);
                $matchedCost->refresh();
                $afterState = $this->timecardAuditLogService->trackedCostState($matchedCost);
                if ($beforeState !== $afterState) {
                    $this->timecardAuditLogService->logCostEvent(
                        'cost_updated',
                        $timecard,
                        $matchedCost,
                        (int) $request->userId,
                        $beforeState,
                        $afterState
                    );
                }
                $this->syncAppliedOcrRun($cost, $matchedCost, $timecard, (int) $request->userId, $beforeState, $afterState);
                $keptIds[] = $matchedCost->id;
                continue;
            }

            $newCost = new timecardCostRecord();
            $newCost->fill($attributes);
            $newCost->save();
            $this->finalizeReceiptForCost($cost, $newCost);
            $newCost->refresh();
            $afterState = $this->timecardAuditLogService->trackedCostState($newCost);
            $this->timecardAuditLogService->logCostEvent(
                'cost_created',
                $timecard,
                $newCost,
                (int) $request->userId,
                null,
                $afterState
            );
            $this->syncAppliedOcrRun($cost, $newCost, $timecard, (int) $request->userId, null, $afterState);
            $keptIds[] = $newCost->id;
        }

        $costsToDelete = $existingCosts->filter(fn ($cost) => !in_array($cost->id, $keptIds, true));
        foreach ($costsToDelete as $costToDelete) {
            $beforeState = $this->timecardAuditLogService->trackedCostState($costToDelete);
            $this->timecardReceiptStorageService->logicalDeleteByReference(
                $costToDelete->receipt_file_id,
                $costToDelete->file_path,
                $costToDelete->draft_uuid,
                Auth::id()
            );
            $costToDelete->delete();
            $this->timecardAuditLogService->logCostEvent(
                'cost_deleted',
                $timecard,
                $costToDelete,
                (int) $request->userId,
                $beforeState,
                null
            );
        }
    }
    private function attachProjectSegmentIdsToIncomingCosts(array $costs, timecardRecord $timecard): array
    {
        $savedSegmentsByKey = $timecard->project_segments()
            ->get()
            ->keyBy(fn (TimecardProjectSegment $segment) => $this->projectSegmentVehicleMatchKey($segment));

        return array_map(function (array $cost) use ($savedSegmentsByKey) {
            if (filled(Arr::get($cost, 'timecard_project_segment_id'))) {
                return $cost;
            }

            $segmentKey = Arr::get($cost, 'project_segment_key');
            if (filled($segmentKey) && $savedSegmentsByKey->has($segmentKey)) {
                $cost['timecard_project_segment_id'] = $savedSegmentsByKey->get($segmentKey)->id;
            }

            return $cost;
        }, $costs);
    }

    private function expenseProjectsForIncomingProjectSegments(Request $request): ?array
    {
        $entries = collect($request->input('project_time_entries', []))
            ->filter(fn ($entry) => is_array($entry));

        if ($entries->isEmpty()) {
            return null;
        }

        $projectIds = $entries
            ->filter(fn ($entry) => in_array('expenses', (array) ($entry['details'] ?? []), true))
            ->map(fn ($entry) => (int) ($entry['project_id'] ?? 0))
            ->filter()
            ->unique()
            ->values();

        if ($projectIds->isEmpty()) {
            return [
                'ids' => [],
                'names' => [],
                'namesById' => [],
            ];
        }

        $namesById = ProjectRecord::query()
            ->whereIn('id', $projectIds)
            ->pluck('name', 'id')
            ->mapWithKeys(fn ($name, $id) => [(int) $id => $name])
            ->all();

        return [
            'ids' => $projectIds->all(),
            'names' => collect($namesById)
                ->filter()
                ->values()
                ->all(),
            'namesById' => $namesById,
        ];
    }
    private function projectNamesForIncomingCosts(array $costs): array
    {
        $projectIds = collect($costs)
            ->map(fn ($cost) => (int) Arr::get($cost, 'project_id', 0))
            ->filter()
            ->unique()
            ->values();

        if ($projectIds->isEmpty()) {
            return [];
        }

        return ProjectRecord::query()
            ->whereIn('id', $projectIds)
            ->pluck('name', 'id')
            ->mapWithKeys(fn ($name, $id) => [(int) $id => $name])
            ->all();
    }
    private function normalizeIncomingCostProjectData(array $cost, array $projectNamesById): array
    {
        $projectId = filled(Arr::get($cost, 'project_id')) ? (int) Arr::get($cost, 'project_id') : null;
        if ($projectId === null) {
            $projectId = $this->projectIdFromDepartmentName(Arr::get($cost, 'department'), $projectNamesById);
            if ($projectId !== null) {
                $cost['project_id'] = $projectId;
            }
        }

        if ($projectId !== null && blank(Arr::get($cost, 'department')) && filled($projectNamesById[$projectId] ?? null)) {
            $cost['department'] = $projectNamesById[$projectId];
        }

        return $cost;
    }
    private function projectIdFromDepartmentName(?string $department, array $projectNamesById): ?int
    {
        if (blank($department)) {
            return null;
        }

        $matchedProjectId = collect($projectNamesById)
            ->search(fn ($name) => (string) $name === (string) $department);

        return $matchedProjectId === false ? null : (int) $matchedProjectId;
    }

    private function validateCost($costs, int $statusFlag){
        
        foreach($costs as $move){
            if(Arr::get($move, 'department') == null ){
                throw ValidationException::withMessages(['message' => '部門に割り当ててください。']);
            }
            if(filled($move['content'] ?? null) || filled($move['departure_place'] ?? null) || filled($move['arrival_place'] ?? null)){
                if(blank($move['expenses'] ?? null)){
                    throw ValidationException::withMessages(['message' => '経費必須です。']);
                }
            }
            if(filled($move['expenses'] ?? null)){
                if(blank($move['merchant_name'] ?? null) && blank($move['receipt_date'] ?? null)){
                    throw ValidationException::withMessages(['message' => '取引先、領収書日付のいずれか必須です。']);
                }
            }
            if(filled($move['merchant_name'] ?? null) || filled($move['receipt_date'] ?? null)){
                if(blank($move['expenses'] ?? null)){
                    throw ValidationException::withMessages(['message' => '経費必須です。']);
                }
            }
            if (
                $statusFlag === 1 &&
                !empty($move['file_path']) &&
                (empty($move['merchant_name']) || empty($move['receipt_date']))
            ) {
                throw ValidationException::withMessages(['message' => '領収書がある経費は、申請時に取引先と領収書日付が必須です。']);
            }
        }   
    }
    private function checkWaitingAllowance($request){
        [$currentYear, $currentMonth] = explode('-', $request->day);
        $count = $this->monthlyAllowanceCount((int) $request->userId, 2, (int) $currentYear, (int) $currentMonth);
        if($count >= 5){
            throw ValidationException::withMessages(['message' => '待機手当は1か月に5回以上の利用はできません。']);
        }
    }
    private function monthlyAllowanceCount(int $userId, int $allowanceValue, int $year, int $month): int
    {
        return timecardRecord::query()
            ->where('user_id', $userId)
            ->whereYear('day', $year)
            ->whereMonth('day', $month)
            ->where('status_flag', '!=', timecardRecord::STATUS_DRAFT)
            ->with([
                'project_segments:id,timecard_record_id,detail_values',
                'custom_field_data_records' => fn ($q) => $q->where('type_id', 37)->select('id', 'table_record_id', 'type_id', 'value_int'),
            ])
            ->get(['id', 'user_id', 'day', 'status_flag'])
            ->sum(fn (timecardRecord $timecard) => $this->timecardAllowanceCount($timecard, $allowanceValue));
    }
    private function timecardAllowanceCount(timecardRecord $timecard, int $allowanceValue): int
    {
        $segments = $timecard->relationLoaded('project_segments') ? $timecard->project_segments : collect();
        if ($segments->isNotEmpty()) {
            return $segments->sum(function (TimecardProjectSegment $segment) use ($allowanceValue) {
                return collect($this->segmentAllowanceValues($segment))
                    ->filter(fn (int $value) => $value === $allowanceValue)
                    ->count();
            });
        }

        return ($timecard->relationLoaded('custom_field_data_records') ? $timecard->custom_field_data_records : collect())
            ->where('type_id', 37)
            ->where('value_int', $allowanceValue)
            ->count();
    }
    private function segmentAllowanceValues(TimecardProjectSegment $segment): array
    {
        $detailValues = is_array($segment->detail_values) ? $segment->detail_values : [];
        $allowances = $detailValues['allowance'] ?? [];
        if (!is_array($allowances)) {
            return [];
        }

        return collect($allowances)
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => (int) $value)
            ->values()
            ->all();
    }
    private function saveCustomData($date, $table_record_id, $user_id, $value, $type_id, $vehicleData){
        $typeId = (int) $type_id;

        if ($value === null || $value === '') {
            return;
        }

        if ($typeId === 44 && (int) $value !== 1) {
            return;
        }

        if ($typeId === 44 && (int) $value === 1){
            $this->saveVehicleData($vehicleData, $table_record_id, $user_id);
        }
        $new_custom_data = new customFieldDataRecord;
        $new_custom_data->date = $date;
        $new_custom_data->table_record_id = $table_record_id;
        $new_custom_data->user_id = $user_id;
        $new_custom_data->type_id = $typeId;
        
        switch ($typeId) {
            case 39:
            case 40:
            case 42: 
                $new_custom_data->value_text = $value;
                break;
            default: 
                $new_custom_data->value_int = $value;
                $partsRecord = customFieldPartsRecord::where('record_id', $typeId)
                                                    ->where('parts_value', $value)
                                                    ->select('parts_lavel')
                                                    ->first();
                $new_custom_data->label = $partsRecord?->parts_lavel;
        }
        $new_custom_data->save();
    }
    private function saveVehicleData($vehicleData, $table_record_id, $user_id, ?int $projectId = null, ?int $projectSegmentId = null){
        $vehicleData = $this->normalizedVehicleData($vehicleData);
        if ($vehicleData === null) {
            return;
        }

        $attributes = [
            'record_id' => $table_record_id,
            'user_id' => $user_id,
            'project_id' => $projectId,
            'timecard_project_segment_id' => $projectSegmentId,
            'vehicle' => $vehicleData['vehicle'],
            'confirm_before_user' => $vehicleData['confirm_before_user'],
            'confirm_after_user' => $vehicleData['confirm_after_user'],
            'alcohol_before_time' => $vehicleData['alcohol_before_time'],
            'alcohol_after_time' => $vehicleData['alcohol_after_time'],
            'alcohol_before_value' => $vehicleData['alcohol_before_value'],
            'alcohol_after_value' => $vehicleData['alcohol_after_value']
        ];

        if (filled($vehicleData['id'] ?? null)) {
            $vehicle = timecardVehicle::withTrashed()->updateOrCreate(
                ['id' => (int) $vehicleData['id']],
                $attributes
            );
            if ($vehicle->trashed()) {
                $vehicle->restore();
            }
            return;
        }

        timecardVehicle::create($attributes);
    }
    private function syncVehicleDataFromProjectSegments(Request $request, timecardRecord $timecard, array $projectSegments): void
    {
        $savedSegments = $timecard->project_segments()->get();
        $savedSegmentsByKey = $savedSegments->keyBy(fn (TimecardProjectSegment $segment) => $this->projectSegmentVehicleMatchKey($segment));
        $rootVehicleData = $this->normalizedVehicleData($request->input('vehicleData'));

        $vehicleRows = collect($projectSegments)
            ->filter(fn ($segment) => in_array('vehicle', (array) ($segment['details'] ?? []), true))
            ->map(function ($segment) use ($rootVehicleData, $savedSegments, $savedSegmentsByKey) {
                $vehicleData = $this->normalizedVehicleData(data_get($segment, 'detail_values.vehicle'));
                if ($vehicleData === null && $rootVehicleData !== null) {
                    $vehicleData = $rootVehicleData;
                }
                if ($vehicleData === null) {
                    return null;
                }

                $savedSegment = null;
                if (filled($segment['id'] ?? null)) {
                    $savedSegment = $savedSegments->firstWhere('id', (int) $segment['id']);
                }
                $savedSegment ??= $savedSegmentsByKey->get($this->projectSegmentVehicleMatchKey($segment));

                return [
                    'vehicle' => $vehicleData,
                    'project_id' => (int) ($segment['project_id'] ?? 0),
                    'segment_id' => $savedSegment?->id,
                ];
            })
            ->filter()
            ->values();

        $timecard->vehicle_records()->delete();

        $vehicleRows->each(function ($row) use ($timecard, $request) {
            $this->saveVehicleData(
                $row['vehicle'],
                $timecard->id,
                (int) $request->userId,
                $row['project_id'] ?: null,
                $row['segment_id'] ? (int) $row['segment_id'] : null
            );
        });
    }

    private function projectSegmentVehicleMatchKey(mixed $segment): string
    {
        $projectId = is_array($segment) ? ($segment['project_id'] ?? null) : $segment->project_id;
        $segmentType = is_array($segment) ? ($segment['segment_type'] ?? null) : $segment->segment_type;
        $startTime = is_array($segment) ? ($segment['start_time'] ?? null) : $segment->start_time;
        $endTime = is_array($segment) ? ($segment['end_time'] ?? null) : $segment->end_time;

        return implode('|', [
            (int) $projectId,
            $segmentType ?: TimecardProjectSegment::TYPE_WORK,
            $this->workReportTimeService->normalizeTime($startTime),
            $this->workReportTimeService->normalizeTime($endTime),
        ]);
    }
    public function deleteTimeCard(Request $request){
        $activeUser = $this->active_user();
        return DB::transaction(function () use ($request, $activeUser) {
            $is_exist = timecardRecord::where('day', $request->date)
                ->where('user_id', $request->userId)
                ->lockForUpdate()
                ->first();

            if (!$is_exist) {
                return response()->json(['not found' => 'not found'], 404);
            }

            $targetUser = User::findOrFail($request->userId);
            $shiftForDay = shiftRecord::where('shift_day', $request->date)
                ->where('user_id', $request->userId)
                ->first();
            $this->ensureCanModifyTimecardForTarget($activeUser, $targetUser, $is_exist->loadMissing('project_segments'), $shiftForDay);
            $this->ensureDeletableTimecard($is_exist);

            $over_time = ShiftOvertimeRequest::where('overtime_day', $request->date)
                ->where('user_id', $request->userId)
                ->first();
            $costsToDelete = $is_exist->timecard_costs()->get();
            foreach ($costsToDelete as $costToDelete) {
                $this->timecardAuditLogService->logCostEvent(
                    'cost_deleted',
                    $is_exist,
                    $costToDelete,
                    (int) $request->userId,
                    $this->timecardAuditLogService->trackedCostState($costToDelete),
                    null,
                    ['source' => 'timecard_deleted']
                );
            }
            $is_exist->custom_field_data_records()->delete();
            $is_exist->timecard_costs()->delete();
            $is_exist->timecard_incentives()->delete();
            $is_exist->vehicle_records()->delete();
            $is_exist->timecard_break_records()->delete();
            $is_exist->delete();
            if($over_time){
                $over_time->delete();
            }
            return response()->json(['success' => 'success'], 200);
        });
    }
    public function getAttendanceData(Request $request){
        $user_list = $request->work_group ?? [Auth::id()];
        $user_id = $user_list[0];
        $result = $this->attendanceService->build_attendance_data($user_list, $request->current_date);
        return response()->json($result[$user_id]);
    }
    public function remandTimeCard(Request $request){
        $user = $this->active_user();
        $time_card_record = DB::transaction(function () use ($request, $user) {
            $time_card_record = timecardRecord::where('user_id', $request->user_id )
                ->where('day', '=' , $request->record_day )
                ->lockForUpdate()
                ->first();

            if(empty($time_card_record)){
                return null;
            }
            $this->ensureCanApproveWholeTimecard($user, $time_card_record->loadMissing(['user', 'project_segments']));
            if((int) $time_card_record->status_flag === timecardRecord::STATUS_REJECTED){
                return $time_card_record;
            }

            $beforeState = $this->timecardAuditLogService->trackedTimecardState($time_card_record);
            $time_card_record->status_flag = timecardRecord::STATUS_REJECTED;
            $time_card_record->save();
            $time_card_record->project_segments()
                ->whereIn('status', [TimecardProjectSegment::STATUS_SUBMITTED, TimecardProjectSegment::STATUS_APPROVED])
                ->update([
                    'status' => TimecardProjectSegment::STATUS_REJECTED,
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                    'approval_source' => null,
                ]);
            $this->timecardAuditLogService->logTimecardEvent(
                'timecard_remanded',
                $time_card_record,
                (int) $request->user_id,
                $beforeState,
                $this->timecardAuditLogService->trackedTimecardState($time_card_record),
                ['actor_id' => $user->id]
            );
            return $time_card_record->fresh();
        });

        return response()->json($time_card_record);

    }
    public function approveTimeCard(Request $request){
        $user = $this->active_user();
        $time_card_record = DB::transaction(function () use ($request, $user) {
            $time_card_record = timecardRecord::where('user_id', $request->user_id )
                ->where('day', $request->record_day )
                ->lockForUpdate()
                ->first();

            if(empty($time_card_record)){
                return null;
            }
            $this->ensureCanApproveWholeTimecard($user, $time_card_record->loadMissing(['user', 'project_segments']));
            if((int) $time_card_record->status_flag === timecardRecord::STATUS_APPROVED){
                return $time_card_record;
            }

            if($request->overTimeRequest){
                $overtimeSegments = $request->overTimeRequest['project_segments'] ?? [];
                if (is_array($overtimeSegments) && count($overtimeSegments) > 0) {
                    if ((int) ($request->overTimeRequest['status'] ?? 1) !== 2) {
                        throw ValidationException::withMessages([
                            'message' => '残業申請のプロジェクト別承認が完了していません。',
                        ]);
                    }
                } else {
                    $data = [
                        'id' => $request->overTimeRequest['id'],
                        'status' => 2,
                        'approved_by' => $user->id
                    ];
                    $this->respond_overtime(new Request ($data));
                }
            }
            if ($time_card_record->project_segments()->where('status', '!=', TimecardProjectSegment::STATUS_APPROVED)->exists()) {
                throw ValidationException::withMessages([
                    'message' => 'プロジェクト別時間の承認が完了していません。',
                ]);
            }

            $beforeState = $this->timecardAuditLogService->trackedTimecardState($time_card_record);
            $time_card_record->approved_by = $user->id;
            $time_card_record->status_flag = timecardRecord::STATUS_APPROVED;
            $time_card_record->save();
            $this->timecardAuditLogService->logTimecardEvent(
                'timecard_approved',
                $time_card_record,
                (int) $request->user_id,
                $beforeState,
                $this->timecardAuditLogService->trackedTimecardState($time_card_record),
                ['actor_id' => $user->id]
            );
            return $time_card_record->fresh();
        });

        return response()->json($time_card_record);

    }

    public function approveTimecardProjectSegment(Request $request)
    {
        $user = $this->active_user();
        $segment = DB::transaction(function () use ($request, $user) {
            $segment = TimecardProjectSegment::with(['project:id,name', 'timecardRecord'])
                ->lockForUpdate()
                ->findOrFail($request->id);
            $this->ensureProjectSegmentApprover($user, $segment);

            if ($segment->status === TimecardProjectSegment::STATUS_APPROVED) {
                return $segment->fresh(['project:id,name', 'timecardRecord']);
            }

            $segment->status = TimecardProjectSegment::STATUS_APPROVED;
            $segment->approved_by = $user->id;
            $segment->approved_at = now();
            $segment->approval_source = TimecardProjectSegment::APPROVAL_SOURCE_USER;
            $segment->save();

            if ($segment->timecardRecord) {
                $this->approveTimecardIfProjectSegmentsComplete($segment->timecardRecord, $user);
            }

            return $segment->fresh(['project:id,name', 'timecardRecord']);
        });

        return response()->json($segment);
    }

    public function rejectTimecardProjectSegment(Request $request)
    {
        $user = $this->active_user();
        $segment = DB::transaction(function () use ($request, $user) {
            $segment = TimecardProjectSegment::with(['project:id,name', 'timecardRecord'])
                ->lockForUpdate()
                ->findOrFail($request->id);
            $this->ensureProjectSegmentApprover($user, $segment);

            if ($segment->status === TimecardProjectSegment::STATUS_REJECTED) {
                return $segment->fresh(['project:id,name', 'timecardRecord']);
            }

            $segment->status = TimecardProjectSegment::STATUS_REJECTED;
            $segment->approved_by = $user->id;
            $segment->approved_at = now();
            $segment->approval_source = null;
            $segment->save();

            if ($segment->timecardRecord) {
                $segment->timecardRecord->status_flag = timecardRecord::STATUS_REJECTED;
                $segment->timecardRecord->save();
            }

            return $segment->fresh(['project:id,name', 'timecardRecord']);
        });

        return response()->json($segment);
    }

    public function cancelTimecardProjectSegment(Request $request)
    {
        $user = $this->active_user();
        $segment = DB::transaction(function () use ($request, $user) {
            $segment = TimecardProjectSegment::with(['project:id,name', 'timecardRecord'])
                ->lockForUpdate()
                ->findOrFail($request->id);
            $this->ensureProjectSegmentApprover($user, $segment);

            if ($segment->status !== TimecardProjectSegment::STATUS_APPROVED) {
                return $segment->fresh(['project:id,name', 'timecardRecord']);
            }

            $timecard = $segment->timecardRecord
                ? timecardRecord::whereKey($segment->timecardRecord->id)->lockForUpdate()->first()
                : null;
            $beforeState = $timecard
                ? $this->timecardAuditLogService->trackedTimecardState($timecard)
                : null;

            $segment->status = TimecardProjectSegment::STATUS_SUBMITTED;
            $segment->approved_by = null;
            $segment->approved_at = null;
            $segment->approval_source = null;
            $segment->save();

            if ($timecard && (int) $timecard->status_flag === timecardRecord::STATUS_APPROVED) {
                $timecard->approved_by = null;
                $timecard->status_flag = timecardRecord::STATUS_SUBMITTED;
                $timecard->save();

                $this->timecardAuditLogService->logTimecardEvent(
                    'timecard_approval_cancelled',
                    $timecard,
                    (int) $timecard->user_id,
                    $beforeState,
                    $this->timecardAuditLogService->trackedTimecardState($timecard),
                    [
                        'actor_id' => $user->id,
                        'source' => 'project_segment_approval_cancelled',
                        'segment_id' => $segment->id,
                        'project_id' => $segment->project_id,
                    ]
                );
            }

            return $segment->fresh(['project:id,name', 'timecardRecord']);
        });

        return response()->json($segment);
    }

    private function ensureProjectSegmentApprover(User $user, TimecardProjectSegment $segment): void
    {
        $isAdmin = $user->isAdmin();
        $timecardUserId = $segment->timecardRecord?->user_id;

        if (!$isAdmin && $timecardUserId && (int) $timecardUserId === (int) $user->id) {
            abort(403, '自分のプロジェクト時間は承認できません。');
        }

        if ($isAdmin || (int) $user->work_authority === 1 || $user->isProjectManager($segment->project_id)) {
            return;
        }

        abort(403, 'このプロジェクト時間を承認する権限がありません。');
    }

    private function approveTimecardIfProjectSegmentsComplete(timecardRecord $timecard, User $actor): void
    {
        $timecard = timecardRecord::whereKey($timecard->id)->lockForUpdate()->first();
        if (!$timecard) {
            return;
        }
        if ((int) $timecard->status_flag !== timecardRecord::STATUS_SUBMITTED) {
            return;
        }

        $segments = $timecard->project_segments()->get();
        if ($segments->isEmpty() || $segments->contains(fn ($segment) => $segment->status !== TimecardProjectSegment::STATUS_APPROVED)) {
            return;
        }

        $beforeState = $this->timecardAuditLogService->trackedTimecardState($timecard);
        $timecard->approved_by = $actor->id;
        $timecard->status_flag = timecardRecord::STATUS_APPROVED;
        $timecard->save();

        $this->timecardAuditLogService->logTimecardEvent(
            'timecard_approved',
            $timecard,
            (int) $timecard->user_id,
            $beforeState,
            $this->timecardAuditLogService->trackedTimecardState($timecard),
            [
                'actor_id' => $actor->id,
                'source' => 'all_project_segments_approved',
            ]
        );
    }


    public function cancelTimeCard(Request $request){
        $active_user = $this->active_user();
        $time_card_record = DB::transaction(function () use ($request, $active_user) {
            $time_card_record = timecardRecord::where('user_id', $request->user_id )
                ->where('day', $request->record_day )
                ->lockForUpdate()
                ->first();

            if(empty($time_card_record)){
                return null;
            }
            $this->ensureCanApproveWholeTimecard($active_user, $time_card_record->loadMissing(['user', 'project_segments']));
            if((int) $time_card_record->status_flag !== timecardRecord::STATUS_APPROVED){
                return $time_card_record;
            }
            $segments = $time_card_record->project_segments()->get();
            $isAdmin = $active_user->isAdmin();
            if ($segments->isNotEmpty() && !$isAdmin) {
                throw ValidationException::withMessages([
                    'message' => 'プロジェクト別の承認取消を行ってください。',
                ]);
            }
            if ($segments->isNotEmpty() && $segments->contains(fn ($segment) => $segment->status !== TimecardProjectSegment::STATUS_APPROVED)) {
                throw ValidationException::withMessages([
                    'message' => 'プロジェクト別時間がすべて承認済みの場合のみ日報承認取消できます。',
                ]);
            }

            $beforeState = $this->timecardAuditLogService->trackedTimecardState($time_card_record);
            $time_card_record->approved_by = null;
            $time_card_record->status_flag = timecardRecord::STATUS_SUBMITTED;
            $time_card_record->save();
            $time_card_record->project_segments()
                ->where('status', TimecardProjectSegment::STATUS_APPROVED)
                ->update([
                    'status' => TimecardProjectSegment::STATUS_SUBMITTED,
                    'approved_by' => null,
                    'approved_at' => null,
                    'approval_source' => null,
                ]);
            $this->timecardAuditLogService->logTimecardEvent(
                'timecard_approval_cancelled',
                $time_card_record,
                (int) $request->user_id,
                $beforeState,
                $this->timecardAuditLogService->trackedTimecardState($time_card_record),
                ['actor_id' => $active_user->id]
            );
            return $time_card_record->fresh();
        });
        

        return response()->json($time_card_record);

    }
    public function attendanceConfirm(Request $request){
        
        $active_user = $this->active_user();
        $user_list = $request->user_id ?? [$active_user->id];
        $date = $request->date_year_month;
            
        $result = $this->attendanceService->confirm($user_list, $date);
        return response()->json($result);
        
    }
    public function attendanceDelete(Request $request){
        
        $attendance_record = attendanceRecord::where('user_id', $request->user_id)->where('date_year_month', $request->date_year_month)->first();
        if(!empty($attendance_record)){
            $attendance_record->delete();
        }

        return 'deleted';
    }
    
    public function attendanceClose(Request $request){
        $user_id = $request->user['id'];

        $attendance_record = attendanceRecord::where('user_id', '=' , $user_id )->where('date_year_month', '=' , $request->date_year_month )->first();
        $active_user = $this->active_user();
        $work_type_flag = $request->user['work_type'];
        $work_type = $work_type_flag == 0 ? 'フレックス' : '通常';
        $user_code = $request->user->user_code ?? 99999999;
        if(empty($attendance_record)){
            $attendance_record = new attendanceRecord;
            $attendance_record->user_id = $user_id;
            $attendance_record->confirmed_by = $active_user->id;
            $attendance_record->name = $request->user['name'];
            $attendance_record->user_code = $user_code;
            $attendance_record->date_year_month = $request->date;
            $attendance_record->work_type = $work_type;
            $attendance_record->save();

        }
        return response()->json($request);
    }
    public function one_shot_confirmation(Request $request) {
        $user_ids = $request->user_ids;
        $date_year_month = $request->month;
        $result = $this->attendanceService->confirm($user_ids, $date_year_month);
        return response()->json($result);
    }
    public function request_overtime(Request $request){
        $request->validate([
            'record_id' => 'required',
            'minutes' => 'nullable|integer|min:0',
            'overtime_content' => 'nullable|string',
            'project_segments' => 'nullable|array',
            'project_segments.*.project_id' => 'nullable|integer',
            'project_segments.*.minutes' => 'nullable|integer|min:0',
            'project_segments.*.content' => 'nullable|string|max:2000',
        ]);
        $shift = shiftRecord::findOrFail($request->record_id);

        $projectSegments = $this->sanitizeOvertimeProjectSegments($request, $shift);
        if (collect($projectSegments)->contains(fn ($segment) => blank($segment['content'] ?? null))) {
            throw ValidationException::withMessages([
                'message' => '残業プロジェクトごとに作業内容を入力してください。',
            ]);
        }
        $minutes = count($projectSegments)
            ? collect($projectSegments)->sum('minutes')
            : max(0, (int) ($request->minutes ?? 0));
        $content = $this->overtimeRequestContentSummary($projectSegments, $request->overtime_content);

        $rec = ShiftOvertimeRequest::updateOrCreate([
            'record_id' => $request->record_id,
        ], [
            "minutes" => $minutes,
            "project_segments" => $projectSegments,
            "content" => $content,
            "status" => $request->status ?? 1,
            "user_id" => $shift->user_id,
            "created_by" => $request->created_by ? $request->created_by : Auth::id(),
            "approved_by" => null,
            "overtime_day" => $request->overtime_day ?? $shift->shift_day,
        ]);

        return response()->json($rec);
    }
    public function delete_overtime(Request $request){
        $request->validate([
            'id' => 'required',
        ]);
        $exec = ShiftOvertimeRequest::findOrFail($request->id)->delete();

        return response()->json($exec);
    }
    public function respond_overtime(Request $request){
        $request->validate([
            'id' => 'required',
            'status' => 'required|integer|in:0,1,2',
            'segment_index' => 'nullable|integer|min:0',
            'project_id' => 'nullable|integer',
        ]);
        $user = $this->active_user();
        $overtimeRequest = ShiftOvertimeRequest::findOrFail($request->id);
        $status = (int) $request->status;
        $segments = is_array($overtimeRequest->project_segments) ? array_values($overtimeRequest->project_segments) : [];

        if ($request->has('segment_index') || $request->filled('project_id')) {
            $index = $request->has('segment_index')
                ? (int) $request->segment_index
                : collect($segments)->search(fn ($segment) => (int) ($segment['project_id'] ?? 0) === (int) $request->project_id);

            if ($index === false || !array_key_exists($index, $segments)) {
                throw ValidationException::withMessages([
                    'message' => '対象の残業プロジェクトが見つかりません。',
                ]);
            }

            $projectId = (int) ($segments[$index]['project_id'] ?? 0);
            if ($projectId <= 0 || ($request->filled('project_id') && $projectId !== (int) $request->project_id)) {
                throw ValidationException::withMessages([
                    'message' => '対象の残業プロジェクトが一致しません。',
                ]);
            }

            $this->ensureOvertimeProjectSegmentApprover($user, $overtimeRequest, $projectId);

            $segments[$index]['status'] = $status;
            if ($status === 2) {
                $segments[$index]['approved_by'] = $user->id;
                $segments[$index]['approved_at'] = now()->toDateTimeString();
                unset($segments[$index]['rejected_by'], $segments[$index]['rejected_at']);
            } elseif ($status === 0) {
                $segments[$index]['rejected_by'] = $user->id;
                $segments[$index]['rejected_at'] = now()->toDateTimeString();
                unset($segments[$index]['approved_by'], $segments[$index]['approved_at']);
            } else {
                unset(
                    $segments[$index]['approved_by'],
                    $segments[$index]['approved_at'],
                    $segments[$index]['rejected_by'],
                    $segments[$index]['rejected_at']
                );
            }

            $segments = array_map(fn ($segment) => $this->overtimeSegmentWithStatus($segment), $segments);
            $overtimeRequest->project_segments = $segments;
            $overtimeRequest->status = $this->deriveOvertimeRequestStatus($segments, $status);
            $overtimeRequest->approved_by = $overtimeRequest->status === 2 ? $user->id : null;
            $overtimeRequest->save();

            return response()->json([
                'data' => $overtimeRequest->fresh(),
            ]);
        }

        if (!empty($segments)) {
            foreach ($segments as $segment) {
                $projectId = (int) ($segment['project_id'] ?? 0);
                if ($projectId > 0) {
                    $this->ensureOvertimeProjectSegmentApprover($user, $overtimeRequest, $projectId);
                }
            }

            $segments = array_map(function ($segment) use ($status, $user) {
                $segment['status'] = $status;
                if ($status === 2) {
                    $segment['approved_by'] = $user->id;
                    $segment['approved_at'] = now()->toDateTimeString();
                    unset($segment['rejected_by'], $segment['rejected_at']);
                } elseif ($status === 0) {
                    $segment['rejected_by'] = $user->id;
                    $segment['rejected_at'] = now()->toDateTimeString();
                    unset($segment['approved_by'], $segment['approved_at']);
                } else {
                    unset($segment['approved_by'], $segment['approved_at'], $segment['rejected_by'], $segment['rejected_at']);
                }

                return $this->overtimeSegmentWithStatus($segment);
            }, $segments);
            $overtimeRequest->project_segments = $segments;
        }

        $overtimeRequest->status = $this->deriveOvertimeRequestStatus($segments, $status);
        $overtimeRequest->approved_by = $overtimeRequest->status === 2 ? ($request->approved_by ?? $user->id) : null;
        $exec = $overtimeRequest->save();
        

        return response()->json([
            'data' => $exec ? $overtimeRequest->fresh() : null
        ]);
    }
    public function work_badge(Request $request){
        $user = $this->active_user();
        if($user->work_authority == 1){
            $ids = [608, 610, $user->id];
            $work_group_list = workGroup::whereHas('members', function($q) use($user) {
                $q->whereIn('users.id', [$user->id]);
            })->with(['members' => function($q) use($ids) {
                $q->whereNotIn('users.id', $ids);
            }])
            ->get();
            $work_group_users = $work_group_list->flatMap(function ($work_group_list_value) {
                return $work_group_list_value->members;
            })->unique('id')->pluck('id')->toArray();
            $today = Carbon::now()->format('Y-m-d');
            $overtime = shiftRecord::where('shift_day', '>=', $today)->whereIn('user_id', $work_group_users)->
            whereHas('overtime_request', function ($q){
                $q->where('status', 1);
            })
            ->with('overtime_request')
            ->get();
            return response()->json($overtime);
        }
        return response()->json([]);
    }
    private function path_generator(){
        $timestamp = time();
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < 5; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        $iconId = $timestamp . $randomString;
        if (strlen($iconId) > 15) {
            $iconId = substr($iconId, 0, 15);
        }    
        return $iconId;
    }
    private function ensureEditableTimecard(?timecardRecord $timecard): void
    {
        if ($timecard && (int) $timecard->status_flag === timecardRecord::STATUS_APPROVED) {
            throw ValidationException::withMessages(['message' => '承認済みの日報は編集できません。']);
        }
    }
    private function ensureDeletableTimecard(?timecardRecord $timecard): void
    {
        $this->ensureEditableTimecard($timecard);

        if ($this->hasLockedProjectSegments($timecard)) {
            throw ValidationException::withMessages([
                'message' => '申請中または承認済みのプロジェクト時間がある日報は削除できません。差戻されたプロジェクトのみ修正してください。',
            ]);
        }
    }

    private function statesDiffer(?array $beforeState, ?array $afterState): bool
    {
        return json_encode($beforeState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            !== json_encode($afterState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function normalizeReceiptDateValue(mixed $rawReceiptDate): ?string
    {
        if (blank($rawReceiptDate)) {
            return null;
        }

        $value = trim((string) $rawReceiptDate);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        if (preg_match('/(\d{4}-\d{2}-\d{2})/', $value, $matches)) {
            return $matches[1];
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages(['message' => '領収書日付の形式が不正です。']);
        }
    }

    private function mapIncomingCostAttributes(array $cost, Request $request, timecardRecord $timecard, string $yearMonth): array
    {
        $receiptDate = $this->normalizeReceiptDateValue(Arr::get($cost, 'receipt_date'));
        $draftUuid = Arr::get($cost, 'draft_uuid') ?: (string) Str::uuid();
        $transportType = Arr::get($cost, 'transport_type');
        $departurePlace = trim((string) Arr::get($cost, 'departure_place', ''));
        $arrivalPlace = trim((string) Arr::get($cost, 'arrival_place', ''));
        $content = Arr::get($cost, 'content');

        if ((int) Arr::get($cost, 'type') === 4) {
            $content = $this->buildTransportContent($transportType, $departurePlace, $arrivalPlace);
        }

        return [
            'record_id' => $timecard->id,
            'user_id' => $request->userId,
            'draft_uuid' => $draftUuid,
            'file_path' => Arr::get($cost, 'file_path'),
            'receipt_file_id' => Arr::get($cost, 'receipt_file_id'),
            'type' => Arr::get($cost, 'type'),
            'transport_type' => $transportType ?: null,
            'departure_place' => $departurePlace ?: null,
            'arrival_place' => $arrivalPlace ?: null,
            'date_month' => $yearMonth,
            'content' => $content ?: null,
            'expenses' => Arr::get($cost, 'expenses'),
            'department' => Arr::get($cost, 'department'),
            'project_id' => filled(Arr::get($cost, 'project_id')) ? (int) Arr::get($cost, 'project_id') : null,
            'timecard_project_segment_id' => filled(Arr::get($cost, 'timecard_project_segment_id')) ? (int) Arr::get($cost, 'timecard_project_segment_id') : null,
            'merchant_name' => Arr::get($cost, 'merchant_name'),
            'receipt_date' => $receiptDate,
            'currency' => Arr::get($cost, 'currency', 'JPY') ?: 'JPY',
            'receipt_source_type' => Arr::get($cost, 'receipt_source_type', 'paper_scan') ?: 'paper_scan',
            'file_original_name' => Arr::get($cost, 'file_original_name'),
            'file_mime_type' => Arr::get($cost, 'file_mime_type'),
            'file_size_bytes' => Arr::get($cost, 'file_size_bytes'),
            'file_sha256' => Arr::get($cost, 'file_sha256'),
            'file_uploaded_at' => Arr::get($cost, 'file_uploaded_at'),
            'scan_dpi' => Arr::get($cost, 'scan_dpi'),
            'scan_color_depth' => Arr::get($cost, 'scan_color_depth'),
            'scan_color_mode' => Arr::get($cost, 'scan_color_mode'),
            'document_size' => Arr::get($cost, 'document_size'),
            'image_width_px' => Arr::get($cost, 'image_width_px'),
            'image_height_px' => Arr::get($cost, 'image_height_px'),
        ];
    }

    private function finalizeReceiptForCost(array $incomingCost, timecardCostRecord $savedCost): void
    {
        $this->timecardReceiptStorageService->finalizeReceipt(
            $savedCost,
            Arr::get($incomingCost, 'receipt_file_id'),
            Arr::get($incomingCost, 'file_path'),
            Arr::get($incomingCost, 'draft_uuid'),
            [
                'scan_dpi' => Arr::get($incomingCost, 'scan_dpi'),
                'scan_color_depth' => Arr::get($incomingCost, 'scan_color_depth'),
                'scan_color_mode' => Arr::get($incomingCost, 'scan_color_mode'),
                'document_size' => Arr::get($incomingCost, 'document_size'),
                'image_width_px' => Arr::get($incomingCost, 'image_width_px'),
                'image_height_px' => Arr::get($incomingCost, 'image_height_px'),
            ]
        );
    }

    private function buildTransportContent($transportType, string $departurePlace, string $arrivalPlace): ?string
    {
        $route = collect([$departurePlace, $arrivalPlace])
            ->filter(fn ($value) => filled($value))
            ->implode(' → ');

        if ($route === '') {
            return null;
        }

        $transportLabels = [
            1 => '電車のみ',
            2 => '電車・バス',
            3 => 'タクシー',
            4 => '飛行機',
            5 => 'その他',
        ];

        $transportLabel = $transportLabels[(int) $transportType] ?? null;
        if ($transportLabel) {
            return "{$transportLabel} / {$route}";
        }

        return $route;
    }
    private function syncAppliedOcrRun(array $cost, timecardCostRecord $savedCost, timecardRecord $timecard, int $subjectUserId, ?array $beforeState, ?array $afterState): void
    {
        $ocrRunId = Arr::get($cost, 'ocr_run_id');
        $appliedFields = array_values(array_filter(Arr::wrap(Arr::get($cost, 'ocr_applied_fields', []))));
        if (!$ocrRunId || empty($appliedFields)) {
            return;
        }

        $ocrRun = TimecardCostOcrRun::find($ocrRunId);
        if (!$ocrRun) {
            return;
        }

        $alreadyApplied = $ocrRun->applied_at !== null;
        $ocrRun->timecard_record_id = $timecard->id;
        $ocrRun->timecard_cost_record_id = $savedCost->id;
        if (!$alreadyApplied) {
            $ocrRun->applied_by_user_id = Auth::id();
            $ocrRun->applied_at = now();
        }
        $ocrRun->save();

        if ($alreadyApplied) {
            return;
        }

        $fieldLevelBefore = $beforeState ? Arr::only($beforeState, $appliedFields) : [];
        $fieldLevelAfter = $afterState ? Arr::only($afterState, $appliedFields) : [];
        $this->timecardAuditLogService->logOcrEvent(
            'ocr_applied',
            $ocrRun,
            $subjectUserId,
            $timecard,
            $savedCost,
            $fieldLevelBefore ?: null,
            $fieldLevelAfter ?: null,
            ['applied_fields' => $appliedFields]
        );
    }
    private function fileHash(string $filePath): ?string
    {
        $storagePath = storage_path("app/timecard_files/{$filePath}");
        if (!file_exists($storagePath)) {
            return null;
        }

        return hash_file('sha256', $storagePath) ?: null;
    }
    public function work_file_upload(Request $request){
        $request->validate([
            'file' => 'required|file|mimes:jpeg,jpg,png,gif,webp,svg,pdf|max:10240',
            'draft_uuid' => 'required|uuid',
            'subject_user_id' => 'nullable|integer',
            'timecard_record_id' => 'nullable|integer',
        ]);

        $timecard = $request->timecard_record_id ? timecardRecord::find($request->timecard_record_id) : null;
        $this->ensureEditableTimecard($timecard);
        $fileContent = $request->file('file');
        $subjectUserId = (int) ($request->subject_user_id ?: Auth::id());
        $receiptFile = $this->timecardReceiptStorageService->storePending(
            $fileContent,
            $request->draft_uuid,
            $subjectUserId,
            $timecard
        );
        $file_path = $receiptFile->canonical_path;
        $mime_type = $receiptFile->mime_type ?: $fileContent->getMimeType();
        $mime_type_array = explode('/', (string) $mime_type);
        $file_type = $mime_type_array[0] ?? 'application';
        $metadata = [
            'receipt_file_id' => $receiptFile->id,
            'file_path' => $file_path,
            'preview_path' => $receiptFile->preview_path,
            'file_original_name' => $receiptFile->original_name,
            'file_mime_type' => $mime_type,
            'file_size_bytes' => $receiptFile->size_bytes,
            'file_sha256' => $receiptFile->sha256,
            'file_uploaded_at' => $receiptFile->uploaded_at?->toDateTimeString(),
            'scan_dpi' => $receiptFile->scan_dpi,
            'scan_color_depth' => $receiptFile->scan_color_depth,
            'scan_color_mode' => $receiptFile->scan_color_mode,
            'document_size' => $receiptFile->document_size,
            'image_width_px' => $receiptFile->image_width_px,
            'image_height_px' => $receiptFile->image_height_px,
        ];
        $this->timecardAuditLogService->logReceiptEvent(
            'receipt_uploaded',
            $subjectUserId,
            $request->draft_uuid,
            $timecard,
            null,
            null,
            ['file_path' => $file_path],
            $metadata
        );
        $data = [
            "receipt_file_id" => $receiptFile->id,
            "file_path" => $file_path,
            "preview_path" => $receiptFile->preview_path,
            "file_type" => $file_type,
            "file_extension" => $receiptFile->extension,
            "file_original_name" => $metadata['file_original_name'],
            "file_mime_type" => $metadata['file_mime_type'],
            "file_size_bytes" => $metadata['file_size_bytes'],
            "file_sha256" => $metadata['file_sha256'],
            "file_uploaded_at" => $metadata['file_uploaded_at'],
            "scan_dpi" => $metadata['scan_dpi'],
            "scan_color_depth" => $metadata['scan_color_depth'],
            "scan_color_mode" => $metadata['scan_color_mode'],
            "document_size" => $metadata['document_size'],
            "image_width_px" => $metadata['image_width_px'],
            "image_height_px" => $metadata['image_height_px'],
            "scan_warnings" => $this->timecardReceiptStorageService->scanWarnings($receiptFile),
        ];
        return response()->json($data); 
    }
    public function work_file_delete(Request $request)
    {
        $request->validate([
            'draft_uuid' => 'required|uuid',
            'file_path' => 'required|string',
            'receipt_file_id' => 'nullable|integer',
            'subject_user_id' => 'nullable|integer',
            'timecard_record_id' => 'nullable|integer',
            'timecard_cost_record_id' => 'nullable|integer',
        ]);

        $timecard = $request->timecard_record_id ? timecardRecord::find($request->timecard_record_id) : null;
        $cost = $request->timecard_cost_record_id ? timecardCostRecord::find($request->timecard_cost_record_id) : null;
        $this->ensureEditableTimecard($timecard ?: $cost?->timecard);

        $subjectUserId = (int) ($request->subject_user_id ?: Auth::id());
        $beforeState = ['file_path' => $request->file_path];
        $this->timecardReceiptStorageService->logicalDeleteByReference(
            $request->receipt_file_id,
            $request->file_path,
            $request->draft_uuid,
            Auth::id()
        );
        $this->timecardAuditLogService->logReceiptEvent(
            'receipt_removed',
            $subjectUserId,
            $request->draft_uuid,
            $timecard ?: $cost?->timecard,
            $cost,
            $beforeState,
            ['file_path' => null],
            ['file_path' => $request->file_path]
        );

        return response()->json(['success' => true]);
    }
    public function work_receipt_ocr(Request $request)
    {
        $validated = $request->validate([
            'draft_uuid' => 'required|uuid',
            'file_path' => 'required|string',
            'expense_type' => 'nullable|integer',
            'subject_user_id' => 'nullable|integer',
            'timecard_record_id' => 'nullable|integer',
            'timecard_cost_record_id' => 'nullable|integer',
        ]);

        $timecard = !empty($validated['timecard_record_id']) ? timecardRecord::find($validated['timecard_record_id']) : null;
        $cost = !empty($validated['timecard_cost_record_id']) ? timecardCostRecord::find($validated['timecard_cost_record_id']) : null;
        $this->ensureEditableTimecard($timecard ?: $cost?->timecard);

        $subjectUserId = (int) ($validated['subject_user_id'] ?? Auth::id());
        $ocrRun = new TimecardCostOcrRun([
            'timecard_record_id' => $timecard?->id ?? $cost?->record_id,
            'timecard_cost_record_id' => $cost?->id,
            'draft_uuid' => $validated['draft_uuid'],
            'source_file_path' => $validated['file_path'],
            'source_file_sha256' => $this->fileHash($validated['file_path']),
            'executed_by_user_id' => Auth::id(),
        ]);

        try {
            $ocrResponse = $this->workReceiptOcrService->extract(
                $validated['file_path'],
                isset($validated['expense_type']) ? (int) $validated['expense_type'] : null
            );
            $ocrRun->fill([
                'provider' => Arr::get($ocrResponse, 'provider', 'gemini'),
                'model' => Arr::get($ocrResponse, 'model'),
                'status' => 'completed',
                'normalized_result' => Arr::get($ocrResponse, 'normalized_result'),
                'raw_response' => Arr::get($ocrResponse, 'raw_response'),
            ]);
            $ocrRun->save();

            $this->timecardAuditLogService->logOcrEvent(
                'ocr_extracted',
                $ocrRun,
                $subjectUserId,
                $timecard ?: $cost?->timecard,
                $cost,
                null,
                Arr::get($ocrResponse, 'normalized_result')
            );

            return response()->json([
                'ocr_run_id' => $ocrRun->id,
                'suggestions' => Arr::get($ocrResponse, 'normalized_result', []),
                'multiple_receipts_detected' => Arr::get($ocrResponse, 'multiple_receipts_detected', false),
                'status' => 'completed',
            ]);
        } catch (\Throwable $exception) {
            $ocrRun->fill([
                'provider' => 'gemini',
                'model' => config('services.google.receipt_ocr_model'),
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
            $ocrRun->save();

            $this->timecardAuditLogService->logOcrEvent(
                'ocr_failed',
                $ocrRun,
                $subjectUserId,
                $timecard ?: $cost?->timecard,
                $cost,
                null,
                null,
                ['error_message' => $exception->getMessage()]
            );

            throw $exception;
        }
    }
    public function work_cost_delete(Request $request){
        $request->validate([
            'id' => 'required',
        ]);
        $workCost = timecardCostRecord::with('timecard')->findOrFail($request->id);
        $this->ensureEditableTimecard($workCost->timecard);
        $beforeState = $this->timecardAuditLogService->trackedCostState($workCost);
        $this->timecardReceiptStorageService->logicalDeleteByReference(
            $workCost->receipt_file_id,
            $workCost->file_path,
            $workCost->draft_uuid,
            Auth::id()
        );
        $workCost->delete();
        if ($workCost->timecard) {
            $this->timecardAuditLogService->logCostEvent(
                'cost_deleted',
                $workCost->timecard,
                $workCost,
                (int) $workCost->user_id,
                $beforeState,
                null,
                ['source' => 'work_cost_delete']
            );
        }

        return response()->json($workCost);
    }
    public function work_incentive_delete(Request $request){
        $request->validate([
            'id' => 'required',
        ]);
        $workCost = timecardIncentive::findOrFail($request->id)->delete();

        return response()->json($workCost);
    }
    public function next_month_shift(Request $request){
        $currentDate = Carbon::now();
        $dayOfMonth = $currentDate->day;
        if($dayOfMonth >= 25){
            $nextMonthDate = $currentDate->addMonthNoOverflow();
            $nextMonthYear = $nextMonthDate->year;
            $nextMonth = $nextMonthDate->month;
            $auth_user = $this->active_user();
            $shiftNotSubmittedList = [];
            if($auth_user->isAdmin() || in_array($auth_user->position_id, [1, 2, 3, 4, 5, 14, null])){
                
                return response()->json();
            }
            $nextMonthShift = shiftRecord::whereYear('shift_day', $nextMonthYear)
                                        ->whereMonth('shift_day', $nextMonth)
                                        ->where('user_id', $auth_user->id)->get();
            $numberOfDays = cal_days_in_month(CAL_GREGORIAN, $nextMonth, $nextMonthYear);

            if(count($nextMonthShift) < $numberOfDays){
                $shiftNotSubmittedList[] = [
                    'year' => $nextMonthYear, 
                    'month' => (int) $nextMonth ,
                ];
            }
            return response()->json($shiftNotSubmittedList);
        }
        return response()->json([]);
    }         

    private function buildProjectSelectOptionTotalsByUser($cases): array
    {
        $countsByUser = [];

        foreach ($cases as $case) {
            $userId = (int) $case->user_id;
            $countsByUser[$userId] ??= [];
            $this->addCaseSelectOptionCounts($countsByUser[$userId], $case);
        }

        return collect($countsByUser)
            ->map(fn ($counts) => $this->summarizeSelectOptionCounts($counts))
            ->all();
    }

    private function addCaseSelectOptionCounts(array &$counts, ProjectCase $case): void
    {
        if (empty($case->meta) || !is_array($case->meta)) {
            return;
        }

        foreach ($this->caseSelectFieldLabels($case) as $fieldLabel) {
            $value = $case->meta[$fieldLabel] ?? null;
            $values = is_array($value) ? $value : [$value];

            foreach ($values as $option) {
                $option = trim((string) $option);
                if ($option === '') {
                    continue;
                }

                $counts[$fieldLabel][$option] = ($counts[$fieldLabel][$option] ?? 0) + 1;
            }
        }
    }

    private function caseSelectFieldLabels(ProjectCase $case): array
    {
        $statuses = $case->project?->actual_statuses ?? [];
        if (!is_array($statuses)) {
            return [];
        }

        $caseStatus = $case->status ?? '実績';
        $matchedStatus = collect($statuses)->first(function ($status) use ($caseStatus) {
            return ($status['label'] ?? $status['custom_label'] ?? null) === $caseStatus;
        });
        $sourceStatuses = $matchedStatus ? [$matchedStatus] : $statuses;

        return collect($sourceStatuses)
            ->flatMap(function ($status) {
                return collect($status['extra_fields'] ?? [])
                    ->filter(fn ($field) => ($field['type'] ?? null) === 'select')
                    ->pluck('label');
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function summarizeSelectOptionCounts(array $counts): array
    {
        $summary = [];

        foreach ($counts as $fieldLabel => $optionCounts) {
            if (empty($optionCounts)) {
                continue;
            }

            arsort($optionCounts);
            $option = array_key_first($optionCounts);
            if ($option === null) {
                continue;
            }

            $summary[] = [
                'field_label' => $fieldLabel,
                'option' => $option,
                'count' => $optionCounts[$option],
            ];
        }

        return $summary;
    }

    private function projectUnitLabel(?ProjectRecord $project): string
    {
        $unitLabel = [
            'COUNT' => '件',
            'HOUR' => '時間',
            'JPY' => '円',
            'CUSTOM' => '',
        ];
        $unitId = $project->unit_id ?? 'JPY';

        if ($unitId === 'CUSTOM') {
            return $project->custom_unit_label ?? '単位';
        }

        return $unitLabel[$unitId] ?? '円';
    }

    private function formatMinutesForCsv(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return $hours . '時間' . $remainingMinutes . '分';
    }

    private function formatSelectOptionSummary(array $counts): string
    {
        return collect($this->summarizeSelectOptionCounts($counts))
            ->map(fn ($row) => "{$row['field_label']}: {$row['option']} ({$row['count']}件)")
            ->join("\n");
    }

    private function costTypeLabel(?int $type): string
    {
        return [
            1 => '交通費',
            2 => '通信費',
            3 => '宿泊費',
            4 => '旅費交通費',
            5 => '消耗品費',
            6 => '交際費',
            7 => '支払手数料',
            8 => '福利厚生費',
        ][$type] ?? '経費';
    }

    private function vehicleLabel(?int $vehicle): string
    {
        return [
            0 => '福岡582く5617 ホンダライフ',
            1 => '福岡582え8686 ダイハツミラ',
            2 => '福岡580と5654 オッティ',
            3 => '福岡480わ3206 クリッパー',
            4 => '福岡480ね5019 バン',
            5 => '福岡480ね5020 バン',
            6 => '鹿児島582そ6650 ミライース',
            7 => '福岡582ち7350',
            8 => 'なにわ502の1116',
            9 => '大阪581わ707（ﾚﾝﾀｶｰ）',
            10 => '福岡582て7672',
            11 => '長崎581つ9501',
            12 => '福岡582た8963',
            13 => '大分581な4912',
            14 => '鹿児島582そ8143',
            15 => 'レンタカー',
            16 => 'マイカー',
            17 => '弘前580い7009',
            18 => '弘前580い7008',
            19 => '仙台580よ8134',
            20 => '郡山580け8503',
            22 => '愛媛581な1880',
            23 => '盛岡580さ6353',
            24 => '福岡582そ1234',
            25 => '仙台580ひ6191',
            27 => 'なにわ581き9917',
            28 => '久留米581と3345',
        ][$vehicle] ?? '';
    }

    private function projectSegmentTypeLabel(?string $segmentType): string
    {
        return $segmentType === TimecardProjectSegment::TYPE_TRAINING ? '研修' : '就業';
    }

    private function workProjectDetailCsv(int $year, int $month, array $usersList)
    {
        $users = User::whereIn('id', $usersList)
            ->with(['time_card_records' => function ($q) use ($year, $month) {
                $q->whereYear('day', $year)
                    ->whereMonth('day', $month)
                    ->with([
                        'custom_field_data_records' => function ($q) {
                            $q->whereIn('type_id', [37, 40, 39, 42])
                                ->orderBy('created_at', 'desc')
                                ->select('id', 'table_record_id', 'type_id', 'value_text', 'value_int', 'date', 'label', 'user_id');
                        },
                        'timecard_costs',
                        'department:id,name,unit_id,custom_unit_label',
                        'project_segments.project:id,name,unit_id,custom_unit_label',
                        'project_case.project:id,unit_id,custom_unit_label,actual_statuses',
                        'vehicle_data',
                        'vehicle_records',
                    ])
                    ->select(
                        'id',
                        'break_time',
                        'end_time',
                        'day',
                        'over_time',
                        'stamp_flag',
                        'start_time',
                        'status_flag',
                        'work_time',
                        'user_id',
                        'car_mileage',
                        'car_used_project',
                        'gas_full_price',
                        'work_group_id'
                    );
            }])
            ->get();

        $rows = [];

        foreach ($users as $user) {
            $userTotals = [
                'minutes' => 0,
                'cost_count' => 0,
                'cost_sum' => 0,
                'mileage' => 0,
                'gas' => 0,
                'actuals' => [],
            ];
            $userHasRows = false;

            foreach ($user->time_card_records->sortBy('day') as $timecard) {
                $segments = $timecard->project_segments;
                $hasRealProjectSegments = $segments->isNotEmpty();
                if ($segments->isEmpty() && ($timecard->department || $timecard->work_group_id)) {
                    $segments = collect([(object) [
                        'id' => null,
                        'project_id' => $timecard->work_group_id,
                        'segment_type' => TimecardProjectSegment::TYPE_WORK,
                        'project' => $timecard->department,
                        'start_time' => $timecard->start_time,
                        'end_time' => $timecard->end_time,
                        'minutes' => $timecard->work_time,
                        'status' => null,
                        'details' => [],
                        'detail_values' => [],
                        'comment' => null,
                    ]]);
                }

                $hasStoredDetails = $segments->contains(fn ($segment) => !empty($segment->details));
                $firstDetailIndexes = [];
                foreach ($segments->values() as $detailIndex => $detailSegment) {
                    foreach ((array) ($detailSegment->details ?? []) as $detail) {
                        $firstDetailIndexes[$detail] ??= $detailIndex;
                    }
                }
                $allowances = $timecard->custom_field_data_records->where('type_id', 37)->pluck('label')->filter()->implode(' ');
                $incident = $timecard->custom_field_data_records->firstWhere('type_id', 40);
                $comment = $timecard->custom_field_data_records->firstWhere('type_id', 39);
                $overtimeReason = $timecard->custom_field_data_records->firstWhere('type_id', 42);

                foreach ($segments as $index => $segment) {
                    $projectName = $segment->project?->name ?? $timecard->department?->name ?? '';
                    $projectId = (int) ($segment->project_id ?? $segment->project?->id ?? 0);
                    if (!$projectId && $projectName === '') {
                        continue;
                    }
                    $details = is_array($segment->details ?? null) ? $segment->details : [];
                    $detailValues = is_array($segment->detail_values ?? null) ? $segment->detail_values : [];
                    $projectComment = trim((string) ($segment->comment ?? ''));
                    $legacyComment = trim($comment?->value_text ?? '');
                    $legacyFirstSegment = !$hasRealProjectSegments && !$hasStoredDetails && $index === 0;
                    $firstProjectSegmentIndex = $segments->search(fn ($candidate) => (int) ($candidate->project_id ?? $candidate->project?->id ?? 0) === $projectId);
                    $isFirstProjectSegment = $firstProjectSegmentIndex === $index;
                    $segmentCosts = $timecard->timecard_costs->filter(function ($cost) use ($segment, $projectId, $projectName, $isFirstProjectSegment) {
                        $linkedSegmentId = (int) ($cost->timecard_project_segment_id ?? 0);
                        if ($linkedSegmentId > 0) {
                            return $linkedSegmentId === (int) ($segment->id ?? 0);
                        }

                        if (!$isFirstProjectSegment) {
                            return false;
                        }

                        $costProjectId = (int) ($cost->project_id ?? 0);
                        if ($costProjectId > 0) {
                            return $costProjectId === $projectId;
                        }

                        return $cost->department === $projectName;
                    });
                    $segmentCases = $isFirstProjectSegment
                        ? $timecard->project_case->filter(fn ($case) => (int) $case->project_record_id === $projectId)
                        : collect();
                    $segmentMileage = is_array($detailValues['mileage'] ?? null) ? $detailValues['mileage'] : null;
                    $segmentVehicle = is_array($detailValues['vehicle'] ?? null) ? $this->vehicleLabel($detailValues['vehicle']['vehicle'] ?? null) : '';
                    $mileageMatches = $segmentMileage !== null || (!$hasRealProjectSegments && (int) $timecard->car_used_project === $projectId);
                    $segmentAllowance = isset($detailValues['allowance_labels']) && is_array($detailValues['allowance_labels'])
                        ? implode(' ', array_filter($detailValues['allowance_labels'], fn ($value) => filled($value)))
                        : '';
                    $segmentIncident = trim((string) ($detailValues['incident'] ?? ''));
                    $segmentOvertime = trim((string) ($detailValues['overtime'] ?? ''));
                    $allowanceText = '';
                    if (in_array('allowance', $details, true) || $legacyFirstSegment) {
                        $allowanceText = $segmentAllowance !== '' ? $segmentAllowance : (((int) ($firstDetailIndexes['allowance'] ?? -1) === $index || $legacyFirstSegment) ? $allowances : '');
                    }
                    $incidentText = '';
                    if (in_array('incident', $details, true) || $legacyFirstSegment) {
                        $incidentText = $segmentIncident !== '' ? $segmentIncident : (((int) ($firstDetailIndexes['incident'] ?? -1) === $index || $legacyFirstSegment) ? ($incident?->value_text ?? $incident?->label ?? '') : '');
                    }
                    $vehicleText = '';
                    if (in_array('vehicle', $details, true) || $legacyFirstSegment) {
                        $vehicleText = $segmentVehicle !== ''
                            ? $segmentVehicle
                            : (((int) ($firstDetailIndexes['vehicle'] ?? -1) === $index || $legacyFirstSegment)
                            ? $this->vehicleLabel($timecard->vehicle_data?->vehicle)
                            : '');
                    }

                    $segmentMinutes = (int) ($segment->minutes ?? 0);
                    $segmentCostCount = $segmentCosts->count();
                    $segmentCostSum = $segmentCosts->sum(fn ($cost) => (int) $cost->expenses);
                    $segmentMileageDistance = $mileageMatches ? (int) ($segmentMileage['mileage'] ?? $timecard->car_mileage) : '';
                    $segmentGasCost = $mileageMatches ? (int) ($segmentMileage['gas_full_price'] ?? $timecard->gas_full_price) : '';
                    $segmentActualText = $segmentCases->map(function ($case) {
                        $label = $this->projectUnitLabel($case->project);
                        return ($case->status ?? '実績') . ': ' . $case->amount . $label;
                    })->implode("\n");

                    $userHasRows = true;
                    $userTotals['minutes'] += $segmentMinutes;
                    $userTotals['cost_count'] += $segmentCostCount;
                    $userTotals['cost_sum'] += $segmentCostSum;
                    $userTotals['mileage'] += is_numeric($segmentMileageDistance) ? (int) $segmentMileageDistance : 0;
                    $userTotals['gas'] += is_numeric($segmentGasCost) ? (int) $segmentGasCost : 0;
                    foreach ($segmentCases as $case) {
                        if (!filled($case->amount) || !is_numeric($case->amount)) {
                            continue;
                        }

                        $label = $this->projectUnitLabel($case->project);
                        $key = ($case->status ?? '実績') . '|' . $label;
                        $userTotals['actuals'][$key]['status'] = $case->status ?? '実績';
                        $userTotals['actuals'][$key]['unit_label'] = $label;
                        $userTotals['actuals'][$key]['amount'] = ($userTotals['actuals'][$key]['amount'] ?? 0) + (int) $case->amount;
                    }

                    $rows[] = [
                        '日付' => Carbon::parse($timecard->day)->format('Y-m-d'),
                        'メンバー' => $user->name,
                        '区分' => $this->projectSegmentTypeLabel($segment->segment_type ?? TimecardProjectSegment::TYPE_WORK),
                        'プロジェクト' => $projectName,
                        '開始' => $segment->start_time ? substr($segment->start_time, 0, 5) : '',
                        '終了' => $segment->end_time ? substr($segment->end_time, 0, 5) : '',
                        '作業時間' => $this->formatMinutesForCsv($segmentMinutes),
                        '経費件数' => $segmentCostCount,
                        '経費合計' => $segmentCostSum,
                        '経費詳細' => $segmentCosts->map(function ($cost) {
                            $parts = [$this->costTypeLabel((int) $cost->type)];
                            if (filled($cost->content)) {
                                $parts[] = $cost->content;
                            }
                            $parts[] = ((int) $cost->expenses) . '円';
                            return implode(': ', $parts);
                        })->implode("\n"),
                        'マイカー距離' => $segmentMileageDistance,
                        'ガソリン代' => $segmentGasCost,
                        '諸手当' => $allowanceText,
                        '車両使用' => $vehicleText,
                        'インシデント内容' => $incidentText,
                        '時間外業務内容' => in_array('overtime', $details, true) || $legacyFirstSegment
                            ? ($segmentOvertime !== '' ? $segmentOvertime : ($overtimeReason?->value_text ?? ''))
                            : '',
                        '実績' => $segmentActualText,
                        'コメント' => in_array('comment', $details, true) || $legacyFirstSegment
                            ? ($projectComment !== '' ? $projectComment : $legacyComment)
                            : '',
                    ];
                }
            }

            if ($userHasRows) {
                $rows[] = [
                    '日付' => '集計',
                    'メンバー' => $user->name,
                    '区分' => '',
                    'プロジェクト' => '',
                    '開始' => '',
                    '終了' => '',
                    '作業時間' => $this->formatMinutesForCsv($userTotals['minutes']),
                    '経費件数' => $userTotals['cost_count'] ?: '',
                    '経費合計' => $userTotals['cost_sum'] ?: '',
                    '経費詳細' => '',
                    'マイカー距離' => $userTotals['mileage'] ?: '',
                    'ガソリン代' => $userTotals['gas'] ?: '',
                    '諸手当' => '',
                    '車両使用' => '',
                    'インシデント内容' => '',
                    '時間外業務内容' => '',
                    '実績' => collect($userTotals['actuals'])
                        ->map(fn ($row) => "{$row['status']}: {$row['amount']}{$row['unit_label']}")
                        ->values()
                        ->implode("\n"),
                    'コメント' => '',
                ];
            }
        }

        return response()->json($rows);
    }

    public function work_generate_csv(Request $request){
        abort_unless(Auth::check(), 401);

        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'between:1,12'],
            'users' => ['required', 'string'],
            'mode' => ['nullable', 'in:summary,project_detail'],
        ]);

        $activeUser = $this->active_user();
        abort_unless($this->canExportWorkCsv($activeUser), 403, 'CSVを出力する権限がありません。');

        $rawUserIds = collect(explode(',', $data['users']))
            ->map(fn ($id) => trim((string) $id))
            ->filter(fn ($id) => $id !== '');

        if ($rawUserIds->isEmpty() || $rawUserIds->contains(fn ($id) => !ctype_digit($id))) {
            throw ValidationException::withMessages([
                'users' => 'CSV出力対象のメンバーを選択してください。',
            ]);
        }

        $year = (int) $data['year'];
        $month = (int) $data['month'];
        $mode = $data['mode'] ?? 'summary';
        $users_list = $rawUserIds
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($users_list)) {
            throw ValidationException::withMessages([
                'users' => 'CSV出力対象のメンバーを選択してください。',
            ]);
        }

        if ($mode === 'project_detail') {
            return $this->workProjectDetailCsv($year, $month, $users_list);
        }
        $users = User::whereIn('id', $users_list)->with(['time_card_records' => function($q) use($year, $month) {
            $q->whereYear('day', $year)->whereMonth('day', $month)
                ->with(['custom_field_data_records' => function ($q) {
                    $q->whereIn('type_id', [37, 40, 39, 41, 42])->orderBy('created_at', 'desc')->select('id', 'table_record_id', 'type_id', 'value_text', 'value_int', 'date', 'label', 'user_id');
                }])
                ->with(['timecard_costs', 'timecard_incentives', 'department', 'project_segments', 'project_case.project:id,unit_id,custom_unit_label,actual_statuses'])
                ->select('id', 'break_time', 'end_time', 'day', 'over_time', 'stamp_flag', 'start_time', 'status_flag', 'work_time', 'user_id', 'car_mileage', 'work_group_id');
        }])->with(['shift_records' => function ($q) use($year, $month) {
            $q->whereYear('shift_day', $year)->whereMonth('shift_day', $month)
                ->with([
                    'shiftType' => function ($query) {
                        $query->select('id', 'name', 'abbreviation', 'value');
                    },
                    'overtime_request'
                ])
                ->select('id', 'shift_day', 'shift_type', 'user_id', 'start_time', 'end_time', 'status_flag');
        }])->with(['custom_field_data_records' => function ($q) use($year, $month) {
            $q->whereYear('date', $year)->whereMonth('date', $month)
                ->where('type_id', 43);
        }])->get();  
        $insentive_user = $users->where('position_id', 15)->first();
        $insentive_exists = !empty($insentive_user); 
        $recordList = [];
        $conditions = ['🌈','☀️','☁️','☂️','⚡','☃️'];
        $totalData = [
            'work_time' => 0,
            'over_time' => 0,
            'break_time' => 0,
            'costs' => 0,
            'mileage' => 0,
            'actuals' => [],
            'select_counts' => [],
        ];
        for ($day = 1; $day <= cal_days_in_month(CAL_GREGORIAN, $month, $year); $day++) {
            $date = Carbon::create($year, $month, $day);
        
            foreach ($users as $user) {
                $targetShiftDay = $date->format('Y-m-d');
                $time_card_record = $user->time_card_records->where('day', $targetShiftDay)->first();                
                $shift = $user->shift_records->where('shift_day', $targetShiftDay)->first();
                $condition_index = $user->custom_field_data_records->where('date', $targetShiftDay)->first()?->value_int;
                $hasProjectSegmentsForDetails = !empty($time_card_record) && $time_card_record->project_segments->isNotEmpty();
                $segmentDetailValues = $hasProjectSegmentsForDetails
                    ? $time_card_record->project_segments->map(fn ($segment) => is_array($segment->detail_values) ? $segment->detail_values : [])
                    : collect();
                $comment = empty($time_card_record) || $hasProjectSegmentsForDetails ? '' : $time_card_record->custom_field_data_records->where('type_id', 39)->first();
                $segmentComments = $hasProjectSegmentsForDetails
                    ? $time_card_record->project_segments->pluck('comment')->map(fn ($value) => trim((string) $value))->filter()->unique()->values()->implode("\n")
                    : '';
                $allowances = empty($time_card_record) || $hasProjectSegmentsForDetails ? [] : $time_card_record->custom_field_data_records->where('type_id', 37)->pluck('label')->toArray();
                $allowances_value = implode(" ", $allowances); 
                if ($hasProjectSegmentsForDetails) {
                    $allowances_value = $segmentDetailValues
                        ->flatMap(fn ($values) => $values['allowance_labels'] ?? [])
                        ->filter()
                        ->unique()
                        ->values()
                        ->implode(' ');
                }
                $incident = empty($time_card_record) || $hasProjectSegmentsForDetails ? [] : $time_card_record->custom_field_data_records->where('type_id', 40)->first();
                $segmentIncident = $hasProjectSegmentsForDetails
                    ? $segmentDetailValues->map(fn ($values) => trim((string) ($values['incident'] ?? '')))->filter()->unique()->values()->implode("\n")
                    : '';
                $costs = !empty($time_card_record) ? $time_card_record->timecard_costs : [];
                $cases = empty($time_card_record) ? collect() : collect($time_card_record->project_case);
                
                $satisfy = empty($time_card_record) ? [] : $time_card_record->custom_field_data_records->where('type_id', 41)->first();  
                $isRegistered = $user->position_id == 15;
                $costFormatted = '';
                if($isRegistered){
                    $transportCost = collect($costs)->where('type', 1)->sum('expenses');
                    $communicationCost = collect($costs)->where('type', 2)->sum('expenses');
                    $accommodationCost = collect($costs)->where('type', 3)->sum('expenses');
                    $costFormatted = ($transportCost ? "交通費 : $transportCost" . '円 ' : '') . ($communicationCost ? "通信費 : $communicationCost" . '円' : "") . ($accommodationCost ? "宿泊費 : $accommodationCost" . '円' : "");
                }else{
                    $travelCost = collect($costs)->where('type', 4)->sum('expenses');
                    $communicationCost = collect($costs)->where('type', 2)->sum('expenses');
                    $suppliesCost = collect($costs)->where('type', 5)->sum('expenses');
                    $entertainmentCost = collect($costs)->where('type', 6)->sum('expenses');
                    $commissionCosts = collect($costs)->where('type', 7)->sum('expenses');
                    $welfareExpense = collect($costs)->where('type', 8)->sum('expenses');
                    $costFormatted = ($travelCost ? "旅費交通費 : $travelCost'円'": '') . 
                                    ($communicationCost ? "通信費 : $communicationCost'円'" : "") . 
                                    ($suppliesCost ? "消耗品費 : $suppliesCost'円'" : "") . 
                                    ($entertainmentCost ? "交際費 : $entertainmentCost'円'" : "") . 
                                    ($commissionCosts ? "支払手数料 : $commissionCosts'円'" : "") . 
                                    ($welfareExpense ? "福利厚生費 : $welfareExpense'円'" : "" );
                }
                if (!empty($time_card_record)) {
                    $totalData['work_time'] += (int) $time_card_record->work_time;
                    $totalData['over_time'] += (int) $time_card_record->over_time;
                    $totalData['break_time'] += (int) $time_card_record->break_time;
                    $totalData['costs'] += (int) collect($costs)->sum('expenses');
                    $totalData['mileage'] += (int) $time_card_record->car_mileage;

                    foreach ($cases as $case) {
                        if (!filled($case->amount) || !is_numeric($case->amount)) {
                            continue;
                        }

                        $label = $this->projectUnitLabel($case->project);
                        $key = ($case->status ?? '実績') . '|' . $label;
                        $totalData['actuals'][$key]['status'] = $case->status ?? '実績';
                        $totalData['actuals'][$key]['unit_label'] = $label;
                        $totalData['actuals'][$key]['amount'] = ($totalData['actuals'][$key]['amount'] ?? 0) + (int) $case->amount;
                        $this->addCaseSelectOptionCounts($totalData['select_counts'], $case);
                    }
                }
               
                $data = [
                    '日付' => $date->format('Y-m-d'),
                    'メンバー' => $user->name,
                    '予定' => empty($shift) || $isRegistered ? '' : $shift->shiftType->name,
                    '出勤' => empty($time_card_record) ? '' : substr($time_card_record->start_time, 0, 5),
                    '退勤' => empty($time_card_record) ? '' : substr($time_card_record->end_time, 0, 5),
                    '労働時間' => (empty($time_card_record) ? '' : floor($time_card_record->work_time / 60).'時間') . (empty($time_card_record) ? '' : ($time_card_record->work_time % 60).'分')   ,
                    '時間外' => empty($time_card_record) ? '' : ($time_card_record->over_time).'分',
                    '休憩時間' => empty($time_card_record) ? '' : ($time_card_record->break_time).'分',
                    '部門' => empty($time_card_record) || empty($time_card_record->department) ? '' : $time_card_record->department->name,
                    '諸手当' => $allowances_value, 
                    'インシデント' => $hasProjectSegmentsForDetails ? $segmentIncident : (empty($incident) ? '' : ($incident->value_text ?? $incident->label)),
                    '目標達成率' => empty($satisfy) ? '' : $satisfy->label,
                    'コンディション' => $condition_index ? $conditions[$condition_index] : '',
                    'コメント' => $hasProjectSegmentsForDetails ? $segmentComments : ($comment ? $comment->value_text : ''),
                    '経費' => $costFormatted,
                    '実績' => '',
                    'マイカー走行距離' => empty($time_card_record) ? '' : $time_card_record->car_mileage
                ];
                if (!empty($time_card_record->department) && $time_card_record->department->has_actual_func) {
                    $data['実績'] = $cases
                        ->filter(fn ($c) => filled($c->amount))
                        ->map(function ($c) {
                            $label = $this->projectUnitLabel($c->project);

                            return ($c->status ?? '実績') . ': ' . $c->amount . $label;
                        })
                        ->join("\n");
                }
                if($insentive_exists){
                    $data['実績'] = $cases
                        ->filter(function ($c) {
                            $amount = $c->amount ?? null;

                            if (is_array($amount)) {
                                return collect($amount)->filter(fn ($v) =>
                                    $v !== null && trim((string) $v) !== ''
                                )->isNotEmpty();
                            }

                            return $amount !== null && trim((string) $amount) !== '';
                        })
                        ->map(function ($c) {
                            $lines = [];

                            if (!empty($c->meta) && is_array($c->meta)) {
                                foreach ($c->meta as $key => $val) {
                                    if ($val !== null && trim((string) $val) !== '') {
                                        $lines[] = "{$key}: {$val}";
                                    }
                                }
                            }

                            $status = $c->status ?? '実績';

                            $amount = $c->amount ?? '';

                            if (is_array($amount)) {
                                $amount = collect($amount)
                                    ->filter(fn ($v) => $v !== null && trim((string) $v) !== '')
                                    ->implode(', ');
                            }

                            $unitLabel = $this->projectUnitLabel($c->project);
                            $lines[] = "{$status}: {$amount}{$unitLabel}";

                            return implode("\n", $lines);
                        })
                        ->implode("\n\n");
                }
                array_push($recordList, $data);
            }
        }
        $actualSummary = collect($totalData['actuals'])
            ->map(fn ($row) => "{$row['status']}: {$row['amount']}{$row['unit_label']}")
            ->values()
            ->join("\n");
        $selectSummary = $this->formatSelectOptionSummary($totalData['select_counts']);

        $recordList[] = [
            '日付' => '合計',
            'メンバー' => '',
            '予定' => '',
            '出勤' => '',
            '退勤' => '',
            '労働時間' => $this->formatMinutesForCsv($totalData['work_time']),
            '時間外' => $totalData['over_time'] . '分',
            '休憩時間' => $totalData['break_time'] . '分',
            '部門' => '',
            '諸手当' => '',
            'インシデント' => '',
            '目標達成率' => '',
            'コンディション' => '',
            'コメント' => '',
            '経費' => $totalData['costs'] ? $totalData['costs'] . '円' : '',
            '実績' => trim($actualSummary . ($actualSummary && $selectSummary ? "\n" : '') . $selectSummary),
            'マイカー走行距離' => $totalData['mileage'] ? $totalData['mileage'] . 'km' : '',
        ];
        return response()->json($recordList);
    }    

    public function shift_add_department(Request $request) {
        $request->validate([
            'id' => 'required',
        ]);
        $update = shiftRecord::findOrFail($request->id)->update(['department_id' => $request->department_id]);
        return response()->json($update);
    }
    public function get_planned_leaves(Request $request){
        $paidholidays = shiftRecord::where('user_id', $request->user_id)
                                    ->where('planned_year', $request->year)
                                    ->where('shift_type', 3)
                                    ->select('shift_day', 'user_id', 'id', 'planned_year')
                                    ->orderBy('shift_day')
                                    ->with(['planned_leave_change_request'])
                                    ->get();

        $workTemp = [];
        $user = User::find($request->user_id);
        if($user->user_code){
            $workTemp = workTemp::where('user_code', $user->user_code)->whereYear('date', $request->year)->first();
        }
        
        
        $selectableProjects = ProjectRecord::whereHas('members', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->select('id', 'name')->get();
        
        $pmApprovalNeeded = $user->position_id != 6 && count($selectableProjects) > 0;
        return response()->json([
            'paidholidays' => $paidholidays,
            'workTemp' => $workTemp,
            'pmApprovalNeeded' => $pmApprovalNeeded,
            'selectableProjects' => $selectableProjects

        ]);
    }
    public function planned_leave_change_request(Request $request){
        $request->validate([
            'shift_id' => 'required|integer|exists:shift_records,id',
            'change_request_date' => 'required|date',
            'project_id' => 'nullable|integer|exists:project_records,id',
            'pm_approval_required' => 'nullable|boolean',
            'reason' => 'nullable|string',
        ]);
        
        $shift = shiftRecord::findOrFail($request->shift_id);
        $user = User::find($shift->user_id);
        if($user->user_code == null){
            throw ValidationException::withMessages(['message' => 'ユーザーコードが設定されていません。']);
        }
        $planned_year = $shift->planned_year;
        $change_request_date = Carbon::create($request->change_request_date);
        $work_temp = workTemp::where('user_code', $user->user_code)->where(fn($query) => $query->whereYear('date', $planned_year))->first();
        if(!$work_temp){
            throw ValidationException::withMessages(['message' => '勤務表テンプレートが見つかりません。']);
        }
        $startLimit = Carbon::create($work_temp->date);
        $endLimit = Carbon::create($work_temp->date)->addYear()->subDay();
        $makeSureBetween = $change_request_date->between($startLimit, $endLimit);
        if(!$makeSureBetween){
            throw ValidationException::withMessages(['message' => "変更申請日は{$startLimit->toDateString()}から{$endLimit->toDateString()}の間に設定してください。"]);
        }
        $checkDuplicatePlannedLeave = shiftRecord::where('user_id', $shift->user_id)
                                    ->where('shift_type', 3)
                                    ->where('shift_day', $change_request_date->toDateString())
                                    ->exists();
        if($checkDuplicatePlannedLeave){
            throw ValidationException::withMessages(['message' => '既に同日に計画有給が存在しています。']);
        }
        $checkDuplicateRequest = PlannedLeaveChangeRequest::where('user_id', $shift->user_id)
                                    ->where('shift_record_id', $shift->id)
                                    ->exists();
        if($checkDuplicateRequest){
            throw ValidationException::withMessages(['message' => '既に同シフトに対して変更申請が存在しています。']);
        }
        $pmApprovalRequired = $request->boolean('pm_approval_required');
        if($pmApprovalRequired){
            if(!$request->project_id){
                throw ValidationException::withMessages(['message' => 'プロジェクトを選択してください。']);
            }
            $belongsToProject = ProjectRecord::where('id', $request->project_id)
                ->whereHas('members', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->exists();
            if(!$belongsToProject){
                throw ValidationException::withMessages(['message' => '選択したプロジェクトに所属していません。']);
            }
        }
        $createRequest = PlannedLeaveChangeRequest::create([
            'user_id' => $shift->user_id,
            'shift_record_id' => $shift->id,
            'original_date' => $shift->shift_day,
            'requested_date' => $change_request_date->toDateString(),
            'status' => 'pending',
            'project_id' => $request->project_id ?? null,
            'pm_approval_required' => $pmApprovalRequired,
            'reason' => $request->reason ?? null,
        ]);
        return response()->json($createRequest);


    }
    public function planned_leave_change_requests(Request $request)
    {
        $user = $this->active_user();

        return response()->json($this->plannedLeaveChangeRequestQuery($user)
            ->with([
                'user:id,name,icon_path,icon_bg,position_id',
                'approver:id,name,icon_path,icon_bg,position_id',
                'pmApprover:id,name,icon_path,icon_bg,position_id',
                'project_record:id,name',
                'shift_record:id,shift_day,user_id',
            ])
            ->orderBy('created_at')
            ->get());
    }

    private function plannedLeaveChangeRequestQuery(User $user)
    {
        $query = PlannedLeaveChangeRequest::query();

        if ($this->canAdminPlannedLeaveChangeRequest($user)) {
            return $query;
        }

        return $query
            ->where('pm_approval_required', true)
            ->whereHas('project_record.manager', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
    }

    private function canAdminPlannedLeaveChangeRequest(User $user): bool
    {
        return in_array($user->id, [608, 610], true);
    }

    public function annual_leave_data(Request $request){
        $user = $this->active_user();
        $year = Carbon::now()->year;
        $planned_leaves_this_year = $this->get_planned_leaves(new Request(['user_id' => $user->id, 'year' => $year]))->getData(true);
        $planned_leaves_last_year = $this->get_planned_leaves(new Request(['user_id' => $user->id, 'year' => $year - 1]))->getData(true);
        $user->code = $user->user_code;
        $remaining_days = 0;
        if($user->user_code){
            $remaining_days_data = $this->get_remaining_days(new Request(['user_code' => $user->user_code]))->getData(true);

            $remaining_days = $remaining_days_data['days'] ?? 0;
            $remaining_days = (float) $remaining_days;
        }
        return response()->json([
            'planned_leaves_this_year' => $planned_leaves_this_year,
            'planned_leaves_last_year' => $planned_leaves_last_year,
            'remaining_days' => $remaining_days
         ]);
    }
    public function send_departure_report(Request $request){
        $user = Auth::user();
        $date = Carbon::now()->toDateString();
        $data = $this->sharedService->createDepartureReport($user, $date);
        return response()->json($data);
    }
    public function get_my_car_data(Request $request){
        $data = $request->validate([
            'user_code' => 'required',
            'mileage' => 'required|integer|min:2'
        ], [
            'user_code.required' => '関連するレコードが見つかりません。',
            'mileage.integer' => '数字を入力してください。',
            'mileage.required' => '走行距離が必要です。',
            'mileage.min' => '最低走行距離が2㎞です。'
        ]);
        $user_code = $data['user_code'];
        $mileage = $data['mileage'];
        
        $query = "従業員番号 = \"{$user_code}\" and 実燃費 != 0 order by 作成日時 desc limit 1";
        $fields = ["従業員番号", "氏名", "ガソリン単価", "実燃費", "作成日時"];
        
        $responseData = $this->kintone->getRecords(777, $query, $fields);
        $mileage_data = [];
        $gas_price_per_km = 0;
        if(!empty($responseData)) {
            $record = $responseData[0];
            $gas_full_price = ($mileage / $record['実燃費']['value']) * $record['ガソリン単価']['value'];
            $mileage_data = [
                'gas_unit_price'=>$record['ガソリン単価']['value'], 
                'gas_consumption'=>$record['実燃費']['value'],
                'gas_full_price'=>ceil($gas_full_price / 10) * 10,
                'status'=>'success'
            ];
            
        } else {
            throw ValidationException::withMessages(['message' => '関連するレコードが見つかりません。']);
        }
        return response()->json($mileage_data);
        
        
    }
    
    public function get_remaining_days(Request $request) {
        $data = $request->validate([
            'user_code' => 'required',
        ], [
            'user_code.required' => '関連するレコードが見つかりません。',
        ]);
        $user_code = $data['user_code'];
        $ledgerBalance = $this->paidLeaveLedger->balanceForUserCode($user_code);
        if ($ledgerBalance) {
            $user = User::query()->where('user_code', $user_code)->first();

            return response()->json([
                'name' => $user?->name,
                'user_code' => $user_code,
                'days' => $ledgerBalance['days'],
                'minutes' => $ledgerBalance['minutes'],
                'minutes_per_day' => $ledgerBalance['minutes_per_day'],
                'status' => 'success',
                'source' => 'glowd',
            ]);
        }

        $query = "社員ｺｰﾄﾞ = \"{$user_code}\"";
        $fields = ["社員ｺｰﾄﾞ", "氏名", "残日数"];
        $responseData = $this->kintone->getRecords(794, $query, $fields);
        $remaining_days = [];
        if(!empty($responseData)) {
            $record = $responseData[0];
            $remaining_days = [
                'name'=>$record['氏名']['value'], 
                'user_code'=>$record['社員ｺｰﾄﾞ']['value'],
                'days'=>$record['残日数']['value'],
                'status'=>'success'
            ];
            
        }
        return response()->json($remaining_days);
    }
}
