<?php

namespace App\Http\Controllers;

use App\Models\attendanceRecord;
use App\Models\boardToUser;
use App\Models\CustomForm;
use App\Models\messageRecord;
use App\Models\ProjectRecord;
use App\Models\ShiftOvertimeRequest;
use App\Models\shiftRecord;
use App\Models\taskRecord;
use App\Models\timecardRecord;
use App\Models\User;
use App\Models\workTemp;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RemindController extends Controller
{
    private function active_user(){
        $sub = Auth::user()->linked()->where('main_id', Auth::id())->wherePivot('active', 1)->first();
        if($sub){
            return $sub;
        }else{
            return Auth::user();
        }
    }
    public function notSubmitted(Request $request){
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

                        }else if($timecard->status_flag == 0){
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
    public function get_not_started_tasks() {
        $active_user = $this->active_user();
        $tasks = taskRecord::whereHas('executors', function ($q) use($active_user) {
            $q->where('users.id', $active_user->id)->where('progress_flag', 0);
        })
        ->with([
            'executors', 
            'files', 
            'supervisors', 
            'project', 
            'board.board_to_users'
        ])
        ->whereRaw('NOW() > DATE_ADD(created_at, INTERVAL TIMESTAMPDIFF(SECOND, created_at, CONCAT(end_at, " 23:59:59")) / 2 SECOND)')
        ->orderBy('created_at', 'desc')
        ->get();
        $data = [
            'not_started_tasks' => $tasks
        ];
        return response()->json($data);
    }
    public function get_not_completed_tasks() {
        $active_user = $this->active_user();
        $tasks = taskRecord::whereHas('executors', function ($q) use ($active_user) {
            $q->where('users.id', $active_user->id)->where('progress_flag', 1);
        })
        ->with(['executors', 'files', 'supervisors', 'project', 'board.board_to_users'])
        ->whereRaw('NOW() > DATE_ADD(created_at, INTERVAL TIMESTAMPDIFF(SECOND, created_at, CONCAT(end_at, " 23:59:59")) * 0.8 SECOND)')
        ->orderBy('created_at', 'desc')
        ->get();
        $data = [
            'not_completed_tasks' => $tasks
        ];
        return response()->json($data);
    }
    public function getUnsignedUsers(Request $request){
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
            'unsigned_messages' => $comment_list_pre
        ];
       
        return response()->json($data);
    }
    public function getUncheckedMessage(Request $request){
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
            'unchecked_messages' => $checkMessages
        ];
        return response()->json($data);
    }
    public function not_approved(Request $request){
        $date = Carbon::now();
        $day = $date->day;
        $year = $date->year;
        $month = $date->month;
        $prev_month = $month == 1 ? $month : $date->clone()->subMonth()->month;
        $shift_month = $day >= 25 ? $date->clone()->addMonthNoOverflow()->month : $month;
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
                            'time_card_records' => function ($q) use($year, $month, $workGroupIds, $prev_month) {
                                $q->whereYear('day', $year)
                                    ->where(function ($query) use ($month, $prev_month) {
                                        $query->whereMonth('day', $month)
                                            ->orWhereMonth('day', $prev_month);
                                    })
                                    ->where('status_flag', 1)
                                    ->whereIn('work_group_id', $workGroupIds);
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
            $timeCardsCount = $user->time_card_records->count();
            $overtimeRequests = $user->shift_overtime->count();
            $shiftCount = $user->shift_records;
            
             $d = [
                "user" => $user,
                "timecard" => $timeCardsCount,
                "overtime" => $overtimeRequests,
                "shift" => $shiftCount,
            ];
            if($timeCardsCount || $overtimeRequests || count($shiftCount)){                    
                $list[] = $d;
            }
        }
        $data = [
            "not_approved_time_sheets" => $list
        ];
        return response()->json($data);
    }
    public function task_not_approved(){
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
            "not_approved_tasks" => $tasks
        ];
        return response()->json($data);
    }
    public function project_not_approved() {
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
            "not_approved_projects" => $members
        ];
        return response()->json($data);
    }
    
    private function getAdminMembers() {
        return User::whereHas('outcome_goals', function ($query) {
                $query->where('status', 3)
                      ->orWhereHas('salaryIssue', function ($query) {
                          $query->where('status', 3);
                      });
            })
            ->orWhereHas('salary_issues', function ($query) {
                $query->where('status', 3);
            })
            ->with([
                'outcome_goals' => function ($query) {
                    $query->where('status', 3)
                          ->orWhereHas('salaryIssue', function ($query) {
                              $query->where('status', 3);
                          })
                          ->with(['salaryIssue', 'project']);
                },
                'salary_issues' => function ($query) {
                    $query->where('status', 3);
                }
            ])
            ->get();
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
    public function getRemindMessage(){
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
            "reminded_messages" => $remindedMessages
        ];
        return response()->json($data);
    }
    public function get_temp_data(Request $request){
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
            $endDate = Carbon::parse($startDate)->addYear()->format('Y-m-d');
            $temp['notification_user'] = $notificationUser;
            $temp['endDate'] = $endDate;
            $planned_shifts = shiftRecord::whereBetween('shift_day', [$startDate, $endDate])->where('shift_type', 3)->where('user_id', Auth::id())->count();
            $plannedDateCarbon = Carbon::createFromFormat('Y-m-d', $startDate);
            $remaining_days = $plannedDateCarbon->year === 2023 ? 0 : $temp->planned_days - $planned_shifts;
            if($remaining_days > 0){
                $list[] = [
                    "shift_count" => $planned_shifts,
                    "tempData" => $temp,
                    "remaining_days" => $remaining_days,
                ];
                
            }
        }
        $data = [
            "paid_leaves" => $list
        ];
        return response()->json($data);
    }
    public function get_not_answered_forms() {
        $active_user = $this->active_user();
        $forms = CustomForm::whereHas('users', function ($q) use ($active_user) {
            $q->where('users.id', $active_user->id);
        })->whereDoesntHave('survey_answers')
            ->with(['users', 'admins', 'survey_answers' => function ($query) {
                $query->select('user_id', 'custom_form_id'); // Load only necessary fields
            }])->get();
        
        $forms->each(function ($form) {
            $answeredUserIds = $form->survey_answers->pluck('user_id')->toArray();
            $form->users->each(function ($user) use ($answeredUserIds) {
                $user->is_answered = in_array($user->id, $answeredUserIds);
            });
        });
        $data = [
            'not_answered_forms' => $forms
        ];
        return response()->json($data);
    }
    public function get_remind_badge(Request $request) {
        $responses = [];
    
        $responses['not_started_tasks'] = $this->get_not_started_tasks()->getData(true);
        $responses['not_completed_tasks'] = $this->get_not_completed_tasks()->getData(true);
        $responses['unsigned_messages'] = $this->getUnsignedUsers($request)->getData(true);
        $responses['unchecked_messages'] = $this->getUncheckedMessage($request)->getData(true);
        $responses['not_approved_time_sheets'] = $this->not_approved($request)->getData(true);
        $responses['not_approved_tasks'] = $this->task_not_approved()->getData(true);
        $responses['not_approved_projects'] = $this->project_not_approved()->getData(true);
        $responses['reminded_messages'] = $this->getRemindMessage()->getData(true);
        $responses['paid_leaves'] = $this->get_temp_data($request)->getData(true);
        $responses['not_answered_forms'] = $this->get_not_answered_forms()->getData(true);
        $count = 0;
        $counts = [];
        foreach ($responses as $key => $response) {
            if ($key === 'reminded_messages') {
                $counts[$key] = count($response[$key]);
            } else {
                $count += count($response[$key]);
            }
        }
        $counts['total'] = $count;
    
        return response()->json($counts);
    }
}
