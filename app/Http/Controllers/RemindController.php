<?php

namespace App\Http\Controllers;

use App\Models\AssetRecord;
use App\Models\attendanceRecord;
use App\Models\boardToUser;
use App\Models\CalendarRecord;
use App\Models\CustomForm;
use App\Models\EvaluationRecord;
use App\Models\messageRecord;
use App\Models\ProjectRecord;
use App\Models\ProjectSetIncrease;
use App\Models\ShiftOvertimeRequest;
use App\Models\shiftRecord;
use App\Models\taskRecord;
use App\Models\timecardRecord;
use App\Models\User;
use App\Models\workTemp;
use App\Models\PostRecord;
use App\Models\CustomfieldRead;
use App\Models\customFieldDataRecord;
use App\Services\BadgeService;
use App\Models\ProjectGoal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use App\Services\ReminderMessageService;
use App\Services\RemindTaskService;
use App\Http\Controllers\ProjectController;

class RemindController extends Controller
{

    public function __construct(
        protected BadgeService $badgeService,
        protected ReminderMessageService $reminderMessageService,
        protected RemindTaskService $remindTaskService,
        protected ProjectController $projectController    
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
    public function remind_attendance(Request $request){
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
        $user_detail = $auth_user->user_detail;
        $date = Carbon::now();
        $yesterday = $date->clone()->subDay()->isoFormat('YYYY-MM-DD');
        $today = $date->isoFormat('YYYY-MM');
        $year = $date->year;
        $month = $date->month;
        $prev_month = $date->clone()->subMonth()->month;
        $ids = [610, 608];
        if(in_array($auth_user_id, $ids) || in_array($auth_user->position_id, [1, 2, 3, 4, 5, 14, null])){
            
            return response()->json([
                'timecard_notSubmitted' => [],
                'shift_notSubmitted' => []
            ]);
        }
        $previousMonth = $date->clone()->subMonth()->isoFormat('YYYY-MM');
        $prev_year = $month == 1 ? $date->clone()->subYear()->year : $date->year;
        $attendanceRecords = attendanceRecord::where('user_id', $auth_user_id)
                                     ->whereIn('date_year_month', [$previousMonth, $today])
                                     ->get();
        $attendance_prev_record = $attendanceRecords->where('date_year_month', $previousMonth)->first();
        $attendance_this_record = $attendanceRecords->where('date_year_month', $today)->first();
        
        $prev_shift_record = empty($attendance_prev_record)
                            ? shiftRecord::where('user_id', $auth_user_id)
                                        ->whereYear('shift_day', $prev_year)
                                        ->whereMonth('shift_day', $prev_month)
                                        ->get()
                            : [];
        
        $shift_record = shiftRecord::where('user_id',$auth_user_id)
                                    ->whereYear('shift_day',$year)
                                    ->whereMonth('shift_day', $month)
                                    ->get();
        $shift_overtime_requests = ShiftOvertimeRequest::where('created_by', $auth_user_id)
                                                        ->whereYear('overtime_day', $year)
                                                        ->whereMonth('overtime_day', $month)
                                                        ->select('overtime_day', 'created_by', 'minutes')
                                                        ->get();
        $shiftNotSubmittedList = [];
        $shiftSubmittedList = [];
        $timecardNotSubmittedList = [];
        $numberOfDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        if($user_detail) {
            $leave_start = $user_detail->leave_start;
            $leave_end = $user_detail->leave_end;
        }
        if(count($shift_record) < $numberOfDays){
            $shiftNotSubmittedList[] = [
                'year' => $year, 
                'month' => (int) $month , 
                'value' => $today
            ];
        }else{
            if(empty($attendance_this_record)){
                foreach($shift_record as $value){
                    $shiftSubmittedList[$value->shift_day] = [
                        "type" => $value->shift_type,
                        "status" => $value->status_flag
                    ];
                }
                if(!empty($prev_shift_record)){
                    foreach($prev_shift_record as $valuePrev){
                        $shiftSubmittedList[$valuePrev->shift_day] = [
                            "type" => $valuePrev->shift_type,
                            "status" => $valuePrev->status_flag,  
                        ];
                    }
                }
                foreach($shiftSubmittedList as $date => $value2){
                    if (!is_array($value2) || $value2['type'] != 1) {
                        continue;
                    }
                    if (isset($leave_start, $leave_end) && $date >= $leave_start && $date <= $leave_end) {
                        continue;
                    }
                    if($date <= $yesterday){
                        $timecard = timecardRecord::where('deleted_at', null)
                                                    ->where('user_id', $auth_user_id )
                                                    ->where('day', $date)
                                                    ->with('timecard_costs')
                                                    ->with('custom_field_data_records')
                                                    ->with('department')
                                                    ->first();
                        if($timecard === null){
                            $dateExplode = explode("-",$date);
                            $timecardNotSubmittedList[] = [
                                'year' => (int) $dateExplode[0],
                                'month' => (int) $dateExplode[1],
                                'day' =>  (int) $dateExplode[2],
                                'value' => $date,
                                'shiftStatus' => $value2['status'],
                                'shiftEndTime' => $shift_record && count($shift_record) > 0 ? $shift_record[0]->end_time : '18:00:00',
                                'shiftStartTime' => $shift_record && count($shift_record) > 0 ? $shift_record[0]->start_time : '09:00:00',
                                'shiftOverTimeRequest' => $shift_overtime_requests->where('overtime_day', $date)->first()
                            ];

                        }else if($timecard->status_flag == 0 || $timecard->status_flag == 10){
                            $dateExplode = explode("-",$date);
                            $timecardNotSubmittedList[] = [
                                'year' => (int) $dateExplode[0],
                                'month' => (int) $dateExplode[1],
                                'day' =>  (int) $dateExplode[2],
                                'value' => $date,
                                'shiftStatus' => $value2['status'],
                                'costs' => $timecard->timecard_costs,
                                'customData' => $timecard->custom_field_data_records,
                                'department' => $timecard->department,
                                'user_id' => $timecard->user_id,
                                'work_group_id' => $timecard->work_group_id,
                                'shiftEndTime' => $timecard->edit_end_time,
                                'shiftStartTime' => $timecard->edit_start_time,
                                'shiftOverTimeRequest' => $shift_overtime_requests->where('overtime_day', $date)->first()
                            ];
                        }

                    }

                    
                }
            }
        }
        if(!empty($shift_record)){
            if(!empty($timecardNotSubmittedList)){
                foreach ($timecardNotSubmittedList as $Detail) {
                    $ArrDate[] = $Detail['value'];
                }
                array_multisort($ArrDate, SORT_DESC, SORT_NUMERIC, $timecardNotSubmittedList);
            }
        }
        $data = [
            
            'timecard_notSubmitted' => $timecardNotSubmittedList,
            'shift_notSubmitted' => $shiftNotSubmittedList
            
        ];
        return response()->json($data);
    }
    public function remind_task_untouched() {
        $active_user = $this->active_user();
        $tasks = taskRecord::whereHas('executors', function ($q) use($active_user) {
            $q->where('users.id', $active_user->id)->where('progress_flag', 0);
        })
        ->with([
            'executors', 
            'files', 
            'supervisors', 
            'project', 
            'board.board_to_users.user'
        ])
        ->whereRaw('NOW() > DATE_ADD(created_at, INTERVAL TIMESTAMPDIFF(SECOND, created_at, CONCAT(end_at, " 23:59:59")) / 2 SECOND)')
        ->orderBy('created_at', 'desc')
        ->get();
        $data = [
            'remind_task_untouched' => $tasks
        ];
        return response()->json($data);
    }
    public function remind_task_unfinished() {
        $active_user = $this->active_user();
        $tasks = taskRecord::whereHas('executors', function ($q) use ($active_user) {
            $q->where('users.id', $active_user->id)->where('progress_flag', 1);
        })
        ->with(['executors', 'files', 'supervisors', 'project', 'board.board_to_users'])
        ->whereRaw('NOW() > DATE_ADD(created_at, INTERVAL TIMESTAMPDIFF(SECOND, created_at, CONCAT(end_at, " 23:59:59")) * 0.8 SECOND)')
        ->orderBy('created_at', 'desc')
        ->get();
        $data = [
            'remind_task_unfinished' => $tasks
        ];
        return response()->json($data);
    }
    public function remind_unsigned_messages(){
        $active_user = $this->active_user();
        $auth_id = $active_user->id;   
        $list = boardToUser::where('user_id', $auth_id)
                            ->where('deleted_status', 0)
                            ->pluck('record_id');
                 
        $comment_list_pre = messageRecord::whereIn('record_id', $list)
        ->whereHas('message_files', function ($query) use ($auth_id) {
            $query->where('sign_flag', 1)->whereHas('unsignedUsers', function ($q) use ($auth_id) {
                $q->where('user_id', $auth_id)->where('cancel_flag', 0);
            });
        })
        ->with('user')
        ->with(['message_files', 'message_files.unsignedUsers', 'message_files.signedUsers'])
        ->with('reactedUsers')
        ->with('checkedUsers')
        ->with('uncheckedUsers')
        ->select('check_flag', 'created_at', 'id', 'message', 'record_id', 'user_id', 'info_flag')
        ->get();
           
        $data = [
            'remind_unsigned_messages' => $comment_list_pre
        ];
       
        return response()->json($data);
    }
    public function remind_unchecked_messages(){
        $user = $this->active_user();
        $start_point = Carbon::parse('2023-03-13 00:00:00')->format('Y-m-d');
        $list = boardToUser::where('user_id', $user->id)
                            ->where('deleted_status', 0)
                            ->pluck('record_id');
        $checkMessages = messageRecord::
            whereIn('record_id', $list)
            ->whereHas('checkUsers', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->where('checked', 0);
            })
            ->whereDate('check_request_at', '>', $start_point)
            ->where('deleted_flag', '0')
            ->where('check_flag', 1)
            ->with('messageRemindUsers')
            ->with('user')
            ->with('message_files', 'message_files.unsignedUsers', 'message_files.signedUsers')
            ->with('reactedUsers')
            ->with('checkedUsers')
            ->with('uncheckedUsers')
            ->select('check_flag', 'created_at', 'id', 'message', 'record_id', 'user_id', 'info_flag')
            ->get();

        $data = [
            'remind_unchecked_messages' => $checkMessages
        ];
        return response()->json($data);
    }
    public function remind_timesheet(){
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
        if(in_array($active_user->id, $ids)){
            $pms = User::where('position_id', 6)
                        ->where('retire', 0)
                        ->where('partner_flag', 0)
                        ->where('deleted_flag', 0)
                        ->where('on_leave', 0)
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
        $data = [
            "remind_timesheet" => $list
        ];
        return response()->json($data);
    }
    public function remind_task_not_approved(){
        $active_user = $this->active_user();
        $tasks = taskRecord::where('comp_flag', 0)
                            ->whereHas('supervisors', function ($q) use($active_user) {
                                $q->where('users.id', $active_user->id)
                                    ->where('supervisor', 1);
                            })
                            ->whereHas('executors', function ($q) {
                                $q->where('status_flag', 1);
                            })
                            ->with([
                                'executors' => function ($q) {
                                    $q->where('status_flag', 1);
                                },
                                'supervisors' => function ($q) use($active_user) {
                                    $q->where('users.id', $active_user->id)
                                        ->where('supervisor', 1);
                                },
                                'files', 'board.board_to_users', 'project'
                            ])
                            ->get();
        $data = [
            "remind_task_not_approved" => $tasks
        ];
        return response()->json($data);
    }
    public function remind_project_not_approved() {
        $user = Auth::user();
        if ($user->id === 631) {
            $members = $this->getAdminMembers();
        } else {
            $members = [];
        }
        // elseif ($user->position_id == 6) {
        //     $members = $this->getUserMembers($user->id);
        // } elseif ($user->position_id < 6) {
        //     $members = $this->getUserManagers($user->id);
        // } else {
        //     $members = $this->getUserMentors($user->id);
        // }
        
        $data = [
            "remind_project_not_approved" => $members,
            "not_approved_increases" => $user->id === 604 || $user->id === 631 ? $this->not_approved_increases() : []
        ];
        return response()->json($data);
    }

    private function not_approved_increases(){
        $evaluations = EvaluationRecord::where('status', 2)
                                        ->where('created_at', '>', Carbon::now()->subMonths(3))
                                        ->with('user.positions', 'checklist', 'candidate', 'mentor')
                                        ->get();
        return $evaluations;
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
    
    private function getUserMembers($userId) {
        return User::where(column: function ($query) use ($userId) {
            $query->whereHas('salary_issues', function ($q) use ($userId) {
                    $q->where('mentor_id', $userId)->where('status', 2);
                });
                
            
            $query->orWhereHas('outcome_goals', function ($q) use ($userId) {
                    $q->where(function ($subQuery) {
                        $subQuery->where('status', 2)
                                ->orWhere('status', 4);
                    })
                    ->whereHas('project', function ($projectQuery) use ($userId) {
                        $projectQuery->whereHas('manager', function ($directorQuery) use ($userId) {
                            $directorQuery->where('users.id', $userId);
                        });
                    });
                });
        })
        ->with([
            'outcome_goals' => function ($query) use($userId) {
                $query->where(function ($subQuery) {
                        $subQuery->where('status', 2)
                                ->orWhere('status', 4);
                    })
                    
                    ->whereHas('project', function ($q) use($userId) {
                        $q->whereHas('manager', function ($q) use($userId) {
                            $q->where('users.id', $userId);
                        });
                    })
                    ->orWhereHas('salaryIssue', function ($query) {
                        $query->where('status', 2);
                    })
                    ->with(['salaryIssue', 'project.manager']);
            },
            'salary_issues' => function ($query) use($userId) {
                $query->where('status', 2)
                    ->where('mentor_id', $userId);
            }
        ])
        ->get();
    }
    
    private function getUserManagers($userId) {
        return User::where(column: function ($query) use ($userId) {
                    $query->whereHas('salary_issues', function ($q) use ($userId) {
                        $q->where('mentor_id', $userId)->where('status', 2);
                    });
                    $query->orWhere('position_id', 6)
                        ->whereHas('outcome_goals', function ($q) use ($userId) {
                            $q->where('status', 2)
                            ->whereHas('project', function ($projectQuery) use ($userId) {
                                $projectQuery->whereHas('director', function ($directorQuery) use ($userId) {
                                    $directorQuery->where('id', $userId);
                                });
                            });
                        });
                })
                ->with([
                    'outcome_goals' => function ($query) use($userId) {
                        $query->where('status', 2)
                            
                            ->whereHas('project', function ($q) use($userId) {
                                $q->whereHas('director', function ($q) use($userId) {
                                    $q->where('id', $userId);
                                });
                            })
                            ->orWhereHas('salaryIssue', function ($query) {
                                $query->where('status', 2);
                            })
                            ->with(['salaryIssue', 'project.manager']);
                    },
                    'salary_issues' => function ($query) use($userId) {
                        $query->where('status', 2)
                              ->where('mentor_id', $userId);
                    }
                ])
                ->get();
    }
    private function getUserMentors($userId) {
        return User::whereHas('salary_issues', function ($query) use($userId) {
                    $query->where('mentor_id', $userId)
                        ->where('status', 2);
                })->with([
                    'outcome_goals' => function ($query) {
                        $query->whereHas('salaryIssue', function ($q) {
                            $q->where('status', 2);
                        })->with(['salaryIssue', 'project']);
                    },
                    'salary_issues' => function ($query) {
                        $query->where('status', 2);
                    },
                ])
                ->get();
    }
    public function remind_reminded_messages(){
        $user = $this->active_user();
        // $list = boardRecord::whereHas('board_to_users', function($q) use($user){
        //     $q->where('user_id', $user->id)->where('deleted_status', 0);
        // })->pluck('id')->toArray();
        $list = boardToUser::where('user_id', $user->id)
                            ->where('deleted_status', 0)
                            ->pluck('record_id');
        $remindedMessages = messageRecord::whereIn('record_id', $list)
            ->whereHas('messageRemindUsers', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->where('reminded', 1);
            })
            ->where('deleted_flag', 0)
            ->with('messageRemindUsers')
            ->with('user')
            ->with('message_files', 'message_files.unsignedUsers', 'message_files.signedUsers')
            ->with('reactedUsers')
            ->with('checkedUsers')
            ->with('uncheckedUsers')
            ->select('check_flag', 'created_at', 'id', 'message', 'record_id', 'user_id', 'info_flag')
            ->get();
        $data = [
            "remind_reminded_messages" => $remindedMessages
        ];
        return response()->json($data);
    }
    public function remind_planned_leave(){
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
        $data = [
            "remind_planned_leave" => $list
        ];
        return response()->json($data);
    }
    public function remind_form() {
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
        $data = [
            'remind_form' => $forms
        ];
        return response()->json($data);
    }
    public function remind_asset(){
        $active_user = $this->active_user();

        $target_assets = AssetRecord::where(function ($query) use ($active_user) {
            $query->whereHas('requests', function ($query) use ($active_user) {
                $query->where('status', 1)->where('to_user', $active_user->id)
                ->whereHas('steps', function($query){
                    $query->where('value', 2)->whereNull('approved_by');
                });
            });
        })
        ->with(['requests' => function($q){
            $q->with(['send_user', 'recieve_user', 'steps' => function($q){
                $q->where('value', 2)->with('approver');
            }]);
        }])->get();
        return response()->json(["remind_asset" => $target_assets]);
    }
    public function remind_temp_reserved_schedules(){
        $active_user = $this->active_user();
        $userId = $active_user->id;
        $records = CalendarRecord::where('temp_flag', 1)->where('date_start', '>=', Carbon::today()->startOfMonth())
            ->whereHas('calendar_users', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })->with([
                'calendar_users',
                'department',
                'task',
                'updated_by',
                'created_by',
                'files',
                'calendar_view_users',
    
            ])->get();
        return response()->json([
            'remind_temp_reserved_schedules' => $records
        ]);
        
        
    }
    public function remind_departure_report($badge = false) {
        if(!in_array(Auth::id(), [833,832])){
            return response()->json([
                'remind_departure_report' => []
            ]);
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

        return response()->json([
            'remind_departure_report' => $target_users
        ]);
    }
    public function check_departure_report(Request $request) {
        $now = Carbon::now();
        $user = Auth::user();
        $has_target_project = $user->related_projects()->whereIn('project_records.id', [34, 36, 56])->exists();   
        $cutoff = Carbon::today()->setTime(7, 30); 
        if ($now->lessThan($cutoff) || !$has_target_project) {
            return response()->json(['should_send' => false]);
        }
        $check = shiftRecord::where('user_id', Auth::id())
                ->where('shift_day', Carbon::now()->toDateString())
                ->where('shift_type', 1)
                ->whereNull('departure_report')
                ->exists();
        return response()->json(['should_send' => $check]);
    }
    public function remind_challenge_progress()
    {
        $now = now();
        $active_user = $this->active_user();
        $challenges = PostRecord::query()
            ->where('app_type', 2)
            ->where('status_flag', 0)
            ->whereHas('to_users', function ($q) use ($active_user) {
                $q->where('users.id', $active_user->id);
            })
            ->whereNotNull('date_start')
            ->whereNotNull('date_end')
            ->where('date_start', '<=', $now)
            ->where('date_end', '>=', $now)    // active window
            ->orderByDesc('date_start')
            ->get();

        if (!$challenges->count()) {
            return [];
        }
        $data = $challenges->map(function ($challenge) use ($now) {
            $start = Carbon::parse($challenge->date_start);
            $end   = Carbon::parse($challenge->date_end);

            $elapsed = $start->diffInSeconds($now);
            $total   = max(1, $start->diffInSeconds($end));
            $pct     = (int) round(($elapsed / $total) * 100);
            $pct     = max(0, min(100, $pct));
            if ($pct < 50) {
                return null; // skip if less than 50%
            }
            return $challenge;
        })->filter()->values();
        return response()->json([
            'remind_challenge_progress' => $data
        ]); 
    }
    public function remind_overdue()
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

        return response()->json([
            'remind_overdue' => $members,
            'overdue_grace_count' => $members->where('has_overdue_grace', true)->count(),
        ]);
    }

