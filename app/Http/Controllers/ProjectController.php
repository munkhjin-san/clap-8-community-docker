<?php

namespace App\Http\Controllers;

use App\Models\ProjectEvaluation;
use App\Models\ProjectMember;
use App\Models\ProjectRecord;
use App\Models\ProjectSetIncrease;
use App\Models\SalaryIssue;
use App\Models\ProjectGoal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    //
    public function get_projects(Request $request) {
        $evaluation_date = $request->evaluation_date;
        $projects = ProjectRecord::with(['members' => function ($q) use ($evaluation_date) {
                        $q->with(['evaluation' => function ($q) use ($evaluation_date) {
                            $q->whereDate('date', $evaluation_date)
                                ->with('mentor');
                        }])->where('retire', 0);
                    }])
                    ->with(['manager' => function ($q) use ($evaluation_date) {
                        $q->with(['evaluation' => function ($q) use ($evaluation_date) {
                            $q->whereDate('date', $evaluation_date)
                                ->with('mentor');
                        }])->where('retire', 0);
                    }])
                    ->with('director')
                    ->get();
        $sortedProjects = $projects->sortByDesc(function ($project) {
            $isMember = in_array(Auth::id(), $project->members->pluck('id')->toArray());
            $isManager = in_array(Auth::id(), $project->manager->pluck('id')->toArray());
            $isDirector = $project->director && $project->director->id == Auth::id();
            if ($isMember) {
                return 3;
            } elseif ($isManager) {
                return 2;
            } elseif ($isDirector) {
                return 1;
            }
            return 0;
        })->values();

        return response()->json($sortedProjects);
    }

    public function update_projects() {
        $projects = ProjectRecord::get();
        $user_name = env('KINTONE_USER_NAME');
        $password = env('KINTONE_PASSWORD');
        $string = "{$user_name}:{$password}";
        $x_token = base64_encode($string);
        $headers = [
            'Authorization' => 'Basic', 
            'X-Cybozu-Authorization' => $x_token
        ];
        foreach($projects as $project){
            $departmentQueryParams = [
                'app' => '26',
                'query' => "部門 = \"{$project->name}\"",
            ];
            $departmentQueryString = http_build_query($departmentQueryParams);
            $departmentUrl = "https://glowd-hldgs.cybozu.com/k/v1/records.json?" . $departmentQueryString;
    
            
    
            $departmentResponse = Http::withHeaders($headers)->get($departmentUrl);
            if (isset($departmentResponse->json()['records']) && count($departmentResponse->json()['records'])){
                foreach($departmentResponse->json()['records'] as $department) {
                    $project->overview = $department['文字列__複数行__1']['value'];
                    $project->strategy = $department['文字列__複数行__2']['value'];
                    $project->kgi = $department['文字列__複数行__3']['value'];
                    $project->kpi = $department['文字列__複数行__4']['value'];
                    $project->save();
                }
                
            }
            
        }
        return response()->json($projects);
    }
    public function get_outcome_goals(Request $request) {
        $request->validate([
            'target_period' => 'required',
            'user_id' => 'required'
        ]);

        $project_goals = ProjectGoal::where('target_period', $request->target_period)
                                    ->where('user_id', $request->user_id)
                                    ->with('project')
                                    ->with(['salaryIssue' => function ($q) {
                                        $q->with('files');
                                    }])
                                    ->get();
        return response()->json($project_goals);
        // $members = $request->members ?? [];
        // $date = $request->date ?? '';
        // if (empty($members)) {
        //     return response()->json([]);
        // }
        
        // $membersCollection = collect($members);
        // $ids = $membersCollection->pluck('id');

        // $user_name = env('KINTONE_USER_NAME');
        // $password = env('KINTONE_PASSWORD');
        // $string = "{$user_name}:{$password}";
        // $x_token = base64_encode($string);
        // $headers = [
        //     'Authorization' => 'Basic', 
        //     'X-Cybozu-Authorization' => $x_token
        // ];
        // $data = [];
        // foreach($members as $member){
        //     $queryParams = [
        //         'app' => '954',
        //         'query' => "社員ｺｰﾄﾞ = {$member['user_code']} and ドロップダウン in (\"{$date}\")",
        //         // 'fields' => ['社員ｺｰﾄﾞ', '氏名', 'KGI', 'KPI', '職務評価基準', '職務遂行のための基準', '成果評価テーブル', 'ドロップダウン', '更新日時', '作成日時']
        //     ];
        //     $queryString = http_build_query($queryParams);
        //     $url = "https://glowd-hldgs.cybozu.com/k/v1/records.json?{$queryString}";
        //     $responseData = Http::withHeaders($headers)->get($url);
        //     $data = array_merge($data, $responseData->json()['records']);
        // }
        // return response()->json($data);
        
    }
    public function get_member($projectId, $memberId)
    {
        $project = ProjectRecord::with('members')->find($projectId);

        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        $member = $project->members->find($memberId);

        if (!$member) {
            return response()->json(['message' => 'Member not found'], 404);
        }
        return response()->json($member);
    }
    public function get_project_criteria(Request $request){
        
        
        $user_name = env('KINTONE_USER_NAME');
        $password = env('KINTONE_PASSWORD');
        $string = "{$user_name}:{$password}";
        $x_token = base64_encode($string);
        $headers = [
            'Authorization' => 'Basic', 
            'X-Cybozu-Authorization' => $x_token,
        ];
        $appId = '1272';
        $fields = ['文字列__1行__1', 'テーブル'];
        $limit = 30;
        $query = $request->first
            ? "limit $limit"
            : ($request->keywords ? "文字列__1行__1 like \"" . addslashes($request->keywords) . "\" limit $limit" : "limit $limit");

        if (!$request->keywords) {
            $limit = 20;
            $query = "limit $limit";
        }
        $queryParams = [
            'app' => $appId,
            'query' => $query,
            'fields' => $fields
        ];
        
        $queryString = http_build_query($queryParams);
        $url = "https://glowd-hldgs.cybozu.com/k/v1/records.json?{$queryString}";
        
        $response = Http::withHeaders($headers)->get($url);
        $responseData = $response->json();
        $recieve = [];
        $levels = [];
        if (array_key_exists('records', $responseData) && !empty($responseData['records'])) {
            foreach ($responseData['records'] as $record) {
                $standards = [];
                if (isset($record['テーブル']['value'])) {
                    foreach ($record['テーブル']['value'] as $standard) {
                        if (isset($standard['value']['職務遂行のための基準']['value'])) {
                            $standards[] = [
                                'standard' => $standard['value']['職務遂行のための基準']['value'],
                            ];
                        }
                    }
                }
                if (isset($record['文字列__1行__1']['value'])) {
                    $levels[] = [
                        'level' => $record['文字列__1行__1']['value'],
                        'standards' => $standards, 
                    ];
                }
            }
        }

        return response()->json($levels);
    }

    public function save_project_goal(Request $request){
        // $user = Auth::user();
        // $formattedName = str_replace(' ', '', $user->name);
        // $request->validate([
        //     'goal_id' => 'required',
        // ]);
        $id = $request->goal_id;
        $params = $request->params;
        $date = $request->date;
        $projectGoal = ProjectGoal::updateOrCreate(['id' => $id], $params);
        $projectEvaluation = ProjectEvaluation::firstOrCreate(
            ['user_id' => $params['user_id'], 'date' => $date]
        );
        $projectEvaluation->current_level = $params['criteria'];
        $projectEvaluation->employment_type = $params['employment_type'];
        $projectEvaluation->save();
        // $project = ProjectRecord::find($projectGoal->project_id);
        // $user_name = env('KINTONE_USER_NAME');
        // $password = env('KINTONE_PASSWORD');
        // $string = "{$user_name}:{$password}";
        // $x_token = base64_encode($string);
        // $headers = [
        //     'Authorization' => 'Basic', 
        //     'X-Cybozu-Authorization' => $x_token,
        // ];
        // $queryParams = [
        //     'app' => 948,
        //     'query' => "文字列__1行__1 like \"" . addslashes($projectGoal->criteria) . "\"",
        // ];
        // $queryString = http_build_query($queryParams);
        // $url = "https://glowd-hldgs.cybozu.com/k/v1/records.json?{$queryString}";
        
        // $response = Http::withHeaders($headers)->get($url);
        // $responseData = $response->json();
        // $add[] = [
        //     'value' => [
        //         '該当部門' => [ 'value' => $project->name ],
        //         '期日' => [ 'value' => $projectGoal->end_date ],
        //         '成果目標' => [ 'value' => $projectGoal->outcome_goal ],
        //         '確認項目' => [ 'value' => $request->checked_items ],
        //         '事業戦略に与える影響' => [ 'value' => $projectGoal->impact ],
        //         '進捗状況' => [ 'value' => $projectGoal->progress]
        //     ]
        // ];
        
        // if ($request->record_id) {

        // } else {
        //     $selectedRecord = $responseData['records'][0];
        //     $body = [
        //         'app' => 954,
        //         'record' => [
        //             '氏名' => [
        //                 'value' => $formattedName
        //             ],
        //             '雇用形態' => [
        //                 'value' => $projectGoal->employment_type
        //             ],
        //             '配属部門' => [
        //                 'value' => $project->name
        //             ],
        //             'ドロップダウン' => [
        //                 'value' => $projectGoal->target_period
        //             ],
        //             '成果評価テーブル' => [
        //                 'value' => $add
        //             ]
        //         ]
        //     ];
        //     try {
        //         $response = Http::withHeaders($headers)
        //                         ->post('https://glowd-hldgs.cybozu.com/k/v1/record.json', $body);
        //         if ($response->successful()) {
        //             return response()->json($response->json());
        //         } else {
        //             return response()->json([
        //                 'error' => 'Failed to create record in Kintone',
        //                 'details' => $response->json()
        //             ], $response->status());
        //         }
        //     } catch (\Exception $e) {
        //         return response()->json([
        //             'error' => 'An error occurred while creating the record',
        //             'message' => $e->getMessage()
        //         ], 500);
        //     }
        // }
        return response()->json($projectGoal);
    }
    public function get_applied_goals(Request $request) {
        $request->validate([
            'startDate' => 'required',
            'endDate' => 'required',
        ]);
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $projectGoals = ProjectGoal::whereDate('start_date', '>=', $startDate)
                                ->whereDate('end_date', '<=', $endDate)
                               ->get();
        if ($projectGoals->isEmpty()) {
            return response()->json([
                'message' => 'No project goals found for the given date.'
            ], 404);
        }
    
        return response()->json([
            'message' => 'Project goals found',
            'projectGoals' => $projectGoals,
        ]);
    }
    public function update_project_progress(Request $request){
        $request->validate([
            'id' => 'required',
        ]);
        $update = ProjectGoal::findOrFail($request->id)
                                ->update($request->params);
        return response()->json($update);
    }

    public function apply_kadai(Request $request) {
        $template = SalaryIssue::find($request->record_id)->update(['status' => 2]);
        return response()->json($template);
    }

    public function get_selectable_users(Request $request) {
        $date = $request->date;
        $userList = User::where('retire', 0)
                        ->where('partner_flag', 0)
                        ->whereNotNull('user_code')
                        ->where('hide_flag', 0)
                        ->select('id', 'name', 'position_id', 'icon_id', 'user_code')
                        ->with(['evaluation' => function ($q) use($date) {
                            $q->where('date', $date)
                                ->with('mentor');
                        }])
                        ->with('positions')
                        ->get();
        // $user_list = $userList->whereNotNull('user_code')->pluck('user_code')->toArray();
        // $strings = array_map('strval', $user_list);
        // $result = '(' . implode(', ', $strings) . ')';
        // $queryParams = [
        //     'app' => '9',
        //     "query" => "社員コード in $result limit 200",
        //     'fields' => ['社員コード', '文字列__1行__15']
        // ];
        
        // $queryString = http_build_query($queryParams);
        // $url = "https://glowd-hldgs.cybozu.com/k/v1/records.json?{$queryString}";
        // $headers = [
        //     'Authorization' => 'Basic', 
        //     'X-Cybozu-API-Token' => 'BH1geaWExPVVIaa48izBjDzCilqRslkNlcZgNvp4'
        // ];
        // $recieve = [];
        // $response = Http::withHeaders($headers)->get($url);
        // $responseData = $response->json();
        // foreach ($responseData['records'] as $data){
        //     $recieve[] = ['user_code'=>$data['社員コード']['value'], 'general_position'=>$data['文字列__1行__15']['value']];
        // }
        // $userList->transform(function ($user) use ($recieve) {
        //     $kintoneData = collect($recieve)->firstWhere('user_code', $user->user_code);
        //     $user->general_position = $kintoneData['general_position'] ?? null;
        //     return $user;
        // });
        $mentors = $userList->filter(function ($user) {
            $evaluation = $user->evaluation ?? null;
            return (!empty($evaluation->general_position) && $evaluation->general_position !== '一般職') 
                    || ($user->position_id !== null && $user->position_id <= 6);
        })->values(); 
        
        $data = [
            'users' => $userList,
            'mentors' => $mentors,
        ];
        return response()->json($data);
    }
    public function create_project(Request $request) {
        $id = $request->id ?? null;
        $params = $request->params;
        $project = ProjectRecord::updateOrCreate(['id' => $id], $params);
        $members = $request->member_ids;
        $manager = $request->manager_ids;
        $project->members()->sync($members);
        $project->manager()->syncWithPivotValues($manager, ['authority' => 1]);
        return response()->json($project);
    }
    public function get_salary_options() {
        $user_name = env('KINTONE_USER_NAME');
        $password = env('KINTONE_PASSWORD');
        $string = "{$user_name}:{$password}";
        $x_token = base64_encode($string);
        $headers = [
            'Authorization' => 'Basic', 
            'X-Cybozu-Authorization' => $x_token,
        ];
        $appId = '166';
        $fields = ['文字列__1行_', '基本給', '新等級'];
        $queryParams = [
            'app' => $appId,
            'fields' => $fields
        ];
        
        $queryString = http_build_query($queryParams);
        $url = "https://glowd-hldgs.cybozu.com/k/v1/records.json?{$queryString}";
        
        $response = Http::withHeaders($headers)->get($url);
        $responseData = $response->json();
        $recieve = [];
        if(array_key_exists('records', $responseData) && $responseData['records'] && count($responseData['records'])) {
            $records = $responseData['records'];
            foreach ($records as $record) {
                $recieve[] = [
                    'basic_salary'=>$record['文字列__1行_']['value'], 
                    'salary_grade'=>$record['新等級']['value'],
                    'base_salary'=>$record['基本給']['value'],
                ];
            }
            usort($recieve, function ($a, $b) {
                return $b['base_salary'] <=> $a['base_salary'];
            });
        }
        return response()->json($recieve);
    }
    public function get_evaluations(Request $request) {
        $target_period = $request->target_period ?? null;
        $evaluations = ProjectEvaluation::where('target_period', $target_period)->with('mentor')->get();
        return response()->json($evaluations);
    }

    public function save_evaluation_grade(Request $request) {
        $id = $request->id ?? null;
        $params = $request->params;
        $update = ProjectEvaluation::updateOrCreate(['id' => $id], $params);
        return response()->json($update);
    }
    public function save_member_role(Request $request) {
        $request->validate([
            'id' => 'required',
        ]);
        $member = ProjectMember::findOrFail($request->id)->update([
            'role' => $request->role
        ]);       
        return response()->json($member);
    }
    public function get_current_evaluation(Request $request){
        $target_period = $request->target_period ?? null;
        $user_id = $request->user_id ?? null;
        $evaluation = ProjectEvaluation::where('target_period', $target_period)
                                        ->where('user_id', $user_id)
                                        ->with('mentor')->first();
        return response()->json($evaluation);
    }
    public function save_evaluation(Request $request){
        $id = $request->id ?? null;
        $candidates = $request->candidates ?? [];
        $last_candidates = $request->last_candidates ?? [];
        $params = $request->params;
        $project_increase = ProjectSetIncrease::updateOrCreate(['id' => $id], $params);

        $lastcandidateData = collect($last_candidates)->map(function ($candidate) use ($project_increase) {
            return [
                'increase_id' => $project_increase->id,
                'last_candidate' => $candidate
            ];
        })->toArray();
        $candidateData = collect($candidates)->map(function ($candidate) use ($project_increase) {
            return [
                'increase_id' => $project_increase->id,
                'next_candidate' => $candidate
            ];
        })->toArray();
        $project_increase->candidate()->delete();
        $project_increase->candidate()->createMany($candidateData);
        $project_increase->candidate()->createMany($lastcandidateData);

        $evaluations = $request->evaluations;
        $evaluation = $project_increase->evaluation()->where('user_id', $params['user_id'])->first();
        if ($evaluation) {
            if (!empty($evaluation->new_position)) {
                $evaluations['general_position'] = $evaluation->new_position;
            }
            $evaluation->update($evaluations);
        } 
        $project_increase->evaluation()->where('user_id', $params['user_id'])->update($evaluations);

        return response()->json(['message' => 'Data inserted successfully!']);
    }
    public function set_increase_request(Request $request){
        $id = $request->id ?? null;
        $skills = $request->skills ?? [];
        $candidates = $request->candidates ?? [];
        $last_candidates = $request->last_candidates ?? [];
        $params = $request->params;
        $project_increase = ProjectSetIncrease::updateOrCreate(['id' => $id], $params);

        $checklistData = collect($skills)->map(function ($checklist) use ($project_increase) {
            return [
                'increase_id' => $project_increase->id,
                'content' => $checklist,
            ];
        })->toArray();
        $lastcandidateData = collect($last_candidates)->map(function ($candidate) use ($project_increase) {
            return [
                'increase_id' => $project_increase->id,
                'last_candidate' => $candidate
            ];
        })->toArray();
        $candidateData = collect($candidates)->map(function ($candidate) use ($project_increase) {
            return [
                'increase_id' => $project_increase->id,
                'next_candidate' => $candidate
            ];
        })->toArray();
        $project_increase->checklist()->delete();
        $project_increase->candidate()->delete();
        $project_increase->candidate()->createMany($candidateData);
        $project_increase->checklist()->createMany($checklistData);
        $project_increase->candidate()->createMany($lastcandidateData);
        return response()->json(['message' => 'Data inserted successfully!']);
    }
    public function get_set_increase(Request $request) {
        $date = $request->date ?? null;
        $user_id = $request->user_id ?? null;
        $increase = ProjectSetIncrease::where('date', $date)
                                        ->where('user_id', $user_id)
                                        ->with('checklist')
                                        ->with(['salary_issues' => function ($q) use($user_id) {
                                            $q->where('user_id', $user_id);
                                        }])
                                        ->with(['outcome_goals' => function ($q) use($user_id) {
                                            $q->where('user_id', $user_id)
                                                ->where('status', 3);
                                        }])
                                        ->with(['evaluation' => function ($q) use($user_id) {
                                            $q->where('user_id', $user_id)
                                                ->with('mentor');
                                        }])
                                        ->with('candidate')->first();
        if(!$increase) {
            return;
        }
        return response()->json($increase);
    }
    public function approve_increase_request(Request $request){
        $request->validate([
            'id' => 'required',
        ]);
        $id = $request->id;
        $status_flag = $request->status_flag ?? 0;
        ProjectSetIncrease::findOrFail($id)->update(['status_flag' => $status_flag]);

        return response()->json(['message' => 'Successfully approved!']);
    }

    public function delete_project_goal(Request $request){
        $request->validate([
            'id' => 'required',
        ]);
        $id = $request->id;
        ProjectGoal::findOrFail($id)->delete();
        return response()->json(['message' => 'Successfully deleted!']);
    }
    public function approve_salary_issue(Request $request){
        $request->validate([
            'id' => 'required',
            'status' => 'required'
        ]);
        $id = $request->id;
        $status = $request->status;
        SalaryIssue::findOrFail($id)->update(['status' => $status]);

        return response()->json(['message' => 'Successfully approved!']); 
    }
    public function get_salary_issues(Request $request) {
        $date = $request->date;
        $salary_issues = SalaryIssue::where('user_id', Auth::id())
                                    ->where('date', $date)
                                    ->get();
        return response()->json($salary_issues);
    }
    public function delete_evaluation(Request $request){
        $request->validate([
            'id' => 'required',
        ]);
        $id = $request->id;
        ProjectSetIncrease::findOrFail($id)->delete();
        return response()->json(['message' => 'Successfully deleted!']);
    }
    public function delete_project(Request $request) {
        $request->validate([
            'id' => 'required',
        ]);
        $id = $request->id;
        ProjectRecord::findOrFail($id)->delete();
       
        return response()->json(['message' => 'Successfully deleted!']);
    }
    public function approve_outcome_goal(Request $request){
        $request->validate([
            'id' => 'required',
            'status' => 'required'
        ]);
        $id = $request->id;
        $status = $request->status;
        ProjectGoal::findOrFail($id)->update(['status' => $status]);
        return response()->json(['message' => 'Successfully approved!']); 
    }
    public function update_issue_report(Request $request) {
        $request->validate([
            'id' => 'required'
        ]);
        $id = $request->id;
        $result = $request->result;
        $status = $request->status;
        $issue_report = SalaryIssue::findOrFail($id);
        $issue_report->update([
            'result' => $result,
            'status' => $status
        ]);
        $issue_report->files()->sync($request->file_ids);
        return response()->json($issue_report);
    }
    public function project_not_approved() {
        $members = User::whereHas('outcome_goals', function ($q) {
                            $q->where('status', 3)->orWhereHas('salaryIssue', function ($q) {
                                $q->where('status', 3);
                            });
                        })
                        ->orWhereHas('salary_issues', function ($q) {
                            $q->where('status', 3);
                        })
                        ->with([
                            'outcome_goals' => function ($q) {
                                $q->where('status', 3)->orWhereHas('salaryIssue', function ($q) {
                                    $q->where('status', 3);
                                })->with('salaryIssue');
                            },
                            'salary_issues' => function ($q) {
                                $q->where('status', 3);
                            }
                        ])->get();
        return response()->json($members);
    }
} 
