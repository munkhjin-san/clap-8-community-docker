<?php

namespace App\Http\Controllers;

use App\Models\timecardCostRecord;
use App\Models\timecardIncentive;
use App\Models\TimecardProjectSegment;
use App\Models\TimecardAuditEvent;
use App\Models\TimecardAuditEventProjection;
use App\Models\TimecardCostOcrRun;
use App\Models\User;

use App\Models\attendanceRecord;

use App\Models\customFieldDataRecord;

use App\Models\ProjectCase;
use App\Models\shiftRecord;
use App\Models\PlannedLeaveChangeRequest;
use App\Enums\PlannedLeaveChangeRequestStatus;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Services\SharedService;
use Carbon\Carbon;
use App\Infrastructure\Kintone\KintoneClient;
use App\Services\PaidLeaveLedgerService;


class AdminWorkController extends Controller{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    protected $sharedService;
    public function __construct(
        SharedService $sharedService,
        private KintoneClient $api,
        private PaidLeaveLedgerService $paidLeaveLedger
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

    public function get_admin_work(Request $request) {

        $month = $request->month;
        $today = Carbon::now();
        [$currentYear, $currentMonth] = explode('-', $request->month);
        $ng_list = ['推し', '知人', '家族', '友人', '関係者', 'お知らせアカウント', '研修サポート'];
        $ids = [608, 610];
        $all_users = User::where('partner_flag', 0)
        ->whereNotIn('name', $ng_list)
        ->whereNotIn('id', $ids)
        ->where(function ($query) {
            $query->where('retire', 0)
                  ->orWhere('retire_date', '>=', Carbon::now());
        })
        ->with(['shift_records' => function($q) use($currentYear, $currentMonth){
            $q->whereYear('shift_day', $currentYear)
              ->whereMonth('shift_day', $currentMonth)
              ->select('shift_day', 'shift_type', 'user_id', 'department_id', 'status_flag')
              ->orderBy('shift_day', 'asc')
              ->with('shiftType')
              ->with('department');
        }])
        ->with(['time_card_records' => function($q) use($currentYear, $currentMonth){
            $q->whereYear('day', $currentYear)
              ->whereMonth('day', $currentMonth)
              ->select('work_time', 'day', 'id', 'user_id', 'work_group_id', 'car_mileage', 'car_used_project', 'gas_full_price', 'status_flag')
              ->orderBy('day', 'asc')
              ->with([
                'custom_field_data_records' => function($q) {
                    $q->whereIn('type_id', [40, 44])
                    ->where('value_int', 1)
                    ->select('type_id', 'value_int', 'date', 'table_record_id');
                },
                'project_segments' => function ($q) {
                    $q->with('project:id,name');
                },
                'vehicle_data' => function ($q) {
                    $q->with('before_user', 'after_user', 'project:id,name', 'project_segment.project:id,name');
                },
                'vehicle_records' => function ($q) {
                    $q->with('before_user', 'after_user', 'project:id,name', 'project_segment.project:id,name');
                }
              ])
              ->with(['department', 'car_project']);
        }])
        ->with(['attendance_records' => function($q) use($month){
            $q->where('date_year_month', $month)->select('month_petition', 'user_id');
        }])->get([
            'id',
            'name',
            'user_code',
            'general_position',
            'work_time_day',
            'work_type',
            'position_id',
            
        ]);
        $user_codes = $all_users->pluck('user_code')->toArray();
        $user_codes_str = implode('","', $user_codes);
        $fields = ['基本給単位', '社員コード数値', '職務手当に含まれる時間外'];
        $query = "社員コード数値 in (\"{$user_codes_str}\")";
        $limit = 500;
        $kin_records = $this->api->getRecords(1305, $query . " limit {$limit}", $fields); 
        
        $userIds = $all_users->pluck('id');

        $holiday_shifts = shiftRecord::whereIn('user_id', $userIds)
        ->whereIn('shift_type', [0, 18, 19, 20, 21, 22, 23, 24, 25, 26])
            ->whereYear('shift_day', $currentYear)
            ->select('id', 'user_id', 'shift_day', 'shift_type')
            ->whereHas('shiftType')
            ->with(['shiftType' => function ($query) {
                $query->select('id', 'name', 'full_day');
            }])
            ->get()->groupBy('user_id');

            
        $time_card_costs = timecardCostRecord::where('date_month', $request->month)
        ->with(['user' => function ($q) {
            $q->select('id', 'name');
        }])
        ->with(['timecard' => function ($q) {
            $q->select('id', 'day');
        }])
        ->with(['project:id,name', 'projectSegment.project:id,name'])
        ->select('id', 'date_month', 'department', 'type', 'expenses', 'user_id', 'record_id', 'project_id', 'timecard_project_segment_id')
        ->get();
        
        
        
        $attendance_record = attendanceRecord::where('date_year_month', $month)->with([
            'user' => function ($q) {
                $q->select('id', 'name', 'position_id');
            }
        ])->get();

        
        $expenses = timecardCostRecord::selectRaw(
            'user_id,
            SUM(expenses) as totalExpenses'
        )->whereIn('user_id', $userIds)
        ->where('date_month', $request->month)
        ->groupBy('user_id')
        ->get();
        $monthly_expenses = $expenses->pluck('totalExpenses', 'user_id');
        $incentives = timecardIncentive::selectRaw(
            'user_id,
            SUM(count) as totalCount'
        )->whereIn('user_id', $userIds)
        ->where('date_month', $request->month)
        ->groupBy('user_id')
        ->get();
        $monthly_incentive = $incentives->pluck('totalCount', 'user_id');
        $monthly_result = ProjectCase::query()
        ->selectRaw("
            project_cases.user_id,
            project_records.unit_id,
            project_records.custom_unit_label,
            SUM(project_cases.amount) as total_amount
        ")
        ->join('project_records', 'project_records.id', '=', 'project_cases.project_record_id')
        ->whereIn('project_cases.user_id', $userIds)
        ->whereYear('project_cases.report_date', $currentYear)
        ->whereMonth('project_cases.report_date', $currentMonth)
        ->whereHas('timecardRecord')
        ->groupBy(
            'project_cases.user_id',
            'project_records.unit_id',
            'project_records.custom_unit_label'
        )
        ->orderBy('project_cases.user_id')
        ->get()
        ->groupBy('user_id');
        now()->day >= 1 && now()->day <= 6 ? $previousMonth = $currentMonth - 1 : $previousMonth = null;  
        $custom_weather_data = customFieldDataRecord::whereIn('user_id', $userIds)
        ->whereYear('date', $currentYear)
        ->where(function ($query) use ($currentMonth, $previousMonth) {
            $query->whereMonth('date', $currentMonth);
            if ($previousMonth) {
                $query->orWhereMonth('date', $previousMonth);
            }
        })
        ->where('type_id', 43)
        ->where('deleted_flag', 0)
        ->orderBy('user_id')
        ->orderBy('date')
        ->get(['user_id', 'value_int', 'date'])
        ->groupBy('user_id')
        ->map(function ($userRecords) {
            return $userRecords->sortByDesc('date')->take(3);
        });


        $streakDataPerUser = [];
        $mostCommonValuesPerUser = [];
        $custom_weather_data->each(function ($records, $userId) use (&$streakDataPerUser, &$mostCommonValuesPerUser) {
            foreach ($records as $record) {
                $value_int = $record->value_int;
                if (!isset($streakDataPerUser[$userId])) {
                    $streakDataPerUser[$userId] = [
                        'current_streak' => 0,
                        'max_streak' => 0,
                    ];
                }
                if (in_array($value_int, [3, 4, 5])) {
                    $streakData = &$streakDataPerUser[$userId];
                    $streakData['current_streak']++;
                    $streakData['max_streak'] = max($streakData['max_streak'], $streakData['current_streak']);
        
                    if ($streakData['current_streak'] >= 3) {
                        $mostCommonValuesPerUser[$userId] = [
                            'current_streak' => $streakData['current_streak'],
                            'max_streak' => $streakData['max_streak'],
                            'current_value' => $value_int,
                        ];
                    }
                } else {
                    $streakDataPerUser[$userId]['current_streak'] = 0;
                }
            }
        });
        $unitMap = [
            '/日' => '日給',
            '/月' => '月給',
            '/時' => '時給',
        ];   
            $new_shift_record_array = [];
            $month_work_time_array2 = [];
            $allDepartmentCounts = collect();
            $my_car_usage = [];
            $monthly_must_work_days = [];
            foreach ($all_users as $user) {
                $shiftTypes = range(3, 17);
                $totalPaidHours = 0;
                $legal_holiday_shifts = [];
                if ($user->shift_records->isNotEmpty()) {
                    $departmentCountsTemp = [];

                    foreach ($user->shift_records as $record) {

                        if($record->shift_type == 18){
                            $legal_holiday_shifts[] = $record->shift_day;
                        }
                        // Extract common values
                        // $month = Carbon::parse($record->shift_day)->format('Y-m');
                        // $departmentName = $record['department']['name'] ?? null;

                        // // Only process if department name exists
                        // if ($departmentName) {
                        //     $groupKey = "{$departmentName}|{$user->name}|{$month}";

                        //     // Initialize counter
                        //     if (!isset($departmentCountsTemp[$groupKey])) {
                        //         $departmentCountsTemp[$groupKey] = [
                        //             'count' => 0,
                        //             'department' => $departmentName,
                        //             'username' => $user->name,
                        //             'month' => $month,
                        //         ];
                        //     }
                        //     $departmentCountsTemp[$groupKey]['count']++;
                        // }

                        // Paid hours + shift record array logic
                        $shift_type = $record->shiftType;
                        if (in_array($shift_type->id, $shiftTypes)) {
                            $totalPaidHours += $shift_type->value;
                            $new_shift_record_array[$record->user_id][] = [
                                'day' => $record->shift_day,
                                'type' => $shift_type->id,
                            ];
                        }

                    }

                    // // Merge into the main department counts collection
                    // $allDepartmentCounts = $allDepartmentCounts->merge($departmentCountsTemp);
                }

                $workTimeInMinutes = 0;

                $legal_holiday_worked_time_in_minutes = 0;

                $total_gas_price = 0;
                $result = collect($kin_records)
                ->first(function ($record) use ($user) {
                    return data_get($record, '社員コード数値.value') == $user->user_code;
                });
                $jobAllowanceOverTime = data_get($result, '職務手当に含まれる時間外.value', 0);
                if ($user->time_card_records->isNotEmpty()) {
                    $departmentCountsTemp = [];
                    
                    foreach ($user->time_card_records as $record) {
                       
                        $segments = $record->project_segments ?? collect();
                        $workTimeInMinutes += $segments->isNotEmpty()
                            ? (int) $segments->where('segment_type', TimecardProjectSegment::TYPE_WORK)->sum('minutes')
                            : (int) $record->work_time;

                        foreach ($this->timecardDepartmentRows($record, $user->name) as $departmentRow) {
                             
                            $groupKey = $departmentRow['department'] . '|' . $departmentRow['username'] . '|' . $departmentRow['month'];

                            if (!isset($departmentCountsTemp[$groupKey])) {
                                $departmentCountsTemp[$groupKey] = [
                                    'work_time' => 0,
                                    'department' => $departmentRow['department'],
                                    'username' => $departmentRow['username'],
                                    'month' => $departmentRow['month'],
                                    'job_allowance_over_time' => $jobAllowanceOverTime,
                                ];
                            }
                            $departmentCountsTemp[$groupKey]['work_time'] += $departmentRow['work_time'];
                        }

                        foreach ($this->timecardMyCarRows($record, $user->name) as $myCarRow) {
                            $my_car_usage[] = $myCarRow;
                            $total_gas_price += (int) ($myCarRow['gas_full_price'] ?? 0);
                        }
                        if($user->work_type == 1 && in_array($record->day, $legal_holiday_shifts)) {
                            // If work type is 1 and the day is a legal holiday, add to legal holiday worked time
                            $legal_holiday_worked_time_in_minutes += $record->work_time;

                        }
                    }

                    // Merge with main department counts
                    $allDepartmentCounts = $allDepartmentCounts->merge($departmentCountsTemp);
                }
                if($user->work_type == 0){
                    $userWorkData = $this->sharedService->work_days_calculator((int) $currentYear, (int) $currentMonth, $user);

                    $userShouldWorkTimeInMinutes = $userWorkData['work_minutes']; // e.g., 176h → 10560 min
                    $userDailyMinutes = $user->work_time_day; // e.g., 480 min (8h)
                    $minimumLegalHolidayMinutes = 4 * $userDailyMinutes; // 1920 min (4 days)

                    // Actual worked time in minutes for the month
                    $actualWorkedMinutes = $workTimeInMinutes;

                    // Step 1: Calculate total overtime
                    $totalOvertime = $actualWorkedMinutes - $userShouldWorkTimeInMinutes;

                    if ($totalOvertime > 0) {
                        // Step 2: Special overtime threshold
                        $specialOvertimeThreshold = $userShouldWorkTimeInMinutes + $minimumLegalHolidayMinutes;

                        if ($actualWorkedMinutes > $specialOvertimeThreshold) {
                            $legal_holiday_worked_time_in_minutes = $actualWorkedMinutes - $specialOvertimeThreshold;
                        } else {
                        }
                    } 
                    
                }
                $monthly_expenses->put(
                    $user->id,
                    ($monthly_expenses->get($user->id, 0) + $total_gas_price)
                );
                

                $salaryUnit = $unitMap[data_get($result, '基本給単位.value')] ?? null;
                
                $month_work_time_array2[$user->id] = $workTimeInMinutes + $totalPaidHours;
                $userWorkTimeData = $this->sharedService->work_days_calculator($currentYear, $currentMonth, $user);
                $monthly_must_work_days[$user->id] = $userWorkTimeData['days'];
                $user_work_minutes_per_day = $user->work_time_day ?? 480;

                $current_year_holiday_shifts = $holiday_shifts->get($user->id, collect());

                $total_holidays = $current_year_holiday_shifts->sum(function ($shift) use ($user_work_minutes_per_day) {
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
                $user['monthly_mileage'] = $user->time_card_records->sum(fn ($record) => $this->timecardMileageTotal($record));
                $user['yearly_holiday_minutes'] = $total_holidays;
                $user['work_minutes_per_day'] = $user_work_minutes_per_day;
                $user['legal_holiday_shifts'] = $legal_holiday_shifts;
                $user['legal_holiday_worked_time_in_minutes'] = $legal_holiday_worked_time_in_minutes;
                $user['salary_unit'] = $salaryUnit;
            }
            $allDepartmentCountsArray = $allDepartmentCounts->values()->all();
            $responseArray = [
                'attendance_record' => $attendance_record,
                'paid_holiday_record' => $new_shift_record_array,
                'month_work_time' => $month_work_time_array2,
                'month_work_days' => $monthly_must_work_days,
                'users' => $all_users,
                'weather_average' => $mostCommonValuesPerUser,
                'monthly_expenses' => $monthly_expenses,
                'monthly_incentive' => $monthly_incentive,
                'monthly_result' => $monthly_result,
                'timecard_costs' => $time_card_costs,
                'departments' => $allDepartmentCountsArray,
                'holiday_shifts' => $holiday_shifts,
                'my_car_usage' => $my_car_usage
            ];

        return response()->json($responseArray);

    }
    private function timecardDepartmentRows($record, string $userName): array
    {
        $month = Carbon::parse($record->day)->format('Y-m');

        $segments = $record->project_segments ?? collect();

        if ($segments->isNotEmpty()) {
            return $segments
                ->filter(fn ($segment) => $segment->project?->name)
                ->groupBy(fn ($segment) => (int) ($segment->project_id ?? $segment->project?->id))
                ->map(function ($projectSegments) use ($userName, $month) {
                    $project = $projectSegments->first()->project;

                    return [
                        'department' => $project->name,
                        'username' => $userName,
                        'month' => $month,
                        'work_time' => (int) $projectSegments
                            ->where('segment_type', TimecardProjectSegment::TYPE_WORK)
                            ->sum('minutes'),
                    ];
                })
                ->values()
                ->all();
        }

        $departmentName = $record['department']['name'] ?? null;
        if (!$departmentName) {
            return [];
        }

        return [[
            'department' => $departmentName,
            'username' => $userName,
            'month' => $month,
            'work_time' => (int) $record->work_time,
        ]];
    }

    private function timecardMyCarRows($record, string $userName): array
    {
        $segments = $record->project_segments ?? collect();
        $rows = [];

        foreach ($segments as $segment) {
            $detailValues = is_array($segment->detail_values ?? null) ? $segment->detail_values : [];
            $mileage = $detailValues['mileage'] ?? null;
            if (!is_array($mileage)) {
                continue;
            }

            $distance = (int) ($mileage['mileage'] ?? 0);
            $gasPrice = (int) ($mileage['gas_full_price'] ?? 0);
            if ($distance <= 0 && $gasPrice <= 0) {
                continue;
            }

            $rows[] = [
                'user_name' => $userName,
                'date' => $record->day,
                'mileage' => $distance,
                'project' => $segment->project?->name,
                'gas_full_price' => $gasPrice,
            ];
        }

        if (!empty($rows)) {
            return $rows;
        }

        if ((int) $record->car_mileage <= 0) {
            return [];
        }

        return [[
            'user_name' => $userName,
            'date' => $record->day,
            'mileage' => (int) $record->car_mileage,
            'project' => $record?->car_project?->name,
            'gas_full_price' => (int) $record->gas_full_price,
        ]];
    }

    private function timecardMileageTotal($record): int
    {
        return collect($this->timecardMyCarRows($record, ''))->sum(fn ($row) => (int) ($row['mileage'] ?? 0));
    }

    public function work_audit_logs(Request $request)
    {
        $validated = $request->validate([
            'month' => 'nullable|date_format:Y-m',
            'user_id' => 'nullable|integer',
            'event_type' => 'nullable|string',
            'merchant' => 'nullable|string',
            'receipt_date_from' => 'nullable|date_format:Y-m-d',
            'receipt_date_to' => 'nullable|date_format:Y-m-d',
            'amount_min' => 'nullable|numeric',
            'amount_max' => 'nullable|numeric',
            'approval_state' => 'nullable|integer',
            'export' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = (int) ($validated['per_page'] ?? 50);
        $merchant = trim((string) ($validated['merchant'] ?? ''));

        $query = TimecardAuditEventProjection::query()
            ->with([
                'actor',
                'subject',
            ]);

        if (!empty($validated['month'])) {
            [$year, $month] = explode('-', $validated['month']);
            $query->where(function ($query) use ($year, $month) {
                $query
                    ->whereYear('timecard_day', $year)
                    ->whereMonth('timecard_day', $month)
                    ->orWhere(function ($fallbackQuery) use ($year, $month) {
                        $fallbackQuery->whereNull('timecard_day')
                            ->whereYear('occurred_at', $year)
                            ->whereMonth('occurred_at', $month);
                    });
            });
        }

        $query
            ->when(!empty($validated['user_id']), fn ($query) => $query->where('subject_user_id', $validated['user_id']))
            ->when(!empty($validated['event_type']), fn ($query) => $query->where('event_type', $validated['event_type']))
            ->when(array_key_exists('approval_state', $validated) && $validated['approval_state'] !== null, fn ($query) => $query->where('approval_state', $validated['approval_state']))
            ->when($merchant !== '', fn ($query) => $query->where('merchant_name', 'like', "%{$merchant}%"))
            ->when(!empty($validated['receipt_date_from']), fn ($query) => $query->whereDate('receipt_date', '>=', $validated['receipt_date_from']))
            ->when(!empty($validated['receipt_date_to']), fn ($query) => $query->whereDate('receipt_date', '<=', $validated['receipt_date_to']))
            ->when(array_key_exists('amount_min', $validated) && $validated['amount_min'] !== null, fn ($query) => $query->where('expenses', '>=', $validated['amount_min']))
            ->when(array_key_exists('amount_max', $validated) && $validated['amount_max'] !== null, fn ($query) => $query->where('expenses', '<=', $validated['amount_max']))
            ->orderByDesc('occurred_at')
            ->orderByDesc('timecard_audit_event_id');

        if (!empty($validated['export'])) {
            return response()->streamDownload(function () use ($query) {
                $out = fopen('php://output', 'w');
                fputcsv($out, [
                    'イベントID',
                    '記録時刻',
                    'イベント種別',
                    '対象社員ID',
                    '領収書日付',
                    '取引先',
                    '金額',
                    '通貨',
                    '領収書ファイル',
                    'ファイルSHA-256',
                    '内部統制状態',
                    '内部統制説明',
                ]);
                $query->chunk(500, function ($rows) use ($out) {
                    foreach ($rows as $row) {
                        fputcsv($out, [
                            $row->timecard_audit_event_id,
                            $row->occurred_at?->toDateTimeString(),
                            $row->event_type,
                            $row->subject_user_id,
                            $row->receipt_date?->toDateString(),
                            $row->merchant_name,
                            $row->expenses,
                            $row->currency,
                            $row->file_path,
                            $row->file_sha256,
                            $this->internalControlStatusLabel($row->internal_control_status),
                            $this->internalControlStatusReason($row->internal_control_status),
                        ]);
                    }
                });
                fclose($out);
            }, 'timecard-audit-export.csv', ['Content-Type' => 'text/csv']);
        }

        $paginated = $query->paginate($perPage);

        $events = $paginated->getCollection()->map(function (TimecardAuditEventProjection $projection) {
            return [
                'id' => $projection->timecard_audit_event_id,
                'event_type' => $projection->event_type,
                'target_type' => $projection->target_type,
                'occurred_at' => $projection->occurred_at?->toDateTimeString(),
                'actor' => $projection->actor,
                'subject' => $projection->subject,
                'timecard_day' => $projection->timecard_day?->toDateString(),
                'approval_state' => $projection->approval_state,
                'merchant_name' => $projection->merchant_name,
                'expenses' => $projection->expenses,
                'department' => $projection->department,
                'receipt_file_url' => $projection->file_path ? "/cdn/timecard_files/{$projection->file_path}" : null,
                'receipt_file_id' => $projection->receipt_file_id,
                'file_sha256' => $projection->file_sha256,
                'internal_control_status' => $projection->internal_control_status,
                'internal_control_status_label' => $this->internalControlStatusLabel($projection->internal_control_status),
                'internal_control_status_reason' => $this->internalControlStatusReason($projection->internal_control_status),
                'draft_uuid' => $projection->draft_uuid,
            ];
        })->values();

        return response()->json([
            'data' => $events,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }
    public function work_audit_log_detail(TimecardAuditEvent $event)
    {
        $event->load([
            'actor',
            'subject',
            'timecard:id,day,status_flag,user_id,approved_by,work_group_id,start_time,end_time',
            'timecardCost:id,record_id,merchant_name,receipt_date,expenses,file_path,receipt_file_id,file_sha256,currency,receipt_source_type,department,content,type,transport_type,departure_place,arrival_place,scan_dpi,scan_color_depth,scan_color_mode,document_size,image_width_px,image_height_px',
            'timecardCost.receiptFile',
        ]);

        $metadata = $event->metadata ?? [];
        $ocrRun = null;
        if (!empty($metadata['ocr_run_id'])) {
            $ocrRun = TimecardCostOcrRun::with(['executedBy', 'appliedBy'])->find($metadata['ocr_run_id']);
        }

        $cost = $event->timecardCost;
        $filePath = $cost?->file_path
            ?? Arr::get($event->after_state, 'file_path')
            ?? Arr::get($event->before_state, 'file_path')
            ?? Arr::get($metadata, 'file_path');
        $internalControlStatus = TimecardAuditEventProjection::query()
            ->where('timecard_audit_event_id', $event->id)
            ->value('internal_control_status');

        return response()->json([
            'id' => $event->id,
            'event_type' => $event->event_type,
            'target_type' => $event->target_type,
            'occurred_at' => $event->occurred_at?->toDateTimeString(),
            'actor' => $event->actor,
            'subject' => $event->subject,
            'timecard' => $event->timecard,
            'timecard_cost' => $cost,
            'before_state' => $event->before_state,
            'after_state' => $event->after_state,
            'metadata' => $metadata,
            'internal_control_status' => $internalControlStatus,
            'internal_control_status_label' => $this->internalControlStatusLabel($internalControlStatus),
            'internal_control_status_reason' => $this->internalControlStatusReason($internalControlStatus),
            'receipt_file_url' => $filePath ? "/cdn/timecard_files/{$filePath}" : null,
            'receipt_file' => $cost?->receiptFile,
            'ocr_run' => $ocrRun ? [
                'id' => $ocrRun->id,
                'provider' => $ocrRun->provider,
                'model' => $ocrRun->model,
                'status' => $ocrRun->status,
                'normalized_result' => $ocrRun->normalized_result,
                'raw_response' => $ocrRun->raw_response,
                'error_message' => $ocrRun->error_message,
                'executed_by' => $ocrRun->executedBy,
                'applied_by' => $ocrRun->appliedBy,
                'applied_at' => $ocrRun->applied_at?->toDateTimeString(),
            ] : null,
        ]);
    }

    private function internalControlStatusLabel(?string $status): string
    {
        return match ($status) {
            'sealed' => '日次封印済み',
            'recorded' => '証跡記録済み（封印待ち）',
            default => '対象外',
        };
    }

    private function internalControlStatusReason(?string $status): string
    {
        return match ($status) {
            'sealed' => '原本ファイルまたはSHA-256が記録され、該当日の監査digestで封印されています。',
            'recorded' => '原本ファイルまたはSHA-256は記録済みです。該当日の監査digest封印後に日次封印済みになります。',
            default => '領収書ファイル証跡を伴わないイベントです。',
        };
    }
    

    public function get_planned_shifts(Request $request){
        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        return response()->json($this->paidLeaveLedger->plannedLeaveUsers((int) $data['year']));
    }

    public function change_planned_shifts(Request $request){
        $updatedShifts = $this->changePlannedShiftsForUser(
            (array) $request->shifts,
            (int) $request->userId,
            $request->startDate
        );

        return response()->json(['updated_shifts' => $updatedShifts]);
    }

    public function respond_planned_leave_change_request(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer|exists:planned_leave_change_requests,id',
            'action' => 'required|string|in:approve,reject',
        ]);

        $user = $this->active_user();
        $changeRequest = PlannedLeaveChangeRequest::with(['project_record.manager', 'shift_record'])
            ->findOrFail($data['id']);

        if ($changeRequest->status !== PlannedLeaveChangeRequestStatus::Pending) {
            throw ValidationException::withMessages(['message' => 'この申請は既に処理されています。']);
        }

        $isAdmin = $this->canAdminPlannedLeaveChangeRequest($user);
        $isProjectManager = $this->canPmPlannedLeaveChangeRequest($user, $changeRequest);

        if (!$isAdmin && !$isProjectManager) {
            abort(403);
        }

        DB::transaction(function () use ($changeRequest, $user, $data, $isAdmin) {
            $now = Carbon::now();
            $approved = $data['action'] === 'approve';

            if ($isAdmin) {
                if ($approved && $changeRequest->shift_record) {
                    $updatedShifts = $this->changePlannedShiftsForUser([
                        [
                            'id' => $changeRequest->shift_record->id,
                            'shift_day' => $changeRequest->requested_date->toDateString(),
                        ],
                    ], $changeRequest->user_id, $this->plannedLeaveStartDate($changeRequest));
                    $changeRequest->shift_record_id = $updatedShifts[0]->id ?? $changeRequest->shift_record_id;
                }

                $changeRequest->approver_id = $user->id;
                $changeRequest->approval_date = $now;
                $changeRequest->status = $approved
                    ? PlannedLeaveChangeRequestStatus::Approved
                    : PlannedLeaveChangeRequestStatus::Rejected;
                $changeRequest->save();

                return;
            }

            $changeRequest->pm_id = $user->id;
            $changeRequest->pm_approval_date = $now;
            if (!$approved) {
                $changeRequest->status = PlannedLeaveChangeRequestStatus::Rejected;
            }
            $changeRequest->save();
        });

        return response()->json($changeRequest->fresh([
            'user:id,name,icon_path,icon_bg,position_id',
            'approver:id,name,icon_path,icon_bg,position_id',
            'pmApprover:id,name,icon_path,icon_bg,position_id',
            'project_record:id,name',
            'shift_record:id,shift_day,user_id',
        ]));
    }

    private function changePlannedShiftsForUser(array $changedShifts, int $userId, string $startDate): array
    {
        if(empty($changedShifts)){
            throw ValidationException::withMessages(['message' => '変更対象の計画有給がありません。']);
        }

        $shiftDays = collect($changedShifts)->pluck('shift_day');
        $updatedShifts = [];
        $existingShift = shiftRecord::whereIn('shift_day', $shiftDays)
                                    ->where('user_id', $userId)
                                    ->where('shift_type', 3)
                                    ->get()->pluck('shift_day');
        if(count($existingShift) > 0){
            $string = '';
            foreach($existingShift as $day){
                $string = $string . $day . ' ';
            }
            throw ValidationException::withMessages(['message' => $string . '日はすでに計画された計画有給のため、変更することはできません。']);
        }
        $startDate = Carbon::parse($startDate);
        $endDate = $startDate->copy()->addYear()->subDay();
        $allShiftsValid = collect($changedShifts)->every(function ($shift) use ($startDate, $endDate) {
            $shiftDay = Carbon::parse($shift['shift_day']);
            return $shiftDay->between($startDate, $endDate);
        });
    
        if (!$allShiftsValid) {
            throw ValidationException::withMessages(['message' => "{$startDate->format('Y-m-d')}から{$endDate->format('Y-m-d')}の間で選択してください。"]);

        }
        $updatedShifts = DB::transaction(function () use ($userId, $changedShifts, $shiftDays) {
            shiftRecord::whereIn('shift_day', $shiftDays)
                        ->where('user_id', $userId)
                        ->whereNot('shift_type', 3)
                        ->delete();

            $updatedShifts = [];
            $reconcileMonths = [];
            foreach($changedShifts as $shift){
                $shiftRecord = shiftRecord::findOrFail($shift['id']);
                $oldMonthKey = Carbon::parse($shiftRecord->shift_day)->format('Y-m');
                $newMonthKey = Carbon::parse($shift['shift_day'])->format('Y-m');

                $newShift = shiftRecord::create([
                    "user_id" => $shiftRecord->user_id,
                    "start_time" => $shiftRecord->start_time,
                    "end_time" => $shiftRecord->end_time,
                    "status_flag" => 1,
                    "shift_day" => $shift['shift_day'],
                    "descendant_of" => $shiftRecord->id,
                    "shift_type" => 3,
                    "planned_year" => $shiftRecord->planned_year
                ]);
                $shiftRecord->delete();
                $updatedShifts[] = $newShift;
                $reconcileMonths[$oldMonthKey] = true;
                $reconcileMonths[$newMonthKey] = true;
            }
            foreach (array_keys($reconcileMonths) as $monthKey) {
                [$reconcileYear, $reconcileMonth] = array_map('intval', explode('-', $monthKey));
                $this->paidLeaveLedger->reconcileShiftUsagesForUserMonth($userId, $reconcileYear, $reconcileMonth, (int) auth()->id());
            }

            return $updatedShifts;
        });
        return $updatedShifts;
    }

    private function plannedLeaveStartDate(PlannedLeaveChangeRequest $changeRequest): string
    {
        $user = User::findOrFail($changeRequest->user_id);
        if(!$changeRequest->shift_record){
            throw ValidationException::withMessages(['message' => '勤務表テンプレートが見つかりません。']);
        }

        $ledgerWindow = $this->paidLeaveLedger->plannedLeaveWindowForUser(
            (int) $user->id,
            (int) $changeRequest->shift_record->planned_year
        );
        if(!$ledgerWindow){
            throw ValidationException::withMessages(['message' => '勤務表テンプレートが見つかりません。']);
        }

        return Carbon::parse($ledgerWindow['period_start'])->toDateString();
    }

    private function canAdminPlannedLeaveChangeRequest(User $user): bool
    {
        return in_array($user->id, [608, 610], true);
    }

    private function canPmPlannedLeaveChangeRequest(User $user, PlannedLeaveChangeRequest $changeRequest): bool
    {
        return (bool) $changeRequest->pm_approval_required
            && !$changeRequest->pm_id
            && $changeRequest->project_record
            && $changeRequest->project_record->manager->contains('id', $user->id);
    }
}