    public function remind_goal_slot()
    {
        $active_user = $this->active_user();
        $userId = $active_user->id;
        $now = Carbon::now();

        // Fiscal year starts April 1
        $fiscalYear = $now->month >= 4 ? $now->year : $now->year - 1;

        $firstStart = Carbon::create($fiscalYear, 4, 1)->startOfDay();
        $firstEnd   = Carbon::create($fiscalYear, 9, 30)->endOfDay();
        $secondEnd  = Carbon::create($fiscalYear + 1, 3, 31)->endOfDay();

        $isFirstHalf  = $now->between($firstStart, $firstEnd);
        $current_half = $isFirstHalf ? 'first' : 'second';
        $halfEnd      = $isFirstHalf ? $firstEnd : $secondEnd;

        // months left in this half, inclusive (Feb..Mar = 2)
        $monthsRemaining = $now->copy()->startOfMonth()
            ->diffInMonths($halfEnd->copy()->startOfMonth()) + 1;

        $evaluation = EvaluationRecord::where('user_id', $userId)
            ->where('year', $fiscalYear)
            ->where('which_half', $current_half)
            ->first();

        // total slots required for the half
        $monthsTotal = (int) ($evaluation?->monthly_goal_slot ?? 0);

        if ($monthsTotal <= 0) {
            return response()->json(['remind_goal_slot' => []]);
        }

        // how many goals should exist by now
        $should_have = max(0, $monthsTotal - $monthsRemaining);

        $goals = ProjectGoal::where('user_id', $userId)
            ->where('year', $fiscalYear)
            ->where('which_half', $current_half)
            ->count();

        $needs = max(0, $should_have - $goals);

        return response()->json([
            'remind_goal_slot' => $needs > 0 ? [[
                'user' => $active_user,
                'needs' => $needs,
                'year' => $fiscalYear,
                'half' => $current_half,
            ]] : []
        ]);
    }

    private function remindCollect() {
        $user = $this->active_user();
        $remindedMessages = $this->reminderMessageService->getReminderMessagesForUser($user, ['all']);
        $remindedTasks = $this->remindTaskService->getReminderTaskForUser($user, ['all']);
        $remindForm = $this->remind_form()->getData(true);
        $remindOverdueGoals = $this->remind_overdue()->getData(true);
        
        // $responses = [
        //     // 'remind_task_untouched'        => $this->remind_task_untouched()->getData(true),
        //     // 'remind_task_unfinished'       => $this->remind_task_unfinished()->getData(true),
        //     // 'remind_unsigned_messages'     => $this->remind_unsigned_messages()->getData(true),
        //     // 'remind_unchecked_messages'    => $this->remind_unchecked_messages()->getData(true),
        //     'remind_timesheet'             => $this->remind_timesheet()->getData(true),
        //     // 'remind_task_not_approved'     => $this->remind_task_not_approved()->getData(true),
        //     'remind_project_not_approved'  => $this->remind_project_not_approved()->getData(true),
        //     // 'remind_reminded_messages'     => $this->remind_reminded_messages()->getData(true),
        //     'remind_planned_leave'         => $this->remind_planned_leave()->getData(true),
        //     // 'remind_form'                  => $this->remind_form()->getData(true),
        //     'remind_asset'                 => $this->remind_asset()->getData(true),
        //     'remind_temp_reserved_schedules'=> $this->remind_temp_reserved_schedules()->getData(true),
        //     'remind_departure_report'      => $this->remind_departure_report(true)->getData(true),
        //     'remind_challenge'             => $this->remind_challenge_progress()->getData(true),
        //     'remind_overdue'               => $this->remind_overdue()->getData(true),
        //     'remind_goal_slot'             => $this->remind_goal_slot()->getData(true)
        // ];
        $merged = array_merge(
            $remindedMessages, 
            $remindedTasks, 
            $remindForm, 
            $remindOverdueGoals,
            $this->remind_timesheet()->getData(true),
            $this->remind_project_not_approved()->getData(true),
            $this->remind_planned_leave()->getData(true),
            $this->remind_asset()->getData(true),   
            $this->remind_temp_reserved_schedules()->getData(true),
            $this->remind_departure_report(true)->getData(true),
            $this->remind_challenge_progress(),
            $this->remind_overdue()->getData(true),
            $this->remind_goal_slot()->getData(true)
        );
       
        // return $responses;
        return $merged;
    }
    public function remind_summary(Request $request) {
        $collected = $this->remindCollect();
        return response()->json($collected);
        $combinedData = array_map(
            fn($response, $index) => array_merge($response, ['order' => $index]),
            $collected,
            array_keys($collected)
        );
        return response()->json($combinedData);
    }
    public function remind_badge(Request $request) {
        $collected = $this->remindCollect();
        
        $count = 0;
        $counts = [];
        $now = Carbon::now();
        $test = [];
        foreach ($collected as $key => $response) {
           
            if ($key === 'remindedMessages'){
                $counts[$key] = count($response);
                continue;
            }

            
            if( $key === 'overdue_grace_count') {
                $counts['remind_overdue_grace'] =  $response ?? 0;
            }else 
            if (!empty($response)) {
                $test[$key] = count($response);
                $count += count($response);
            }
            
            
            
        }
        $counts['total'] = $count;
    
        return response()->json($test);
    }
    public function get_today_readable(){
        $active = $this->active_user();
        $now = Carbon::now()->format('Y-m-d');
        $typeId = 43;
        $latestId = customFieldDataRecord::where('type_id', $typeId)
            ->where('date', $now)
            ->whereNotNull('value_text')
            ->max('id') ?? 0;
        
        $read = customFieldRead::where('user_id', $active->id)
            ->where('type_id', $typeId)
            ->first();

        $latestReadId = $read?->last_read_customfield_id ?? 0;

        $hasUnread = $latestId > $latestReadId;

        return response()->json([
            'has_unread' => $hasUnread,
            'latest_id'  => $latestId,
        ]);
    }
    public function near_deadline_goals(){
        $user = $this->active_user();
        $today = Carbon::now()->format('Y-m-d');
        $year_ago = Carbon::now()->subYear()->format('Y-m-d');
        $base = $user->outcome_goals()->query()
                    ->whereIn('status', [0,1,5,6,8])
                    ->whereNotNull('start_date')
                    ->whereNotNull('end_date')
                    // ->where('end_date', '>=', $today)
                    ->whereBetween('start_date', [$year_ago, $today]);
                    // ->get();


        $nearGoals = $base->where('end_date', '>=', $today)->get();

        $overDeadlineGoals = $base->where('end_date', '<', $today)->get();

        
        $goalsElapsed80ofDuration = $nearGoals->filter(function ($goal) use ($today) {
            $start = Carbon::parse($goal->start_date);
            $end = Carbon::parse($goal->end_date);
            $elapsed = $start->diffInSeconds(Carbon::parse($today));
            $total = max(1, $start->diffInSeconds($end));
            $pct = ($elapsed / $total) * 100;
            return $pct >= 80;
        })->values();
        return response()->json([    
            'near_deadline_goals' => $goalsElapsed80ofDuration,
            'over_deadline_goals' => $overDeadlineGoals,
        ]);
        

    }
    public function badge_summary()
    {
        $user = $this->active_user();
        $cacheKey = "badge_summary:user:{$user->id}";
        $ttl     = 60;
        $post_result = [
            'created' => 0,
            'changed' => 0,
            'changed_ids' => [],
            'progress_report' => 0,
            'progress_report_ids' => [],
            'last_chargeable' => 0,
            'last_chargeable_ids' => [],
        ]; 
        $data = Cache::remember($cacheKey, $ttl, function () use ($user, $post_result) {
            return [
                'notice' => 0,
                'post' => $user->partner_flag === 0 && $user->position_id !== 15 && $user->linkable === 0 ? $this->badgeService->post($user) : $post_result,
                'members_goals' => [],
                'managers_goals' => [],
                'salary_issue' => [],
                'asset' => [],
                'task_comment' => $this->badgeService->taskComment($user),
                'finance_comment' => $user->position_id <= 6 || in_array($user->id, [610,608]) ? $this->badgeService->financeComment($user) : ['total_unread' => 0, 'projects' => []],
                'goal_issue_comment' => [],
                'contact_comment' => $this->badgeService->contactComment($user),
                'today_readable' => $this->badgeService->todayReadable($user),
                'project_report' => $this->badgeService->getProjectUnreadCount($user),
                'check_item_confirm' => $this->badgeService->checkItemConfirm($user),
            ];
        });
        return response()->json($data);
    }
}
