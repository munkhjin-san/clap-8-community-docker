<?php

namespace App\Http\Controllers;

use App\Imports\YearlyPlanImport;
use App\Models\AssetRecord;
use App\Models\AssetRequest;
use App\Models\boardRecord;
use App\Models\EvaluationRecord;
use App\Models\ProjectCondition;
use App\Models\ProjectMember;
use App\Models\ProjectRecord;
use App\Models\ProjectSetIncrease;
use App\Models\SalaryIssue;
use App\Models\ProjectGoal;
use App\Models\taskRecord;
use App\Models\User;
use App\Models\ProjectFinanceComment;
use App\Models\ProjectFinanceLastRead;
use App\Models\ProjectMetric;
use App\Models\ProjectMetricFormula;
use App\Models\ProjectMetricValue;
use App\Models\ProjectExpense;
use App\Models\ProjectSale;
use Illuminate\Support\Facades\Mail;
use App\Mail\ProjectMention;
use App\Jobs\SendProjectEmail;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\BoardController;
use App\Services\SharedService;
use App\Imports\EvaluationImport;
use App\Http\Requests\StoreMetricRequest;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Google_Client;
use Google_Service_Sheets;
use Google\Service\Exception as GoogleServiceException;
use App\Http\Requests\FinanceRequest;
use DB;
use Illuminate\Support\Str;
class ProjectController extends Controller
{
    //
    protected $boardController;
    protected $sharedService;
    public function __construct(BoardController $boardController, SharedService $sharedService){
        $this->boardController = $boardController;
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
    public function get_projects(Request $request) {
        $data = $request->validate([
            'start' => ['nullable', 'date_format:Y-m-d'],
            'end'   => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start'],
            'year'        => ['nullable','integer'],
            'which_half'  => ['nullable','string'],
        ]);
        $weekStartDate = Carbon::now()->startOfWeek(CarbonInterface::MONDAY)->toDateString(); 
        $year = $data['year'] ?? null;
        $which_half = $data['which_half'] ?? null;
        $usersLoader = function (bool $withEval = false) use ($year, $which_half) {
            return function ($q) use ($withEval, $year, $which_half) {
                $q->select('users.id','users.name','users.icon_path','users.icon_bg')
                ->where('retire', 0);

                if ($withEval && $year && $which_half) {
                    $q->with(['evaluation' => fn($e) => $e->where('year', $year)
                                                        ->where('which_half', $which_half)
                                                        ->with('mentor')]);
                }
            };
        };

        $tz = 'Asia/Tokyo';

        $query = ProjectRecord::query();
        if (!empty($data['start']) && !empty($data['end'])) {
            $start = Carbon::parse($data['start'], $tz)->startOfDay();
            $end   = Carbon::parse($data['end'],   $tz)->endOfDay();

            $query->overlapping($start, $end);
        } else {
            $today = now($tz)->startOfDay();
            $query->activeOn($today);
        }
        $projects = $query->with([
                        'project_conditions' => fn($q) => $q->where('week_start_date', $weekStartDate),
                        'director:id,name,icon_path,icon_bg',
                        'manager' => $usersLoader(true),
                        'members' => $usersLoader(true),
                        'director'
                    ])
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
            'year' => 'required',
            'user_id' => 'required',
            'which_half' => 'required',
        ]);
        $year = $request->year;
        $which_half = $request->which_half;
        $user_id = $request->user_id;
        $project_goals = ProjectGoal::where('year', $year)
                                    ->where('which_half', $which_half)
                                    ->where('user_id', $user_id)
                                    ->with(['project', 'files', 'steps', 'reports' => function ($q) {
                                        $q->with('user');
                                    }])
                                    ->with(['salaryIssue' => function ($q) {
                                        $q->with(['files', 'actions']);
                                    }])
                                    ->get();
        $evalutaionRecord = EvaluationRecord::where('year', $year)
                                    ->where('which_half', $which_half)
                                    ->where('user_id', $user_id)->with('mentor')->first();
        $data = [
            'project_goals' => $project_goals,
            'evaluation' => $evalutaionRecord,
        ];
        return response()->json($data);
        
        
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

        
        
        
        // $user_name = env('KINTONE_USER_NAME');
        // $password = env('KINTONE_PASSWORD');
        // $string = "{$user_name}:{$password}";
        // $x_token = base64_encode($string);
        // $headers = [
        //     'Authorization' => 'Basic', 
        //     'X-Cybozu-Authorization' => $x_token,
        // ];
        // $appId = '1272';
        // $fields = ['文字列__1行__1', 'テーブル'];
        // $limit = 30;
        // $query = $request->first
        //     ? "limit $limit"
        //     : ($request->keywords ? "文字列__1行__1 like \"" . addslashes($request->keywords) . "\" limit $limit" : "limit $limit");

        // if (!$request->keywords) {
        //     $limit = 20;
        //     $query = "limit $limit";
        // }
        // $queryParams = [
        //     'app' => $appId,
        //     'query' => $query,
        //     'fields' => $fields
        // ];
        
        // $queryString = http_build_query($queryParams);
        // $url = "https://glowd-hldgs.cybozu.com/k/v1/records.json?{$queryString}";
        
        // $response = Http::withHeaders($headers)->get($url);
        // $responseData = $response->json();
        // $recieve = [];
        // $levels = [];
        // if (array_key_exists('records', $responseData) && !empty($responseData['records'])) {
        //     foreach ($responseData['records'] as $record) {
        //         $standards = [];
        //         if (isset($record['テーブル']['value'])) {
        //             foreach ($record['テーブル']['value'] as $standard) {
        //                 if (isset($standard['value']['職務遂行のための基準']['value'])) {
        //                     $standards[] = [
        //                         'standard' => $standard['value']['職務遂行のための基準']['value'],
        //                     ];
        //                 }
        //             }
        //         }
        //         if (isset($record['文字列__1行__1']['value'])) {
        //             $levels[] = [
        //                 'level' => $record['文字列__1行__1']['value'],
        //                 'standards' => $standards, 
        //             ];
        //         }
        //     }
        // }

        // $keyword = $request->keywords;
        // [$cat, $job, $level] = explode('_', $keyword);
        // return $cat;

        // $data = $this->get_evaluation_levels()->getData();
        // return response()->json($cat);
    }

    public function save_project_goal(Request $request){
        $id = $request->id;
        $params = $request->params;
        $projectGoal = ProjectGoal::updateOrCreate(['id' => $id], $params);
        $steps = $request->steps ?? [];
        if(count($steps)) {
            $newSteps = [];
            foreach($steps as $step) {
                $stepRecord = $projectGoal->steps()->updateOrCreate(['id' => $step['id'] ?? null], [
                    'content' => $step['content'],
                ]);
                $newSteps[] = $stepRecord->id;
            }
            $projectGoal->steps()->whereNotIn('id',  $newSteps)->delete();
        }
        
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
        $goal_report = ProjectGoal::findOrFail($request->id);
        $goal_report->update($request->params);
        $goal_report->files()->sync($request->file_ids);
        return response()->json($goal_report);
    }

    public function apply_kadai(Request $request) {
        $template = SalaryIssue::find($request->record_id)->update(['status' => 2]);
        return response()->json($template);
    }
    public function get_previous_evaluation (Request $request){
        $request->validate([
            'year' => 'required',
            'which_half' => 'required',
            'user_id' => 'required',
        ]);
        $year = $request->year;
        $which_half = $request->which_half;
        $previous_which_half = $which_half == 'first' ? 'second' : 'first';
        $previous_year = $which_half == 'first' ? $year - 1 : $year;
        $user_id = $request->user_id;

        $evaluation = EvaluationRecord::where('year', $previous_year)
                                        ->where('which_half', $previous_which_half)
                                        ->where('user_id', $user_id)
                                        ->with(['mentor', 'checklist'])
                                        ->first();
        
        return response()->json($evaluation);
    }
    public function get_selectable_users(Request $request) {
        $params = $request->params;
        $userList = User::where('retire', 0)
                        ->where('partner_flag', 0)
                        ->whereNotNull('user_code')
                        ->where('hide_flag', 0)
                        ->select('id', 'name', 'position_id', 'icon_path', 'icon_bg', 'user_code', 'general_position')
                        ->when(!empty($params), function ($q) use($params) {
                            $q->with(['evaluation' => function ($q) use($params) {
                                $q->where('year', $params['year'])
                                    ->where('which_half', $params['which_half'])
                                    ->with('mentor', 'candidate');
                            }]);
                        })                        
                        ->with('positions')
                        ->get();
        $mentors = $userList->filter(function ($user) {
            return ($user->general_position !== null && $user->general_position !== '一般職') 
                    || ($user->position_id !== null && $user->position_id < 6);
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
        $filteredParams = collect($params)->only([
            'name',
            'description',
            'private_memo',
            'mission',
            'innovation',
            'strategy_miso',
            'operation',
            'date_start',
            'date_end',
            'category',
            'partners',
            'customers',
            'industry_type',
        ])->toArray();

        $project = ProjectRecord::updateOrCreate(['id' => $id], $filteredParams);
        $members = collect($params['members'])->pluck('id')->toArray();
        $manager = collect($params['manager'])->pluck('id')->toArray();
        $project->members()->sync($members);
        $project->manager()->syncWithPivotValues($manager, ['authority' => 1]);
        $tasks = $request->tasks ?? [];
        if(count($tasks)) {
            $this->create_generated_project_tasks($tasks, $project->id);
        }
        return response()->json($project);
    }
    public function get_salary_options() {
        $queryParamsSpecs = [
            'app' => 166,
            "fields" => ['文字列__1行_', '基本給', '新等級'],
        ];
        
        $queryStringSpecs = http_build_query($queryParamsSpecs);
        $urlSpecs = "https://glowd-hldgs.cybozu.com/k/v1/records.json?$queryStringSpecs";
        $profits = Http::withHeaders($this->kintone_headers())->get($urlSpecs);
        $responseData = $profits->json();

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
        $evaluations = EvaluationRecord::where('year', $request->year)
        ->where('which_half', $request->which_half)
        ->where('user_id', $request->user_id)
        ->with(['mentor', 'checklist'])->get();
        return response()->json($evaluations);
    }
    public function check_evaluation_for_user_in_span(Request $request) {
        $active_user = $this->active_user();
        $attributes = $request->validate([
            'user_id' => 'required',
            'year' => 'required',
            'which_half' => 'required',
        ]);
        $evaluation = EvaluationRecord::where('user_id', $attributes['user_id'])
            ->where('year', $attributes['year'])
            ->where('which_half', $attributes['which_half'])
            ->with(['checklist', 'mentor', 'candidate'])
            ->first();
        if(empty($evaluation)) {
            return response()->json(['message' => '人事担当より人事考課設定していないため、現在作成できません。'], 404);
        }

        $privilageUsers = [608, 610, 631, $evaluation->mentor_id, $attributes['user_id']];
        if (!in_array($active_user->id, $privilageUsers)) {
            return response()->json(['message' => '権限がありません。'], 403);
        }
        $targetYear = $request->year;
        $previous_year = $request->which_half == 'first' ? $targetYear - 1 : $targetYear;
        $previousHalf = $request->which_half == 'first' ? 'second' : 'first';

        $previous_evaluations = EvaluationRecord::where('user_id', $attributes['user_id'])
        ->where('year', $previous_year)
        ->where('which_half', $previousHalf)
        ->whereNot('id', $evaluation->id)
        ->with(['mentor', 'checklist'])
        ->latest()->first();

        $sum_of_achievment = 0;
        $possible_increase_number = 0;
        $current_level = '';
        $current_skills = [];

        if(!empty($previous_evaluations)) {
            $current_level = $previous_evaluations->current_level ?? '';
            $current_skills = $previous_evaluations->checklist->pluck('content')->toArray() ?? [];
            $monthly_goals = ProjectGoal::where('year',  $previous_year)
                ->where('which_half', $previousHalf)
                ->where('user_id', $request->user_id)
                ->with(['project', 'files'])
                ->with(['salaryIssue' => function ($q) {
                    $q->with('files');
                }])
            ->get();
            $sum_of_achievment = $monthly_goals->sum('achievement_rate');
            $possible_increase_number = match (true) {
                // $sum_of_achievment >= 600 => 4,
                // $sum_of_achievment <= 599 && $sum_of_achievment >= 500 => 3,
                // $sum_of_achievment <= 499 && $sum_of_achievment >= 400 => 2,
                // $sum_of_achievment <= 399 && $sum_of_achievment >= 300 => 1,
                $sum_of_achievment >= 480 => 2,
                $sum_of_achievment >= 360 => 1,
                default => 0,
            };
        }          


        $response = [];
        $response['evaluation'] = $evaluation;
        $response['total_achievment'] = $sum_of_achievment;
        $response['possible_increase_number'] = $possible_increase_number;
        $response['previous_evaluation'] = $previous_evaluations;
        $response['current_level'] = $current_level;
        $response['current_skills'] = $current_skills;


        return response()->json($response);
    }

    public function save_evaluation_grade(Request $request) {
        $attr = $request->validate([
            'attributes.user_id' => 'required',
            'attributes.year' => 'required',
            'attributes.which_half' => 'required',
        ]);

        $params = $request->params;
        if(isset($params['mentor_id']) && $attr['attributes']['user_id'] == $params['mentor_id']) {
            throw ValidationException::withMessages(['message' => '自分自身をメンターに設定することはできません。']);
            
        }
        if(isset($params['general_position'])) {
            User::find($attr['attributes']['user_id'])->update(['general_position' => $params['general_position']]);
        }        
        $update = EvaluationRecord::updateOrCreate($attr['attributes'] , $params);
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
    public function set_increase_request(Request $request){

        $request->validate([
            'attributes.id' => 'required',
        ]);
        $evaluation = EvaluationRecord::findOrFail($request['attributes']['id']);
        $evaluation->update($request['params']);

        $skills = $request['children']['checklist'] ?? [];
        $checklistData = collect($skills)->map(function ($checklist) use ($evaluation) {
            return [
                'content' => $checklist,
            ];
        })->toArray();
        if(!empty($checklistData)) {
            $evaluation->checklist()->delete();
            $evaluation->checklist()->createMany($checklistData);
        }

        $candidates = $request['children']['candidate'] ?? [];
        $candidateData = collect($candidates)->map(function ($candidate) use ($evaluation) {
            return [
                'next_candidate' => $candidate
            ];
        })->toArray();
        if(!empty($candidateData)) {
            $evaluation->candidate()->delete();
            $evaluation->candidate()->createMany($candidateData);
        }
        return response()->json(['message' => 'Data inserted successfully!']);
    }
    public function get_evaluation_data(Request $request) {
        $request->validate([
            'year' => 'required',
            'which_half' => 'required',
            'user_id' => 'required',
        ]);
        $user_id = $request->user_id;
        $year = $request->year;
        $evalutaionRecord = EvaluationRecord::where('year', $year)
                                        ->where('which_half', $request->which_half)
                                        ->where('user_id', $user_id)
                                        ->with('checklist')
                                        ->with(['salary_issues' => function ($q) use($user_id) {
                                            $q->where('user_id', $user_id);
                                        }])
                                        ->with(['outcome_goals' => function ($q) use($user_id) {
                                            $q->where('user_id', $user_id)
                                                ->where('status', 3);
                                        }])
                                        ->with(['candidate', 'checklist'])->first();

        if(!$evalutaionRecord) {
            return;
        }
        $levelData = $this->get_evaluation_levels()->getData();
        $selectedLevel = $evalutaionRecord->current_level ?? '';
        $baseSkills = [];
        if($selectedLevel) {
            $levelName = explode('_', $selectedLevel);
            if (count($levelName) >= 3) {
                $cat = collect($levelData)->where('title', $levelName[0])->first();
                $job = collect($cat->children ?? [])->where('title', $levelName[1])->first();
                $skill = collect($job->children ?? [])->where('title', $levelName[2])->first();
                $baseSkills = $skill->children ?? [];
            }



        }
        $response = [
            'evaluation' => $evalutaionRecord,
            'base_skills' => $baseSkills,
        ];
        return response()->json($response);
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
        $user = $this->active_user();
        $request->validate([
            'id' => 'required',
            'status' => 'required'
        ]);
        $id = $request->id;
        $status = $request->status;
        $comment = $request->comment;
        $issue = SalaryIssue::findOrFail($id);
        $issue->update(['status' => $status]);
        if($comment) {
            $current_comment = $issue->comment ?? '';
            $approver = $user->name;
            $time = Carbon::now()->format('Y/m/d H:i');
            $fullComment = "【{$time}】 {$approver} : {$comment}";
            $new_comment = $current_comment ? "{$current_comment}\n{$fullComment}" : $fullComment;
            $issue->update(['comment' => $new_comment]);
        }

        return response()->json(['message' => 'Successfully approved!']); 
    }
    public function get_salary_issues(Request $request) {
        $date = $request->date;
        $salary_issues = SalaryIssue::where('user_id', Auth::id())
                                    ->where('date', $date)
                                    ->with(['actions'])
                                    ->get();
        return response()->json($salary_issues);
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
        $user = $this->active_user();
        $request->validate([
            'id' => 'required',
            'status' => 'required'
        ]);
        $id = $request->id;
        $status = $request->status;
        $comment = $request->comment;
        $goal = ProjectGoal::findOrFail($id);
        $goal->update(['status' => $status]);
        if($comment) {
            $current_comment = $goal->comment ?? '';
            $approver = $user->name;
            $time = Carbon::now()->format('Y/m/d H:i');
            $fullComment = "【{$time}】 {$approver}: {$comment}";
            $new_comment = $current_comment ? "{$current_comment}\n{$fullComment}" : $fullComment;
            $goal->update(['comment' => $new_comment]);
        }
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
    public function delete_issue(Request $request) {
        $request->validate([
            'id' => 'required',
        ]);
        $id = $request->id;
        SalaryIssue::findOrFail($id)->delete();
       
        return response()->json(['message' => 'Successfully deleted!']);
    }
    private function members_of_project_managed_by_user($user){
        $projects = ProjectRecord::whereHas('manager', fn($q) => $q->where('users.id', $user->id))
        ->with('members:id')
        ->get();
        $s = $projects->map(fn($project) => [
            "project_id" => $project->id,
            "members" => $project->members->pluck('id')->toArray(),
            "type" => "manager"
        ])->toArray();
        return $s;
    }
    private function projects_participate_by_user($user){
        $projects = ProjectRecord::whereHas('members', fn($q) => $q->where('users.id', $user->id))
        ->get();
        $s = $projects->map(fn($project) => [
            "project_id" => $project->id,
            "members" => [$user->id],
            "type" => "member"
        ])->toArray();
        return $s;
    }
    public function get_members_goals_badge(Request $request){
        $user = $this->active_user();
        $date = Carbon::now();
        $managinProjectData = $this->members_of_project_managed_by_user($user);
        $selfProjects = $this->projects_participate_by_user($user);
        $projectData = array_merge($managinProjectData, $selfProjects);

        if(!count($projectData)){
            return response()->json([]);
        }
        $goals = $this->goals_fetch_by_users($projectData, $date);

        return response()->json($goals);
    }

    public function get_managers_goals_badge(Request $request){
        $projects = ProjectRecord::whereHas('manager')
            ->with('manager:id')
            ->get();
        $projectsData = $projects->map(function($project) {
            return [
                "project_id" => $project->id,
                "members" => $project->manager->pluck('id')->toArray(),
                "type" => "manager"
            ];
        })->toArray();
        
        if(!count($projectsData)){
            return response()->json([]);
        }
        $goals = $this->goals_fetch_by_users($projectsData, Carbon::now());

        return response()->json($goals);
    }
    private function goals_fetch_by_users(array $projectData, Carbon $date){
        $user = $this->active_user();
        $goals = ProjectGoal::where(function ($query) use ($projectData, $date) {
            foreach($projectData as $project){
                $query->orWhere(function ($subQuery) use ($project, $date) {
                    $subQuery->where('project_id', $project['project_id'])->whereIn('user_id', $project['members'])
                    ->when($project['type'] == 'manager', function ($q) use($date) {
                        $q->whereNotIn('user_id', [Auth::id()])->whereIn('status', [2, 7]);
                    })
                    ->when($project['type'] == 'member', function ($q) {
                        $q->whereIn('status', [1, 8]);
                    });
                });
            }
        })
        ->when($user->id == 631, function($q) {
            $q->orWhere('status', 4);
        })
        ->select('id', 'project_id', 'user_id', 'year', 'which_half', 'status')->get();
        return $goals;
    }
    public function get_salary_issue_badge(Request $request){
        $user = $this->active_user();
        $date = Carbon::now();
        $year = $date->year;
        $current_half = Carbon::now()->between(Carbon::createFromDate($year, 4, 1), Carbon::createFromDate($year, 9, 30)) ? 'first' : 'second';
        $previous_half = $current_half == 'first' ? 'second' : 'first';
        $previous_year = $current_half == 'first' ? $year - 1 : $year;
        $years = [ $year - 1, $year, $year +1];
        $evaluations = EvaluationRecord::where('mentor_id', $user->id)
            ->where(function ($query) use($previous_year, $previous_half, $year, $current_half) {
                $query->where(function ($q) use($previous_year, $previous_half) {
                    $q->where('year', $previous_year)->where('which_half', $previous_half);
                })->orWhere(function ($q) use($year, $current_half) {
                    $q->where('year', $year)->where('which_half', $current_half);
                });
            })->get();
            // dd($evaluations);
        $mentee_id = $evaluations->pluck('user_id')->toArray();
        $salary_issues = SalaryIssue::whereHas('project_goal', function ($q) use($previous_year, $previous_half, $year, $current_half) {
            $q->where(function ($query) use($previous_year, $previous_half, $year, $current_half) {
                $query->where(function ($q) use($previous_year, $previous_half) {
                    $q->where('year', $previous_year)->where('which_half', $previous_half);
                })->orWhere(function ($q) use($year, $current_half) {
                    $q->where('year', $year)->where('which_half', $current_half);
                });
            });
        })->whereHas('project_goal')
        ->where(function ($query) use ($mentee_id) {
            $query->where(function ($query) use ($mentee_id){
                $query->whereIn('status', [2, 7])->whereIn('user_id', $mentee_id);
            })->orWhere(function($query){
                $query->whereIn('status', [1, 8])->where('user_id', Auth::id());
            });
        })            
        ->with('project_goal')
        ->get();
        
        $data = [];

        // return response()->json($salary_issues);

        foreach($salary_issues as $issue) {
            $issue_year = $issue->project_goal->year;
            $issue_half = $issue->project_goal->which_half;

            $is_my_mentee = $evaluations->contains(function ($evaluation) use ($issue_year, $issue_half, $user, $issue) {
                return $evaluation->mentor_id == $user->id 
                && $evaluation->year == $issue_year 
                && $evaluation->which_half == $issue_half
                && $evaluation->user_id == $issue->user_id;
            });

            if($is_my_mentee || ($issue->user_id == Auth::id() && ($issue->status == 1 || $issue->status == 8))) {
                $data[] = [
                    'issue_id' => $issue->id,
                    'goal_id' => $issue->project_goal->id,
                    'project_id' => $issue->project_goal->project_id,
                    'user_id' => $issue->user_id,
                    'year' => $issue_year,
                    'which_half' => $issue_half,
                    'status' => $issue->status,
                ];
            }
        }           

        return response()->json($data);
    }
    public function get_project_badge() {
        $user = $this->active_user();
        $date = Carbon::now();
        if ($user->position_id == 6) {
            $response = $this->getMemberBadges($user, $date);
        } elseif ($user->position_id < 6 && ($user->id !== 610 && $user->id !== 608)) {
            $response = $this->getManagerBadges($user, $date);
        } elseif ($user->id === 631) {
            $response = $this->getChangeBadge($date);
        } else {
            $response = $this->remindedBadges($user);
        }
        
        $task_counts = $this->project_task_badge($user);
        $asset_badge = $this->asset_badge($user);

        $by_projects = $task_counts->groupBy('id')->mapWithKeys(function ($group, $key) {
            return [$key => $group->sum(fn($task) => $task->tasks->count() ?? 0)];
        })->toArray();
        $total_task_badge = $task_counts->sum(fn($task) => $task->tasks->count() ?? 0);
        $grouped_task_badge = $task_counts->flatMap(function ($project) {
            return $project->tasks;
        })->groupBy('id')->map->count();

        foreach ($by_projects as $project_id => $count) {
            if (isset($response['project_counts'][$project_id])) {
                $response['project_counts'][$project_id] += $count;
            } else {
                $response['project_counts'][$project_id] = $count;
            }
        }

        $response['total_sum'] += $total_task_badge;

        $data = array_merge(
            [
                'by_projects' => $by_projects,
                'grouped_task_badge' => $grouped_task_badge,
                'asset_badge' => $asset_badge,
            ],
            $response,
            
        );
        
       
        
        return response()->json($data);
    }
    private function asset_badge($user){
        $managing_projects = ProjectRecord::whereHas('manager', fn($q) => $q->where('users.id', $user->id))->pluck('id')->toArray();
        $asset_requests = AssetRequest::where(function($q) use($managing_projects, $user) {
            $q->whereIn('from_project', $managing_projects)->where('status', 1);

        })
        ->where('status', 1)
        ->get();
        return $asset_requests;
    }
    private function getMemberBadges($user, $date)
    {

        $memberIds = ProjectRecord::whereHas('manager', fn($q) => $q->where('users.id', $user->id))
        ->with('members:id')
        ->get()
        ->flatMap(fn($project) => $project->members->pluck('id'))
        ->unique()
        ->values();

        $allGoals = ProjectGoal::whereIn('user_id', $memberIds)
            ->whereHas('project.manager', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })
            ->where(function ($query) use ($date) {
                $query->where('status', 2)
                    ->orWhere(function ($subQuery) use ($date) {
                        $subQuery->where('end_date', '<', $date)->where('status', 7);
                    });
            })
            ->orWhereHas('salaryIssue', function ($q) use ($user) {
                $q->whereIn('status', [2, 7])->whereHas('evaluation', function ($subQuery) use ($user) {
                    $subQuery->where('mentor_id', $user->id);
                });
            })
            ->orWhere(function ($query) use ($user) {
                $query->where('user_id', $user->id)->where('status', 1);
            })
            ->get();

        return $this->calculateGoalStats($allGoals);
    }

    private function getManagerBadges($user, $date)
    {
        

        $managerIds = ProjectRecord::with('manager:id') 
            ->get()
            ->flatMap(fn($project) => $project->manager->pluck('id'))
            ->unique()
            ->values()
            ->toArray();

        $goals = ProjectGoal::whereIn('user_id', $managerIds)
            ->where(function ($query) use ($date) {
                $query->where('status', 2)
                    ->orWhere(function ($subQuery) use ($date) {
                        $subQuery->where('end_date', '<', $date)->where('status', 7);
                    });
            })
            ->orWhereHas('salaryIssue', function ($q) use ($user) {
                $q->whereIn('status', [2, 7])->whereHas('evaluation', function ($subQuery) use ($user) {
                    $subQuery->where('mentor_id', $user->id);
                });
            })
            ->get();

        return $this->calculateGoalStats($goals);
    }
    private function remindedBadges($user) {
        $goals = ProjectGoal::where(function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->where(function ($q) {
                            $q->where('status', 1)
                            ->orWhere('status', 8);
                        });
                })->orWhereHas('salaryIssue', function ($q) use ($user) {
                    $q->whereIn('status', [2, 7])->whereHas('evaluation', function ($subQuery) use ($user) {
                        $subQuery->where('mentor_id', $user->id);
                    });
                })
                ->get();
        return $this->calculateGoalStats($goals);
    }
    private function getChangeBadge($date) 
    {
        $ng_list = ['推し', '知人', '家族', '友人', '関係者', 'お知らせアカウント'];
        $user_ids = User::where('deleted_flag', 0)
        ->where('retire', 0)
        ->whereNotIn('name', $ng_list)
        ->select('id', 'name', 'icon_path', 'icon_bg')
        ->pluck('id')
        ->toArray();
        $goals = ProjectGoal::whereIn('user_id', $user_ids)
                            ->where('status', 4)->get();
        return $this->calculateGoalStats($goals);
    }
    private function calculateGoalStats($goals)
    {
        $goalCounts = $goals->groupBy('project_id')
            ->map(function ($projectGoals) {
                return $projectGoals->groupBy('user_id')->map->count();
            })
            ->toArray();
        $goalByWhich = $goals->groupBy('project_id')
            ->map(function ($projectGoals) {
                return $projectGoals->groupBy('user_id')
                    ->map(function ($userGoals) {
                        return $userGoals->groupBy(fn ($goal) => "{$goal['year']}-{$goal['which_half']}")
                            ->map->count();
                    });
            })->toArray();
        $projectCounts = $goals->groupBy('project_id')
            ->map->count()
            ->all();
        $totalSum = array_sum($projectCounts);
        $whichGoal = $goals->groupBy('id')->map->count()->toArray();
        return [
            'which_goal' => $whichGoal,
            'total_sum' => $totalSum,
            'project_counts' => $projectCounts,
            'goal_counts' => $goalCounts,
            'year_half_counts' => $goalByWhich
        ];
    }
    public function get_managing_projects(Request $request)
    {
        $weekStartDate = Carbon::now()->startOfWeek(CarbonInterface::MONDAY)->toDateString(); 
        $projects = ProjectRecord::whereDoesntHave('project_conditions', function ($q) use($weekStartDate, $request) {
            $q->where('user_id', $request->user()->id)
                ->where('week_start_date', $weekStartDate);
        })->whereHas('manager', function ($q) use($request) {
            $q->where('users.id', $request->user()->id);
        })->get();
        return response()->json($projects);
    }
    public function updateConditions(Request $request)
    {
        $validated = $request->validate([
            'selected' => 'required|array',
            'selected.*.value' => 'required|integer|between:0,5', 
            'selected.*.project_record_id' => 'required|integer|exists:project_records,id',
        ]);

        $userId = $request->user()->id; 
        $weekStartDate = Carbon::now()->startOfWeek(CarbonInterface::MONDAY)->toDateString();  

        foreach ($validated['selected'] as $item) {
            ProjectCondition::updateOrCreate(
                [
                    'project_record_id' => $item['project_record_id'],
                    'user_id' => $userId,
                    'week_start_date' => $weekStartDate,
                ],
                [
                    'value' => $item['value'],
                ]
            );
        }

        return response()->json(['message' => 'Project conditions updated successfully.'], 200);
    }
    public function project_task_badge($user) {
        $badge_counts = ProjectRecord::whereHas('tasks', function ($q) use ($user) {
            $q->whereHas('taskUsers', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->whereHas('taskRecord.comments', function ($commentQuery) use ($user) {
                        $commentQuery->whereColumn('task_comments.created_at', '>', 'task_users.checked_at')
                                    ->whereNot('task_comments.user_id', $user->id);
                    });
            });
        })->with(['tasks' => function ($q) use ($user) {
            $q->whereHas('taskUsers', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->whereHas('taskRecord.comments', function ($commentQuery) use($user) {
                        $commentQuery->whereColumn('task_comments.created_at', '>', 'task_users.checked_at')
                                    ->whereNot('task_comments.user_id', $user->id);
                    });
            });
        }])->get();
        

        return $badge_counts;
    }
    public function get_evaluation_levels(){
        $tabs = [
            '',
            '営業',
            '経営',
            'マーケティング',
            '人事・人材開発',
            '労務管理',
            '総務',
            '経理',
            '企業法務',
            '広報',
            '情報システム',
        ];

        $filePath = storage_path('app/evaluation_files/evaluation.xlsx');
        $data = Excel::toArray(new EvaluationImport, $filePath);


        



            if (isset($data[11])) {
                $main_categories = $data[11];
                
                $main_categories = array_map(function($item){
                    return $item[0];
                }, $main_categories);
                unset($main_categories[0]);
                $main_categories = array_unique($main_categories);
                $main_categories = array_values($main_categories);
            } else {
                $main_categories = [];
            }
        $output = new Collection();
        foreach($main_categories as $index => $main_category){
            $category_index = array_search($main_category, $tabs);
            $currentTab = $data[$category_index];
            unset($currentTab[0]);
            $currentTabData = array_values($currentTab);

            // dd($currentTabData);


            $indexed = [];
            foreach($currentTabData as $key => $item){
                $job = $item[2];
                $level = $item[3];
                $skill = $item[4];
                $indexed[] = [
                    "job" => $job,
                    "level" => $level,
                    "skill" => $skill
                ];
            }

            $collection = collect($indexed);

            $grouped = $collection->groupBy('job')->map(function ($group) {
                return [
                    'title' => $group->first()['job'],
                    'children' => $group->groupBy('level')->map(function ($group) {
                        return [
                            'title' => $group->first()['level'],
                            'children' => $group->map(function ($item) {
                                return $item['skill'];
                            })->values()->all(),
                        ];
                    })->values()->all(),          
                ];
            })->values()->all();

            
            $output->push([
                "title" => $main_category,
                "children" => $grouped
            ]);

        }
        // dd($output);
        return response()->json($output);      
     

    }

    public function combine_data() {
        $set_increases = ProjectSetIncrease::all();
        foreach($set_increases as $increase) {
            $evaluation = EvaluationRecord::updateOrCreate([
                    'date' => $increase->date,
                    'user_id' => $increase->user_id,

                ],
            [
                'vision' => $increase->reason,
                'mentor_comment' => $increase->mentor_entry
            ]);
            $increase->candidate()->update(['evaluation_record_id' => $evaluation->id]);
            $increase->checklist()->update(['evaluation_record_id' => $evaluation->id]);
        }
    }
    public function create_generated_project_tasks(array $tasks, int $project_id) {

        $project = ProjectRecord::with('manager')->findOrFail($project_id);
        $managerIds = $project->manager->pluck('id')->toArray();
        $active_user = $this->active_user();
        $taskRecords = [];
        foreach ($tasks as $task) {
            $taskRecord = taskRecord::create([
                'remarks' => $task['remarks'],
                'start_at' => $task['start_at'],
                'end_at' =>  $task['end_at'],
                'project_record_id' => $project->id,
                'user_id' => $active_user->id,
                'updated_user' => $active_user->id
            ]);
            $taskRecord->executors()->sync($managerIds);
            foreach($task['sub_tasks'] as $sub_task) {
                $subTask = taskRecord::create([
                    'remarks' => $sub_task['remarks'],
                    'start_at' => $sub_task['start_at'],
                    'end_at' => $sub_task['end_at'],
                    'project_record_id' => $project->id,
                    'user_id' => $active_user->id,
                    'updated_user' => $active_user->id,
                    'parent_task_id' => $taskRecord->id
                ]);
                $subTask->executors()->sync($managerIds);
            }
            $taskRecords[] = $taskRecord;
        }

        return response()->json($taskRecords);
    }
    public function get_manuals(Request $request) {
        $request->validate([
            'project_name' => 'required',
        ]);
        $project_name = $request->project_name;

        $queryParams = [
            'app' => '1181',
            "query" => "部門 = \"{$project_name}\""
        ];
        
        $queryString = http_build_query($queryParams);
        $url = "https://glowd-hldgs.cybozu.com/k/v1/records.json?$queryString";


        $response = Http::withHeaders($this->kintone_headers())->get($url);
        $responseData = $response->json();

        $records = $responseData['records'] ?? [];
        $fields = [
            '作業'                   => '作業',
            '作業詳細'               => '作業詳細',
            '持ち出し備品利用ツール' => '持ち出し備品利用ツール',
            '対応者・対応部署'       => '対応者対応部署',
            'リスク'       => 'リスク',
            'リスク対策'             => 'リスク対策',
            '期日'                   => '期日',

        ];
        
        $manuals = array_map(function ($record) use ($fields) {
            $table = $record['テーブル1']['value'] ?? [];
        
            $rules = array_map(function ($item) use ($fields) {
                $rule = [];
                $rule['id'] = $item['id'] ?? '';
                $rule['job'] = [];
                foreach ($fields as $key => $path) {
                    $value = $item['value'][$path]['value'] ?? '';
                    // if ($value !== '') {
                        $rule['job'][$path] = $value;
                    // }
                }
                return $rule;
            }, $table);
        
            return [
                'title' => $record['タイトル']['value'] ?? '',
                'id' => $record['$id']['value'] ?? '',
                'rules' => $rules,
                'files' => $record['添付ファイル']['value'] ?? [],
            ];
        }, $records);
        return response()->json($manuals);
    }
    private function kintone_token() {
        $user_name = config('app.kintone_user_name');
        $password = config('app.kintone_password');
        $string = $user_name. ':'. $password;
        $x_token = base64_encode($string);
        return $x_token;
    }
    private function manual_data($record_id){
        $queryParams = [
            'app' => '1181',
            'id' => $record_id,
        ];        
        $queryString = http_build_query($queryParams);
        $url = "https://glowd-hldgs.cybozu.com/k/v1/record.json?$queryString";

        $response = Http::withHeaders($this->kintone_headers())->get($url);
        $responseData = $response->json();
        return $responseData;
    }
    public function update_manuals(Request $request) {
        $record_id = $request->manual['id'] ?? null;
        
        if(!$record_id){
            throw ValidationException::withMessages(['message' => 'エラーが発生しました。']);
        }
        $responseData = $this->manual_data($record_id);
        $rules = collect($request->manual['rules']);
        $risks = $responseData['record']['テーブル1']['value'] ?? [];
        $exists = [];
        foreach($risks as $risk){
            $prev_risk = $risk['value']['リスク']['value'];
            $prev_management = $risk['value']['リスク対策']['value'];
            
            $updated_value = $rules->where('id', $risk['id'])->first();
            if($updated_value){
                $prev_risk = $updated_value['job']['リスク'] ;
                $prev_management = $updated_value['job']['リスク対策'];
            }
            $prep = [
                "id" => $risk['id'],
                "value" => [
                    "リスク" => [
                        "value" => $prev_risk
                    ],
                    "リスク対策" => [
                        "value" => $prev_management
                    ],
                ]
            ];
            array_push($exists, $prep);
        }

        $data = [
            "app" => 1181,
            "id" => $record_id,
            "record" => [
                "テーブル1" => [
                    "value" => $exists                    
                ]
            ]
        ];
        $queryParams = [
            'app' => '1181',
            'id' => $record_id,
        ];
        
        $queryString = http_build_query($queryParams);
        $url = "https://glowd-hldgs.cybozu.com/k/v1/record.json?$queryString";

        $response = Http::withHeaders($this->kintone_headers())->put($url,$data);
        $responseData = $response->json();
        return response()->json($responseData);
    }
    public function create_manual_rule(Request $request){
        $request->validate([
            'manual_id' => 'required',
            'job' => 'required|array',
        ]);
        $record_id = $request->manual_id ?? null;
        $responseData = $this->manual_data($record_id);
        $rules = collect($responseData['record']['テーブル1']['value'] ?? []);
        $job = $request->job;
        if($job['id']){
            $updated = $rules->map(function($rule) use ($job){
                if($rule['id'] == $job['id']){
                    $values = $job['job'];
                    foreach($values as $key => $value){
                        $rule['value'][$key]['value'] = $value;
                    }
                }
                return $rule;
            });
            $response = $this->update_manual_record_table($updated, $record_id);
            return response()->json($response);
            
        }else{
            $new_rule = [
                "id" => "",
                "value" => []                    
            ];
            foreach($job['job'] as $key => $value){
                $new_rule['value'][$key] = [
                    "value" => $value
                ];
            }
            $updated = $rules->push($new_rule);
            $response = $this->update_manual_record_table($updated, $record_id);
            return response()->json($response);
            
        }        

    }
    public function create_manual_record(Request $request){
        $request->validate([
            'project_id' => 'required',
            'title' => 'required',
        ]);

        $project = ProjectRecord::findOrFail($request->project_id);
        $data = ["app" => 1181];
        
        if($request->id){
            $data['id'] = $request->id;
            $data['record'] = [
                "タイトル" => [
                    "value" => $request->title
                ]
            ];
        }else{
            $data['record'] = [
                "タイトル" => [
                    "value" => $request->title
                ],
                "部門" => [
                    "value" => $project->name
                ]
            ];
        }
        $queryParams = [
            'app' => '1181',
        ];
        if($request->id){
            $queryParams['id'] = $request->id;
        }
        $queryString = http_build_query($queryParams);
        $url = "https://glowd-hldgs.cybozu.com/k/v1/record.json?$queryString";

        try {
            $method = $request->id ? 'put' : 'post';
            $response = Http::withHeaders($this->kintone_headers())->$method($url, $data);
            $responseData = $response->json();
            if(isset($response['revision'])){
                return $responseData;
            }
            else{
                throw ValidationException::withMessages(['message' => $response['message'] ?? 'エラーが発生しました。']);
            }
        } catch (\Exception $e) {
            throw ValidationException::withMessages(['message' => 'API request failed: ' . $e->getMessage()]);
        }     
    }
    private function update_manual_record_table ($rules, $record_id){
        $data = [
            "app" => 1181,
            "id" => $record_id,
            "record" => [
                "テーブル1" => [
                    "value" => $rules                    
                ]
            ]
        ];
        $queryParams = [
            'app' => '1181',
            'id' => $record_id,
        ];        
        $queryString = http_build_query($queryParams);
        $url = "https://glowd-hldgs.cybozu.com/k/v1/record.json?$queryString";
        $response = Http::withHeaders($this->kintone_headers())->put($url,$data);
        $responseData = $response->json();
        if(isset($response['revision'])){
            return $responseData;
        }
        else{
            throw ValidationException::withMessages(['message' => $response['message'] ?? 'エラーが発生しました。']);
        }
    }
    public function delete_manual_rule(Request $request){
        $request->validate([
            'manual_id' => 'required',
            'rule_id' => 'required',
        ]);
        $record_id = $request->manual_id;
        $responseData = $this->manual_data($record_id);
        $rules = collect($responseData['record']['テーブル1']['value'] ?? []);
        $updated = $rules->filter(function($rule) use ($request){
            return $rule['id'] != $request->rule_id;
        });
        $updated = $updated->values();
        // dd($updated);
        $response = $this->update_manual_record_table($updated, $record_id);
        return response()->json($response);
    }
    public function delete_manual_record(Request $request){
        $request->validate([
            'manual_id' => 'required',
        ]);
        $record_id = $request->manual_id;
        $data = [
            'app' => '1181',
            'ids' => [$record_id],
        ];
        $url = "https://glowd-hldgs.cybozu.com/k/v1/records.json";

        $response = Http::withHeaders($this->kintone_headers())->delete($url,$data);
        $responseData = $response->json();
        if(isset($response['revision'])){
            return $responseData;
        }
        else{
            throw ValidationException::withMessages(['message' => $response['message'] ?? 'エラーが発生しました。']);
        }
    }
    

    public function get_contracts(Request $request) {
        $request->validate([
            'project_name' => 'required',
        ]);
        $project_name = $request->project_name;

        $queryParams = [
            
            "query" => "部門 = \"{$project_name}\"",
        ];
        
        $contractValues = $this->contract_fetch($queryParams);       

        $contract_ids = array_map(function($record){
            return $record['契約書id']['value'] ?? '';
        }, $contractValues);
        $contract_ids = implode(',', $contract_ids);

        // $partner_ids = array_map(function($record){
        //     return $record['取引先id']['value'] ?? '';
        // }, $contractValues);
        // $partner_ids = implode(',', $partner_ids);

        // $queryParamsPartners = [
        //     "query" => "取引先id in ({$partner_ids}) limit 500",
        // ];
        // $partnerContracts = $this->contract_fetch($queryParamsPartners);

        // dd($partnerContracts);
        $queryParamsSpecs = [
            'app' => 156,
            "query" => "契約書id in ({$contract_ids})",
        ];
        $queryStringSpecs = http_build_query($queryParamsSpecs);
        $urlSpecs = "https://glowd-hldgs.cybozu.com/k/v1/records.json?$queryStringSpecs";
        $specs = Http::withHeaders($this->kintone_headers())->get($urlSpecs);
        $specsData = $specs->json();
        // dd($specsData);
        $specsValues = $specsData['records'] ?? [];
        $specsClean = array_map(function ($record) {
            $spec = [];
            foreach ($record as $key => $value) {
                $spec[$key] = $value['value'] ?? '';
            }
            return $spec;
        }, $specsValues);
        

        $contractsClean = array_map(function ($record) use ($specsClean) {
            $contract = [];
            foreach ($record as $key => $value) {
                $contract[$key] = $value['value'] ?? '';
            }
            $contract['specs'] = array_values(array_filter($specsClean, function($spec) use ($contract){
                return $spec['契約書id'] == $contract['契約書id'];
            }));
            return $contract;
        }, $contractValues);

        $column_types = [
            "array" => ['specs', '契約終了'],
            "file" => ['お見積書','添付ファイル', '契約書原本データ', '契約書確認用データ', '覚書・変更契約書_原本データ', '覚書・変更契約書_確認データ', '誓約書・通知書_原本データ', '誓約書・通知書_確認用データ'],
            "date" => ['契約期間終了日', '契約期間開始日', '契約締結日'],
            "html" => ['メモ'],
            "action" => ['詳細']
        ];
        $table_columns = [
            "レコード番号", 
            "契約案件名",
            "案件担当者",
            "取引先",
            "契約期間開始日",
            "契約期間終了日",
            "詳細"

        ];
        return response()->json([
            'contracts' => $contractsClean,
            'specs' => $specsClean,
            'column_types' => $column_types,
            'table_columns' => $table_columns
        ]);
    }
    private function contract_fetch($query){   
        
        $contract_fields = [
            "レコード番号", "担当者", "取引先検索", "取引先", "役職名", "代表者名", "契約案件名", "甲会社名", "甲役職", "甲代表者名", "取引先検索_1", "部門", "乙会社名", "乙役職", "乙代表者名",
            "契約締結日", "契約期間開始日", "契約期間終了日", "契約終了",
            "契約書確認用データ", "契約書原本データ", "誓約書・通知書_確認用データ", "誓約書・通知書_原本データ","覚書・変更契約書_確認データ", "覚書・変更契約書_原本データ", "お見積書", "メモ",
            "契約書id", "取引先id", "ルックアップ検索用id", '添付ファイル', 'ステータス', '案件担当者'
        ];

        $query['fields'] = $contract_fields;
        $query['app'] = 138;
        $queryString = http_build_query($query);
        $url = "https://glowd-hldgs.cybozu.com/k/v1/records.json?$queryString";
        $contracts = Http::withHeaders($this->kintone_headers())->get($url);
        $contractsData = $contracts->json();
        $contractValues = $contractsData['records'] ?? [];
        return $contractValues;
    }
    private function kintone_headers() {
        $user_name = config('app.kintone_user_name');
        $password = config('app.kintone_password');
        $string = $user_name. ':'. $password;
        $x_token = base64_encode($string);
        $headers = [
            'Authorization' => 'Basic',
            'X-Cybozu-Authorization' => $x_token
        ];
        return $headers;
    }

    public function get_yearly_plan(Request $request){
        $request->validate([
            'project_id' => 'required',
            'year' => 'required',
        ]);
        
        $project = ProjectRecord::findOrFail($request->project_id);

        $year = $request->year;
        $month = $request->month;
        $project_name = $project->name;

        $file_path = storage_path("app/yearly_plan/{$year}.xlsx");
        $file_exists = file_exists($file_path);
        if(!$file_exists){
            return response()->json([]);   
        }
        $file = Excel::toCollection(new YearlyPlanImport, $file_path);
        $data = $file[0];
        $data->shift()->toArray();
        $month_headers = $data->shift()->toArray();

        $month_found = false;
        $month_target_indexes = [];
        foreach($month_headers as $key => $header){
            if(!$month_found){
                if($header == "{$year}年{$month}月"){
                    $month_found = true;  
                    $month_target_indexes[] = $key;      
                }
            }else{
                if($header == null || $header == "{$year}年{$month}月"){
                    $month_target_indexes[] = $key; 
                }else{
                    break;
                }
            }
            
        }
        // $month_headers = array_filter($month_headers, function($header) use ($year, $month){
        //     return $header == "{$year}年{$month}月";
        // });

        $sub_headers = $data->shift()->toArray();
        // $target_header_keys = array_keys($month_headers);
        $sub_headers_for_target_month = array_filter($sub_headers, function ($key) use($month_target_indexes) {
            return in_array($key, $month_target_indexes);
        }, ARRAY_FILTER_USE_KEY);
        $plan_sales_index_1 = array_search('合計 売上高', $sub_headers_for_target_month);
        $plan_sales_index_2 = array_search('合計 内部売上高合計', $sub_headers_for_target_month);
        $plan_expense_index_1 = array_search('合計 給料手当', $sub_headers_for_target_month);
        $plan_expense_index_2 = array_search('合計 外注費', $sub_headers_for_target_month);
        $plan_expense_index_3 = array_search('合計 販管費その他', $sub_headers_for_target_month);
        $plan_expense_index_4 = array_search('合計 間接費配賦', $sub_headers_for_target_month);
        $plan_expense_index_5 = array_search('合計 内部発注合計', $sub_headers_for_target_month);
        $plan_expense_index_6 = array_search('業績連動型賞与引当金', $sub_headers_for_target_month);

        $profit_index = array_search('利益', $sub_headers_for_target_month);
        $profit_rate_index = array_search('利益率', $sub_headers_for_target_month);
        
        $projectsData = $data->filter(function ($row) use ($project_name) {
            return $row[1] === $project_name; 
        });

        $allPlanData = [];
        
        foreach($projectsData as $project){
            $planData = [];
            $totalSales = round((float) $project[$plan_sales_index_1] + (float) $project[$plan_sales_index_2], 0, PHP_ROUND_HALF_UP);
            $totalExpense = round((float)  $project[$plan_expense_index_1] + (float) $project[$plan_expense_index_2] + (float) $project[$plan_expense_index_3] + (float) $project[$plan_expense_index_4] + (float) $project[$plan_expense_index_5] + (float) $project[$plan_expense_index_6], 0, PHP_ROUND_HALF_UP);
            $planData = [
                "sales" => $totalSales,
                "expense" => $totalExpense,
                "profit" => round((float) $project[$profit_index], 0, PHP_ROUND_HALF_UP),
                "profit_rate" => (float) $project[$profit_rate_index],
                "month_target_indexes" => $month_target_indexes
            ];
            $allPlanData[] = $planData;
        } 

        return response()->json($allPlanData);

    }
    public function get_profit(Request $request){
        $request->validate([
            'project_id' => 'required',
            'year' => 'required',
        ]);
        $project = ProjectRecord::findOrFail($request->project_id);
        $year = $request->year;
        $month = $request->month;
        $project_name = $project->name;
        $dateInstance = Carbon::createFromDate($year, $month, 1);
        $endOfMonth = $dateInstance->endOfMonth()->toDateString();

        $queryParamsSpecs = [
            'app' => 1068,
            "query" => "部門 = \"{$project_name}\" and 日付 = \"{$endOfMonth}\"",
            "fields" => ["売上高合計", "内部売上高合計", "販売管理費合計", "間接費配賦", "利益", "利益率"],
        ];
        $queryStringSpecs = http_build_query($queryParamsSpecs);
        $urlSpecs = "https://glowd-hldgs.cybozu.com/k/v1/records.json?$queryStringSpecs";
        $profits = Http::withHeaders($this->kintone_headers())->get($urlSpecs);
        $profitsData = $profits->json();
        $profitRecords = $profitsData['records'] ?? [];

        $profitResponse = [];
        foreach($profitRecords as $profit){

            $totalSales = round((float) $profit['売上高合計']['value'] + (float) $profit['内部売上高合計']['value'], 0, PHP_ROUND_HALF_UP);
            $totalExpense = round((float)  $profit['販売管理費合計']['value'] + (float) $profit['間接費配賦']['value'], 0, PHP_ROUND_HALF_UP);
            $profitData = [

                "sales" => $totalSales,
                "expense" => $totalExpense,
                "profit" => round((float) $profit['利益']['value'], 0, PHP_ROUND_HALF_UP) ?? 0,
                "profit_rate" => (float) $profit['利益率']['value'] ?? 0,
            ];
            $profitResponse[] = $profitData;
        }
        return response()->json($profitResponse);
    }
    public function get_settlement(Request $request){
        $request->validate([
            'project_id' => 'required',
            'year' => 'required',
        ]);
        $project = ProjectRecord::findOrFail($request->project_id);
        $year = $request->year;
        $month = $request->month;
        $tabName = sprintf('%04d%02d', $year, $month);
        $project_name = $project->name;
     
        $client = new Google_Client();
        $client->setApplicationName('Google Sheets API');
        $client->setScopes(['https://www.googleapis.com/auth/spreadsheets.readonly']);
        $client->setAuthConfig(storage_path('app/spread_json_key/gen-lang-client-0333646800-e777adab076d.json')); // Path to your Service Account credentials file
        $client->setAccessType('offline');
        $service = new Google_Service_Sheets($client);
        try {
            $response = $service->spreadsheets_values->get('1HTacPGjBDtg3KAK0hToBeJW__fqCp9iH01a38Ihjet8', $tabName);
        } catch (GoogleServiceException $e) {
            // return response()->json([
            //     'error' => true,
            //     'message' => '実績データ見つかりません<br>' . $e->getErrors()[0]['message'],
            // ], $e->getCode() ?: 500);
            return response()->json([]);
        }
        $settlements = $response->getValues();
        $settlement_headers = $settlements[1];
        $settlement_data = array_slice($settlements, 2);

        $settlement_sales_index = array_search('収入', $settlement_headers);
        $settlement_expense_index = array_search('支出', $settlement_headers);
        $settlement_additional_expense_index = array_search('間接費配賦', $settlement_headers);
        $settlement_profit_index = array_search('利益', $settlement_headers);
        $settlement_profit_rate_index = array_search('利益率', $settlement_headers);
        $settlement_for_project = array_filter($settlement_data, function($settlement) use ($project_name){
            return isset($settlement[1]) && $settlement[1] == $project_name;
        });
        
        $settlementResponse = [];

        foreach($settlement_for_project as $settlement){
            $expenseVal    = $settlement[$settlement_expense_index]    ?? 0;
            $additionalVal = $settlement[$settlement_additional_expense_index] ?? 0;
            $salesVal      = $settlement[$settlement_sales_index]      ?? 0;
            $profitVal     = $settlement[$settlement_profit_index]     ?? 0;
            $profitRateVal = $settlement[$settlement_profit_rate_index] ?? 0;
            $totalExpense = round((float)  str_replace(',', '', $expenseVal) + (float) str_replace(',', '', $additionalVal), 0, PHP_ROUND_HALF_UP);
            $totalSales = round((float)  str_replace(',', '', $salesVal), 0, PHP_ROUND_HALF_UP);
            $settlementData = [
                'sales' => $totalSales,
                'expense' => $totalExpense ?? 0,
                'profit' => round((float) str_replace(',', '', $profitVal), 0, PHP_ROUND_HALF_UP),
                'profit_rate' => (float) str_replace('%', '', $profitRateVal),
            ];
            $settlementResponse[] = $settlementData;
        }
        return response()->json($settlementResponse);
    }
    public function get_asset_badge(Request $request){
        $active_user = $this->active_user();
        $projects = $this->members_of_project_managed_by_user($active_user);
        if(empty($projects) && $active_user->id != 610 && $active_user->id != 608){
            return response()->json([]);
        }
        $project_ids = array_map(function($project){
            return $project['project_id'];
        }, $projects);

        $target_assets = AssetRecord::where(function ($query) use ($active_user, $project_ids) {
            $query->whereHas('requests', function ($query) use ($active_user, $project_ids) {
                $query->where('status', 1)
                ->where(function($query) use ($project_ids){
                    $query->whereIn('from_project', $project_ids)
                    ->whereHas('steps', function($query){
                        $query->where('value', 1)->whereNull('approved_by');
                    });
                })->orWhere(function($query) use ($project_ids){
                    $query->whereIn('to_project', $project_ids)
                    ->whereHas('steps', function($query){
                        $query->where('value', 3)->whereNull('approved_by');
                    });
                })->orWhere(function($query) use ($active_user){
                    $query->when($active_user->id == 610 || $active_user->id == 608, function($query){
                        $query->whereHas('steps', function($query){
                            $query->whereIn('value', [4,7])->whereNull('approved_by');
                        });
                    });
                });
            });
        })
        // ->orWhere(function($query) use ($active_user, $project_ids){
        //     $query->whereHas('requests', function ($query) use ($active_user) {
        //         $query->where('status', 1);
        //     });
        // })
        ->with('requests')->get();
        return response()->json($target_assets);
    }
    public function get_partners_tags(Request $request){
        $partnersQuery = [
            'app' => 118,
            'fields' => ['会社名', '$id']
        ];
        $keyword = $request->key;
        $super = $request->super;
        if($keyword){
            $partnersQuery['query'] = "会社名 like \"{$keyword}%\"";
        }
        if($super){
            $partnersQuery['query'] = "order by \$id desc limit 10";
        }
        $queryStringPartners = http_build_query($partnersQuery);
        $urlSpecs = "https://glowd-hldgs.cybozu.com/k/v1/records.json?$queryStringPartners";
        $partnersResponse = Http::withHeaders($this->kintone_headers())->get($urlSpecs);
        $partnersData = $partnersResponse->json();
        $partnersRecords = $partnersData['records'] ?? [];
        $data = array_map(fn($record) => $record['会社名']['value'] ?? '', $partnersRecords);
        if($keyword){
            if(!in_array($keyword, $data)){
                array_unshift($data, $keyword);
            }
        }
        
        return response()->json($data);

    }
    public function get_task_comment_badge(Request $request){
        $user = $this->active_user();

        $badge_counts = taskRecord::whereHas('taskUsers', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->whereHas('taskRecord.comments', function ($q) use($user) {
                        $q->whereColumn('task_comments.created_at', '>', 'task_users.checked_at')->whereNot('task_comments.user_id', $user->id);
                    });
            })->with(['comments' => function ($query) use ($user) {
                $query->where('user_id', '!=', $user->id);
            }])->get();

        $data = $badge_counts->map(function($task) use($user){
            $comments = $task->comments;
            $task_user = $task->taskUsers->where('user_id', $user->id)->first();
            $comments = $comments->filter(function($comment) use($user, $task_user){
                return $comment->user_id != $user->id && $comment->created_at > $task_user->checked_at;
            });
            return [
                'task_id' => $task->id,
                'comments' => count($comments),
                'project_id' => $task->project_record_id
            ];
        });
        return response()->json($data);
    }
    public function get_dispatch_data(Request $request){
        $request->validate([
            'project_id' => 'required',
        ]);
        $project = ProjectRecord::findOrFail($request->project_id);
        $project_name = $project->name;

        $queryParamsDispatch = [
            'app' => 262,
            "query" => "部門 = \"{$project_name}\"",
        ];
        $queryStringDispatch = http_build_query($queryParamsDispatch);
        $urlSpecs = "https://glowd-hldgs.cybozu.com/k/v1/records.json?$queryStringDispatch";
        $response = Http::withHeaders($this->kintone_headers())->get($urlSpecs);
        $responseData = $response->json();
        $dispatchRecords = $responseData['records'] ?? [];
        $dispatchClean = array_map(function ($record) {
            $spec = [];
            foreach ($record as $key => $value) {
                $spec[$key] = $value['value'] ?? '';
            }
            return $spec;
        }, $dispatchRecords);
        return response()->json($dispatchClean);
    }
    private function profitCollector(Carbon $startInstance, Carbon $endInstance, string $offset, string $project_names_str)
    {
        $startDate = $startInstance->copy()->startOfMonth()->toDateString();
        $endDate = $endInstance->copy()->endOfMonth()->toDateString();

        $queryParamsSpecs = [
            'app' => 1068,
            "query" => "部門 in (\"{$project_names_str}\") and 日付 >= \"{$startDate}\" and 日付 <= \"{$endDate}\" limit 500 offset {$offset}",
            "fields" => ["売上高合計", "内部売上高合計", "販売管理費合計", "間接費配賦", "利益", "利益率", '部門', '日付'],
            "totalCount" => "true",
        ];
        
        $queryStringSpecs = http_build_query($queryParamsSpecs);
        $urlSpecs = "https://glowd-hldgs.cybozu.com/k/v1/records.json?$queryStringSpecs";
        $profits = Http::withHeaders($this->kintone_headers())->get($urlSpecs);
        $profitsData = $profits->json();
        return $profitsData;

    }
    private function settlementCollector(Carbon $startInstance, Carbon $endInstance){
        $client = new Google_Client();
        $client->setApplicationName('Google Sheets API');
        $client->setScopes(['https://www.googleapis.com/auth/spreadsheets.readonly']);
        $client->setAuthConfig(storage_path('app/spread_json_key/gen-lang-client-0333646800-e777adab076d.json')); 
        $client->setAccessType('offline');
        $service = new Google_Service_Sheets($client);
        $sheet_id = '1HTacPGjBDtg3KAK0hToBeJW__fqCp9iH01a38Ihjet8';
        $spreadsheet = $service->spreadsheets->get($sheet_id);
        $sheets = $spreadsheet->getSheets();

        $needed_ranges = [];
        
        $sDate = $startInstance->copy();
        $eDate = $endInstance->copy();
        while ($sDate->lessThanOrEqualTo($eDate)) {
            $tabName = sprintf('%04d%02d', $sDate->year, $sDate->month);
            $needed_ranges[] = $tabName;
            $sDate->addMonth();
        }
        $ranges = [];
        foreach ($sheets as $sheet) {
            if(in_array($sheet['properties']['title'], $needed_ranges)){
                $ranges[] = $sheet['properties']['title'];
            }             
        }
        
        $response = $service->spreadsheets_values->batchGet($sheet_id, ['ranges' => $ranges]);
        $batchSettlementData = [];

        foreach ($response->getValueRanges() as $index => $range) {
            $batchSettlementData[$ranges[$index]] = $range->getValues();
        }
        return $batchSettlementData;
    }
    private function kintone_record_cleaner($records){
        $cleaned = array_map(function ($record) {
            $spec = [];
            foreach ($record as $key => $value) {
                $spec[$key] = $value['value'] ?? '';
            }
            return $spec;
        }, $records);
        return $cleaned;
    }
    public function get_total_finance(FinanceRequest $request): JsonResponse{

        $interval = $request->getInterval();
        $project_ids = $request->getProjectIds();

        $startInstance = Carbon::createFromDate($interval['startYear'], $interval['startMonth'], 1);
        $endInstance = Carbon::createFromDate($interval['endYear'], $interval['endMonth'], 1);
        $durationByMonth = (int) $startInstance->diffInMonths($endInstance, );
        
        if($durationByMonth < 0){
            return response()->json([
                'error' => true,
                'message' => '開始日付は終了日付より前で設定してください。',
            ], 422);
        }
        if($durationByMonth > 12){
            return response()->json([
                'error' => true,
                'message' => '最大12ヶ月まで選択できます。',
            ], 422);
        }        
        
        $projects = ProjectRecord::whereIn('id', $project_ids)->get();
        $project_names = $projects->pluck('name')->toArray();   

        $project_names_str = implode('","', $project_names); 


        //get settlement data
        $batchSettlementData = $this->settlementCollector($startInstance, $endInstance);
        //get settlement data


        $profitDataCollection = collect();
        $firstLoad = $this->profitCollector($startInstance, $endInstance, '0', $project_names_str);
        $totalCount = $firstLoad['totalCount'] ?? 0;
        $fisrtData = $firstLoad['records'] ?? [];
        $firstDataClean = $this->kintone_record_cleaner($fisrtData);

        if(count($firstDataClean)){
            $profitDataCollection = collect($firstDataClean);
        }
        if($totalCount > 500){
            $offset = 500;
            while($offset < $totalCount){
                $profitData = $this->profitCollector($startInstance, $endInstance, $offset, $project_names_str);
                $totalCount = $profitData['totalCount'] ?? 0;
                $profitRecords = $profitData['records'] ?? [];
                if(count($profitRecords)){
                    $profitRecordsClean = $this->kintone_record_cleaner($profitRecords);
                    if(count($profitRecordsClean)){
                        $profitDataCollection->push(...$profitRecordsClean);
                    }
                }
                if($offset > 10000){
                    break;
                }
                $offset += 500;
            }
        }        
        //get yearly plan data

        // Calculate years between startInstance and endInstance
        $startYear = $startInstance->year;
        $endYear = $endInstance->year;
        $yearlyPlanData = [];
        $month_headers = [];
        $sub_headers = [];
        // Create an array of years
        for ($year = $startYear; $year <= $endYear; $year++) {
            $file_path = storage_path("app/yearly_plan/{$year}.xlsx");
            $file_exists = file_exists($file_path);
            if($file_exists){              
            
                $file = Excel::toCollection(new YearlyPlanImport, $file_path);
                $yearlyPlanData[$year] = $file[0];
                $yearlyPlanData[$year]->shift()->toArray();
                $month_headers = $yearlyPlanData[$year]->shift()->toArray();
                $sub_headers = $yearlyPlanData[$year]->shift()->toArray();  
            }else{
                $yearlyPlanData[$year] = collect();
                $month_headers[$year] = [];
                $sub_headers[$year] = [];

            }
            
        }    

        //get yearly plan data        
        
        $plan_res_data = [];
        $default_data = [
            "sales" => 0,
            "expense" => 0,
            "profit" => 0,
            "profit_rate" => 0,
        ];

        $defaultSumData = [
            'yearly_plan' => [
                'sales' => 0,
                'expense' => 0,
            ],
            'profit' => [
                'sales' => 0,
                'expense' => 0,
            ],
            'settlement' => [
                'sales' => 0,
                'expense' => 0,
            ],
        ];
        $sumData = [];
        $summarizeData = $defaultSumData;

        //process each data for each project
        foreach($project_names as $project_name){
            
            $stDate = $startInstance->copy();
            $etDate = $endInstance->copy();
            $sumData[$project_name] = $defaultSumData;
            // foreach($months_array as $month){
            while ($stDate->lessThanOrEqualTo($etDate)) {
                $month = $stDate->month;
                $year = $stDate->year;
                $settle_tab_index = sprintf('%04d%02d', $year, $month);
                $projectsData = $yearlyPlanData[$year]->first(fn($row) => $row[1] === $project_name);
                if($projectsData){
                    $month_target_indexes = [];
                    $month_found = false;
                    foreach($month_headers as $key => $header){
                        if(!$month_found){
                            if($header == "{$year}年{$month}月"){
                                $month_found = true;  
                                $month_target_indexes[] = $key;      
                            }
                        }else{
                            if($header == null || $header == "{$year}年{$month}月"){
                                $month_target_indexes[] = $key; 
                            }else{
                                break;
                            }
                        }                        
                    }
                    $sub_headers_for_target_month = array_filter($sub_headers, function ($key) use($month_target_indexes) {
                        return in_array($key, $month_target_indexes);
                    }, ARRAY_FILTER_USE_KEY);
                    $plan_sales_index_1 = array_search('合計 売上高', $sub_headers_for_target_month);
                    $plan_sales_index_2 = array_search('合計 内部売上高合計', $sub_headers_for_target_month);
                    $plan_expense_index_1 = array_search('合計 給料手当', $sub_headers_for_target_month);
                    $plan_expense_index_2 = array_search('合計 外注費', $sub_headers_for_target_month);
                    $plan_expense_index_3 = array_search('合計 販管費その他', $sub_headers_for_target_month);
                    $plan_expense_index_4 = array_search('合計 間接費配賦', $sub_headers_for_target_month);
                    $plan_expense_index_5 = array_search('合計 内部発注合計', $sub_headers_for_target_month);
                    $plan_expense_index_6 = array_search('業績連動型賞与引当金', $sub_headers_for_target_month);

                    $profit_index = array_search('利益', $sub_headers_for_target_month);
                    $profit_rate_index = array_search('利益率', $sub_headers_for_target_month);
                    $totalSales = round((float) $projectsData[$plan_sales_index_1] + (float) $projectsData[$plan_sales_index_2], 0, PHP_ROUND_HALF_UP);
                    $totalExpense = round((float)  $projectsData[$plan_expense_index_1] + (float) $projectsData[$plan_expense_index_2] + (float) $projectsData[$plan_expense_index_3] + (float) $projectsData[$plan_expense_index_4] + (float) $projectsData[$plan_expense_index_5] + (float) $projectsData[$plan_expense_index_6], 0, PHP_ROUND_HALF_UP);
                    $planData = [
                        "sales" => $totalSales,
                        "expense" => $totalExpense,
                        "profit" => round((float) $projectsData[$profit_index], 0, PHP_ROUND_HALF_UP),
                        "profit_rate" => (float) $projectsData[$profit_rate_index],
                    ];
                    $plan_res_data[$project_name][$month]['yearly_plan'] = $planData;

                    
                    $sumData[$project_name]['yearly_plan']['sales'] = ($sumData[$project_name]['yearly_plan']['sales'] ?? 0) + $totalSales;
                    $sumData[$project_name]['yearly_plan']['expense'] = ($sumData[$project_name]['yearly_plan']['expense'] ?? 0) + $totalExpense;
                    $summarizeData['yearly_plan']['sales'] = ($summarizeData['yearly_plan']['sales'] ?? 0) + $totalSales;
                    $summarizeData['yearly_plan']['expense'] = ($summarizeData['yearly_plan']['expense'] ?? 0) + $totalExpense;
                }
                else{
                    $plan_res_data[$project_name][$month]['yearly_plan']  = $default_data;
                }




                
                $dateInstance = Carbon::createFromDate($year, $month, 1);
                $profitData = $profitDataCollection->where('部門', $project_name)
                ->filter(function ($item) use ($year, $month) {
                    return Carbon::parse($item['日付'])->year == $year &&
                            Carbon::parse($item['日付'])->month == $month;
                })
                ->first();
                if($profitData){
                    $totalSales = round( (float) $profitData['売上高合計'] + (float) $profitData['内部売上高合計'], 0, PHP_ROUND_HALF_UP);
                    $totalExpense = round((float)  $profitData['販売管理費合計'] + (float) $profitData['間接費配賦'], 0, PHP_ROUND_HALF_UP);
                    $profitData = [
                        "sales" => $totalSales,
                        "expense" => $totalExpense,
                        "profit" => round((float)(float) $profitData['利益'], 0, PHP_ROUND_HALF_UP),
                        "profit_rate" => (float) $profitData['利益率'],
                    ];
                    $plan_res_data[$project_name][$month]['profit'] = $profitData;
                    $sumData[$project_name]['profit']['sales'] = ($sumData[$project_name]['profit']['sales'] ?? 0) + $totalSales;
                    $sumData[$project_name]['profit']['expense'] = ($sumData[$project_name]['profit']['expense'] ?? 0) + $totalExpense;
                    $summarizeData['profit']['sales'] = ($summarizeData['profit']['sales'] ?? 0) + $totalSales;
                    $summarizeData['profit']['expense'] = ($summarizeData['profit']['expense'] ?? 0) + $totalExpense;
                }
                else{
                    $plan_res_data[$project_name][$month]['profit'] = $default_data;
                }        
                




                $settlements = $batchSettlementData[$settle_tab_index] ?? [];
                if (!empty($settlements )) {
                    $settlement_headers = $settlements[1];
                    $settlement_data = array_slice($settlements, 2);
                    $project_index_in_settlement = array_search($project_name, array_column($settlement_data, 1)); 
                    if($project_index_in_settlement !== false){
                        $settlementOfProject = $settlement_data[$project_index_in_settlement];
                        $settlement_sales_index = array_search('収入', $settlement_headers);
                        $settlement_expense_index = array_search('支出', $settlement_headers);
                        $settlement_additional_expense_index = array_search('間接費配賦', $settlement_headers);
                        $settlement_profit_index = array_search('利益', $settlement_headers);
                        $settlement_profit_rate_index = array_search('利益率', $settlement_headers);                                
 
                        $totalSales = round((float) str_replace(',', '', $settlementOfProject[$settlement_sales_index]), 0, PHP_ROUND_HALF_UP);
                        $totalExpense = round((float) str_replace(',', '', $settlementOfProject[$settlement_expense_index]) + (float) str_replace(',', '', $settlementOfProject[$settlement_additional_expense_index]), 0, PHP_ROUND_HALF_UP);
                        $plan_res_data[$project_name][$month]['settlement']= [
                            'sales' => $totalSales,
                            'expense' => $totalExpense ?? 0,
                            'profit' => round((float)(float) str_replace(',', '', $settlementOfProject[$settlement_profit_index]), 0, PHP_ROUND_HALF_UP),
                            'profit_rate' => (float) str_replace('%', '', $settlementOfProject[$settlement_profit_rate_index]),
                        ];
                        $sumData[$project_name]['settlement']['sales'] = ($sumData[$project_name]['settlement']['sales'] ?? 0) + $totalSales;
                        $sumData[$project_name]['settlement']['expense'] = ($sumData[$project_name]['settlement']['expense'] ?? 0) + $totalExpense;
                        $summarizeData['settlement']['sales'] = ($summarizeData['settlement']['sales'] ?? 0) + $totalSales;
                        $summarizeData['settlement']['expense'] = ($summarizeData['settlement']['expense'] ?? 0) + $totalExpense;
                 
                    }else{
                        $plan_res_data[$project_name][$month]['settlement'] = $default_data;
                    }                    
                    

                }else{
                    $plan_res_data[$project_name][$month]['settlement'] = $default_data;
                }

                $stDate->addMonth();
            }

            
        }

        $final_data = [
            'plan_res_data' => $plan_res_data,
            'sumData' => $sumData,
            'summarizeData' => $summarizeData,
        ];
        return response()->json( $final_data);


    }
    public function get_projects_external(Request $request){
        $projects = ProjectRecord::get();
        return response()->json($projects);
    }
    public function get_team_external(Request $request){
        $users = User::where('retire', 0)
        ->where('hide_flag', 0)
        ->where('partner_flag', 0)
        ->where('id', '>', 105 )
        ->select('id', 'name', 'name_kana', 'motto', 'icon_path', 'icon_bg', 'icon_bg', 'icon_bg', 'office_id', 'position_id')->with(['positions'])
        ->get();
        return response()->json($users);
    }   
    public function set_project_goal_step_status(Request $request){
        $request->validate([
            'project_goal_id' => 'required',
            'step_id' => 'required',
            'status' => 'required|in:0,1',
        ]);
        $project_goal = ProjectGoal::findOrFail($request->project_goal_id);
        $step = $project_goal->steps()->findOrFail($request->step_id);
        $step->update([
            'status' => $request->status,
        ]);
        return response()->json($step);
    }
    public function project_goal_report_create(Request $request){
        $request->validate([
            'project_goal_id' => 'required',
            'content' => 'required',
        ]);
        $project_goal = ProjectGoal::findOrFail($request->project_goal_id);
        $report = $project_goal->reports()->create([
            'content' => $request->content,
            'user_id' => auth()->user()->id,
        ]);
        return response()->json($report);
    }
    public function get_previous_goals(Request $request){
        $request->validate([
            'user_id' => 'required',
            'year' => 'required',
            'which_half' => 'required|in:first,second',
        ]); 


        $previous_goals = ProjectGoal::where('user_id', $request->user_id)
        ->where('year', $request->year)
        ->where('which_half', $request->which_half)
        ->with(['steps'])
        ->get();
        return response()->json($previous_goals);


    }
    public function save_project_progress(Request $request){
        $request->validate([
            'goal_id' => 'required',
            'type' => 'required',
            'progress' => 'required|numeric|min:0|max:100',
        ]);
        $project_goal = ProjectGoal::findOrFail($request->goal_id);
        if($request->type == 'kgi'){
            
            $project_goal->update([
                'achievement_rate' => $request->progress,
            ]);
            return response([], 200); 
        }
        else if($request->type == 'kpi'){
            $step = $project_goal->steps()->findOrFail($request->step_id);
            $step->update([
                'progress' => $request->progress,
            ]);
            return response([], 200); 
        }
        else{
            return response()->json(['message' => 'Invalid type'], 422);
        }       
        
    }
    public function salary_issue_action_complete(Request $request){
        $request->validate([
            'issue_id' => 'required',
            'action_id' => 'required',
        ]);
        $issue = SalaryIssue::findOrFail($request->issue_id);
        $action = $issue->actions()->findOrFail($request->action_id);
        $action->update([
            'status' => $action->status == 1 ? 0 : 1,
        ]);
        return response([], 200);
    }
    public function get_random_projects(Request $request){

        $count = 10;
        $projects = ProjectRecord::inRandomOrder()->take($count)->get();
        return response()->json($projects);
    }
    public function mentionable_users(Request $request) {
        $request->validate([
            'projectIds' => 'required'
        ]);
        $projects = ProjectRecord::whereIn('id', $request->projectIds)
            ->with('manager:id,name,icon_path,icon_bg')
            ->get();

        $managers = $projects->pluck('manager')->flatten(1)->unique('id')->values();
        $directors = User::where('position_id', '<', 6)
            ->orWhere('id', 610)
            ->select('id', 'name', 'icon_path', 'icon_bg')
            ->get();
        $users = $managers->concat($directors)->unique('id')->values();

        return response()->json($users);
    }
    public function project_finance_comment(Request $req) {
        $user = $this->active_user();
        $data = $req->validate([
            'project_record_id'   => ['required','integer','exists:project_records,id'],
            'comment'             => ['required','string','max:20000'],
            'type'                => ['nullable','string','in:年度予算,損益計画,実績'],
            'mentioned_user_ids'  => ['array'],
            'mentioned_user_ids.*'=> ['integer','exists:users,id'],
            'period'              => ['required','date_format:Y-m']
        ]);

        DB::transaction(function () use (&$comment, $data, $user) {
            $comment = ProjectFinanceComment::create([
                'project_record_id' => $data['project_record_id'],
                'user_id'           => $user->id,
                'comment'           => $data['comment'],
                'type'              => $data['type'] ?? null,
                'period'            => $data['period']
            ]);

            if (!empty($data['mentioned_user_ids'])) {
                $rows = collect($data['mentioned_user_ids'])
                    ->unique()
                    ->map(fn($uid) => [
                        'comment_id'        => $comment->id,
                        'mentioned_user_id' => $uid,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ])->all();

                DB::table('project_finance_comment_mentions')->insert($rows);
            }
        });
        if(!empty($data['mentioned_user_ids'])){     
            $emails = User::whereIn('id', $data['mentioned_user_ids'])
                    ->pluck('email')
                    ->filter()          // drop null/empty
                    ->unique()
                    ->values()
                    ->all();          
            $project = ProjectRecord::findOrFail($data['project_record_id']);              
            
            $e_title = $project->name;
                                                 
            $rawContent    = (string) ($data['comment'] ?? '');
            $emailContent  = preg_replace('/\s*\[To:[^:\]\|]+(?:\|\d+)?:\]\s*/u', ' ', $rawContent);
            $emailContent  = trim(preg_replace('/\s{2,}/', ' ', $emailContent));

            $blocked = preg_match('/\b(pass|pw|password)\b/i', $rawContent)
            || str_contains($rawContent, 'パスワード')
            || str_contains($rawContent, 'ﾊﾟｽﾜｰﾄﾞ');        
            $url = rtrim(config('app.url'), '/') . "/project/{$project->id}/finance";
            $url .= "?period={$data['period']}";

            SendProjectEmail::dispatch($emails, new ProjectMention($project, $emailContent, $blocked, $url, auth()->user()));
            
        }
        // load author for UI if you want
        $comment->load(['author:id,name,icon_path,icon_bg']);

        return response()->json($comment, 201);
    }

    public function get_project_finance_comments(Request $req) {
        $data = $req->validate([
            'project_record_id' => ['required','integer','exists:project_records,id'],
            'period'            => ['required','date_format:Y-m']
        ]);
        $periodDate = Carbon::createFromFormat('Y-m', $data['period'])->startOfMonth()->toDateString();

        $comment = ProjectFinanceComment::where('project_record_id', $data['project_record_id'])
                ->whereDate('period', $periodDate)
                ->with(['author:id,name,icon_path,icon_bg'])
                ->get();
        
        return response()->json($comment);
    }
    public function monthlyCount(Request $req, ProjectRecord $project)
    {
        $data = $req->validate([
            'period' => ['required','string'], // e.g. "September-25" or "Sep-25" or "2025-09"
        ]);

        $periodDate = Carbon::createFromFormat('Y-m', $data['period'])->startOfMonth()->toDateString(); // Carbon
        $count = ProjectFinanceComment::where('project_record_id', $project->id)
            ->whereDate('period', $periodDate)
            ->count();

        // always return a stable key
        return response()->json(
            $count,
        );
    }

    public function get_finance_comment_badge() {
        $user = $this->active_user();
        $userId = $user->id;

        $isDirector = ($user->position_id < 6) || ($userId === 610);

        if ($isDirector) {
            $projectIds = ProjectRecord::query()->pluck('id');
        } else {
            $projectIds = ProjectRecord::query()
                ->whereHas('manager', fn($q) => $q->where('users.id', $userId))
                ->pluck('id');

            if ($projectIds->isEmpty()) {
                return response()->json([]);
            }
        }

        $q = DB::table('project_finance_comments as c')
            ->select('c.project_record_id', 'c.period', DB::raw('COUNT(*) as unread_count'))
            ->leftJoin('project_finance_last_reads as lr', function ($j) use ($userId) {
                $j->on('lr.project_record_id', '=', 'c.project_record_id')
                ->where('lr.user_id', '=', $userId);
            })
            ->whereIn('c.project_record_id', $projectIds)
            ->whereNull('c.deleted_at')       // if SoftDeletes
            ->where('c.user_id', '!=', $userId)
            ->where(function ($w) {
                $w->whereColumn('c.created_at', '>', 'lr.last_read_at')
                ->orWhereNull('lr.last_read_at');
            });
         $rows = $q->groupBy('c.project_record_id', 'c.period')->get();

        $data = $rows->map(fn($r) => [
            'project_id' => (int) $r->project_record_id,
            'period'     => $r->period,   
            'comments'   => (int) $r->unread_count,
        ])->values();

        return response()->json($data);
    }

    public function mark_finance_read(Request $request, int $projectId) {
        $user = $this->active_user();
        
        $lastRead = ProjectFinanceLastRead::updateOrCreate(
            ['project_record_id' => $projectId, 'user_id' => $user->id],
            ['last_read_at' => now()]
        );

        return response()->json(['status' => 'ok']);
    }

    public function finance_comment_update(Request $request){
        $request->validate([
            'id' => 'required',
            'comment' => 'required',
        ]); 
        $comment = ProjectFinanceComment::findOrFail($request->id);
        $comment->update(['comment' => $request->comment]);
        return response(200);

    }
    public function finance_comment_delete(Request $request){
        $request->validate([
            'id' => 'required',
        ]); 
        $comment = ProjectFinanceComment::findOrFail($request->id);
        $comment->delete();
        return response(200);

    }


    // #Metrics

    public function project_metrics(Request $req) {
        $q = ProjectMetric::query()
            ->when($req->boolean('active'), fn($qq) => $qq->where('is_active', 1))
            ->orderBy('sort_order')->orderBy('id');

        return $q->get()->map(fn($m) => [
            'id'         => $m->id,
            'code'       => $m->code,
            'label_ja'   => $m->label_ja,
            'label_en'   => $m->label_en,
            'kind'       => $m->kind,
            'value_type' => $m->value_type,
            'line'       => $m->line,
            'is_active'  => (bool)$m->is_active,
            'sort_order' => $m->sort_order,
            'scenario_code' => $m->scenario_code,
            'scenario_label_ja' => $m->scenario_label_ja,
            'expression' => $m->formula?->expression,
            'aliases'    => $m->aliases ?? [],
        ]);
    }
    public function project_sales_expenses(ProjectRecord $project, Request $req) {
        $data = $req->validate([
            'start' => ['required', 'date_format:Y-m-d'],
            'end'   => ['required', 'date_format:Y-m-d'],
        ]);
        
        $start = Carbon::parse($data['start'])->startOfMonth();
        $end = Carbon::parse($data['end'])->startOfMonth();

        $expenses = ProjectExpense::query()
            ->where('project_record_id', $project->id)
            ->whereBetween('period', [$start, $end])
            ->orderBy('period')
            ->get();

        $sales = ProjectSale::query()
            ->where('project_record_id', $project->id)
            ->whereBetween('period', [$start, $end])
            ->orderBy('period')
            ->get();
        
        $expMap = $expenses->keyBy(fn ($r) => Carbon::parse($r->period)->startOfMonth()->toDateString());
        $salMap = $sales->keyBy(fn ($r) => Carbon::parse($r->period)->startOfMonth()->toDateString());

        $periods = CarbonPeriod::create($start, '1 month', $end);
        $out = [];

        foreach ($periods as $p) {
            $ymd = $p->toDateString();

            $e = $expMap->get($ymd);
            $s = $salMap->get($ymd);

            $out[$ymd] = [
                // sales side
                'internal_sales' => $s?->internal_sales,
                'sales'          => $s?->sales,

                // expense side
                'bonus'          => $e?->bonus,
                'indirect'       => $e?->indirect,
                'internal_orders'=> $e?->internal_orders,
                'outsourcing'    => $e?->outsourcing,
                'salaries'       => $e?->salaries,
                'sga_other'      => $e?->sga_other,
            ];
        }

        return response()->json($out);
    
    }
    public function project_metrics_with_values(ProjectRecord $project, Request $req) {
        $data = $req->validate([
            'start' => ['required', 'date_format:Y-m-d'],
            'end'   => ['required', 'date_format:Y-m-d'],
            'active'=> ['sometimes', 'boolean'],
        ]);
        $metrics = ProjectMetric::query()
            ->when($req->boolean('active'), fn($q) => $q->where('is_active', 1))
            ->with([
                'formula:id,project_metric_id,expression',
                'values' => fn($q) => $q
                    ->where('project_record_id', $project->id)
                    ->whereBetween('period', [$data['start'], $data['end']])
                    ->orderBy('period'),
            ])
            ->orderBy('id')
            ->get();
        return $metrics->map(function ($m) {
            $vals = [];
            foreach ($m->values as $v) {
                $vals[$v->period->format('Y-m-d')] = $v->value;
            }
            return [
                'id'                => $m->id,
                'code'              => $m->code,
                'label_ja'          => $m->label_ja,
                'label_en'          => $m->label_en,
                'kind'              => $m->kind,
                'value_type'        => $m->value_type,
                'line'              => $m->line,
                'is_active'         => (bool) $m->is_active,
                'sort_order'        => $m->sort_order,
                'scenario_code'     => $m->scenario_code,
                'scenario_label_ja' => $m->scenario_label_ja,
                'expression'        => $m->formula?->expression,
                'aliases'           => $m->aliases ?? [],
                'values'            => $vals,
            ];
        });
    }
    public function project_metrics_for_period(ProjectRecord $project, Request $req) {
        $period = $req->validate(['period' => ['required', 'date_format:Y-m-d']])['period'];

        $metrics = ProjectMetric::with([
            'formula:id,project_metric_id,expression',
            'values' => fn($q) => $q
                ->where('project_record_id', $project->id)
                ->where('period', $period),
        ])->orderBy('sort_order')->orderBy('id')->get();

        return $metrics->map(fn($m) => [
            'id'         => $m->id,
            'code'       => $m->code,
            'label_ja'   => $m->label_ja,
            'label_en'   => $m->label_en,
            'kind'       => $m->kind,
            'value_type' => $m->value_type,
            'line'       => $m->line,
            'is_active'  => (bool) $m->is_active,
            'sort_order' => $m->sort_order,
            'scenario_code' => $m->scenario_code,
            'scenario_label_ja' => $m->scenario_label_ja,
            'expression' => $m->formula?->expression,
            'aliases'    => $m->aliases ?? [],
            'value'      => optional($m->values->first())->value,
        ]);
    }
    public function metric_store(StoreMetricRequest $req) {
        $data = $req->validated();

        $metric = ProjectMetric::create([
            'code' => $data['code'],
            'label_ja' => $data['label_ja'],
            'kind' => $data['kind'],
            'value_type' => $data['value_type'],
            'line' => $data['line'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
            'scenario_code' => $data['scenario_code'],
            'scenario_label_ja' => $data['scenario_label_ja'],
            'aliases' => $data['aliases'] ?? [],
        ]);

        if ($metric->kind === 'derived') {
            $expr = $this->normalizeToCodes($data['expression'] ?? '');
            $this->assertValidFormulaOrFail($expr, $metric->code);
            $metric->formula()->create([
                'expression' => $expr,
                'version'    => 1,
            ]);
        }

        return response()->json(['id' => $metric->id], 201);
    }
    public function validateExpression(Request $req)
    {
        $req->validate([
            'expression'  => ['required','string'],
            'target_code' => ['nullable','string']
        ]);

        $expr = (string) $req->input('expression');
        $target = (string) $req->input('target_code', '');

        // Throws 422 with message if invalid
        $this->assertValidFormulaOrFail($expr, $target);

        return response()->json([
            'ok'         => true,
            'normalized' => $this->normalizeToCodes($expr),
        ]);
    }
    protected function tokenize(string $expr): array
    {
        $tokens = [];
        $parts = preg_split('/\s+/', trim($expr));
        
        foreach ($parts as $part) {
            preg_match_all('/[A-Za-z_][A-Za-z0-9_]*|\d+(?:\.\d+)?|[\+\-\*\/\(\)]/', $part, $m);
            $tokens = array_merge($tokens, $m[0] ?? []);
        }
        
        return array_filter($tokens);
    }


    protected function allMetricAliases(): array
    {
        return ProjectMetric::query()->get()->flatMap(function ($m) {
            $set = array_filter(array_unique([
                $m->code,
                mb_strtolower($m->label_ja ?? ''),
                mb_strtolower($m->label_en ?? ''),
                ...array_map('mb_strtolower', (array)($m->aliases ?? [])),
            ]));
            return collect($set)->mapWithKeys(fn($k) => [$k => $m->code]);
        })->toArray();
    }
    protected function normalizeToCodes(string $expr): string
    {
        $expr = mb_convert_kana($expr, 'as');
        $expr = preg_replace('/\s+/', ' ', trim($expr));

        $alias = $this->allMetricAliases(); // label_ja/label_en/aliases/code => code

        $out = [];
        foreach ($this->tokenize($expr) as $tok) {
            if (in_array($tok, ['+','-','*','/','(',')'], true) || is_numeric($tok)) {
                $out[] = $tok; continue;
            }
            $key = mb_strtolower(\Illuminate\Support\Str::of($tok)->replace(['-', ' '], '_'));
            $out[] = $alias[$key] ?? $tok;
        }
        return implode(' ', $out);
    }



   protected function assertValidFormulaOrFail(string $expr, string $targetCode = ''): void
    {
        $normalized = $this->normalizeToCodes($expr);
        $tokens = $this->tokenize($normalized);
        \Log::info('Tokens: ', $tokens);
        if (empty($tokens)) abort(422, 'Empty expression.');

        // known metric codes and allowed functions
        $codes = ProjectMetric::pluck('code')->all();
        $codeSet = array_flip($codes);
        $funcs = ['nullif','pct','ratio'];

        // quick operator spam (++ ** //)
        if (preg_match('/[+\-*\/]{2,}/', implode(' ', $tokens))) {
            abort(422, 'Invalid operator sequence.');
        }

        $paren = 0;
        $prevType = null;
        $prevTok  = null;

        foreach ($tokens as $i => $tok) {
            $op_check = in_array($tok, ['+','-','*','/'], true);
            $lpar_check = $tok === '(';
            $rpar_check = $tok === ')';
            $num_check = is_numeric($tok);
            $id_check = preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $tok);
            // Let's test each condition manually
            if ($op_check) {
                $type = 'op';
            } elseif ($lpar_check) {
                $type = 'lpar';
            } elseif ($rpar_check) {
                $type = 'rpar';
            } elseif ($num_check) {
                $type = 'num';
            } elseif ($id_check) {
                $type = 'id';
            } else {
                $type = 'bad';
            }
                
            // Now try the original match
            // $match_type = match (true) {
            //     in_array($tok, ['+','-','*','/'], true) => 'op',
            //     $tok === '('                           => 'lpar',
            //     $tok === ')'                           => 'rpar', 
            //     is_numeric($tok)                       => 'num',
            //     preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $tok) => 'id',
            //     default                                => 'bad',
            // };

            // id must be known (or an allowed function name)
            if ($type === 'id' && !isset($codeSet[$tok]) && !in_array($tok, $funcs, true)) {
                abort(422, "Unknown identifier: {$tok}");
            }
            // simple function rule: func must be followed by '('
            if (in_array($tok, $funcs, true)) {
                if (($tokens[$i+1] ?? null) !== '(') {
                    abort(422, "Function {$tok} must be followed by '('");
                }
            }

            // parens balance
            if ($type === 'lpar') $paren++;
            if ($type === 'rpar') { $paren--; if ($paren < 0) abort(422, 'Parentheses mismatch.'); }
            // core sequence rules
            if (in_array($type, ['id','num'], true) && in_array($prevType, ['id','num','rpar'], true)) {
                abort(422, "Missing operator between '{$prevTok}' and '{$tok}'.");
            }
            
            if ($type === 'lpar' && in_array($prevType, ['id','num','rpar'], true)) {
                abort(422, "Missing operator before '(' at token {$i}.");
            }
            if ($type === 'op' && ($prevType === null || $prevType === 'op' || $prevType === 'lpar')) {
                abort(422, 'Operator in invalid position.');
            }
            if ($type === 'rpar' && $prevType === 'op') {
                abort(422, 'Operator cannot precede ")".');
            }

            $prevType = $type;
            $prevTok  = $tok;
        }

        if ($paren !== 0) abort(422, 'Parentheses mismatch.');
        if (in_array($prevType, ['op','lpar'], true)) abort(422, 'Expression cannot end with operator or "(".');

        // optional: cycle check
        if ($targetCode && $this->dependsOn($targetCode, $targetCode, [])) {
            abort(422, 'Cyclic dependency detected.');
        }
    }

    


    // crude dependency check using current formulas
    protected function dependsOn(string $start, string $needle, array $seen): bool
    {
        if (isset($seen[$start])) return false;
        $seen[$start] = true;

        $f = ProjectMetric::where('code', $start)->first()?->formula?->expression;
        if (!$f) return false;

        preg_match_all('/[A-Za-z_][A-Za-z0-9_]*/', $f, $m);
        $deps = array_values(array_unique(array_filter($m[0], fn($x) => !in_array($x, ['nullif','pct','ratio'], true))));

        foreach ($deps as $d) {
            if ($d === $needle) return true;
            if ($this->dependsOn($d, $needle, $seen)) return true;
        }
        return false;
    }

    public function metric_delete(int $id) {
        $exists = ProjectMetric::where('id', $id)->exists();
        if (! $exists) {
            abort(404, 'メトリックが見つかりません');
        }

        ProjectMetric::destroy($id);

        return response()->json(['status' => 'ok']);
    }
    public function metric_toggle(Request $req, int $id) {
        $data = $req->validate([
            'is_active'  => 'required',
        ]);

        $metric = ProjectMetric::findOrFail($id);
        $metric->update(['is_active' => $data['is_active']]);
        
        return response()->json(['status' => 'ok']);
    }
    public function yearly_budget_store(ProjectRecord $project, Request $req) {
        $data = $req->validate([
            'scenario_code'          => ['required','string'],                 // e.g. 'annual_budget'
            'project_record_id'      => ['required','integer','in:'.$project->id],
            'months'                 => ['required','array','min:1'],

            'months.*.period'        => ['required','date_format:Y-m-d'],      // 'YYYY-MM-01'
            'months.*.sales'         => ['required','array'],
            'months.*.sales.sales'           => ['nullable','numeric'],
            'months.*.sales.internal_sales'  => ['nullable','numeric'],

            'months.*.expenses'      => ['required','array'],
            'months.*.expenses.salaries'        => ['nullable','numeric'],
            'months.*.expenses.outsourcing'     => ['nullable','numeric'],
            'months.*.expenses.internal_orders' => ['nullable','numeric'],
            'months.*.expenses.sga_other'       => ['nullable','numeric'],
            'months.*.expenses.indirect'        => ['nullable','numeric'],
            'months.*.expenses.bonus'           => ['nullable','numeric'],
        ]);

        $codes = [
            'sales' => "{$data['scenario_code']}_sales",
            'expenses' => "{$data['scenario_code']}_expenses",
        ];
        $metricIds = ProjectMetric::whereIn('code', array_values($codes))
            ->pluck('id', 'code');

        DB::transaction(function () use ($project, $data, $metricIds, $codes) {
            $now = now();
            $mvRows = [];

            foreach ($data['months'] as $m) {
                $period = Carbon::parse($m['period'])->startOfMonth()->toDateString();

                ProjectSale::updateOrCreate(
                    ['project_record_id' => $project->id, 'period' => $period],
                    [
                        'sales'          => $m['sales']['sales']          ?? 0,
                        'internal_sales' => $m['sales']['internal_sales'] ?? 0,
                    ]
                );

                ProjectExpense::updateOrCreate(
                    ['project_record_id' => $project->id, 'period' => $period],
                    [
                        'salaries'        => $m['expenses']['salaries']        ?? 0,
                        'outsourcing'     => $m['expenses']['outsourcing']     ?? 0,
                        'internal_orders' => $m['expenses']['internal_orders'] ?? 0,
                        'sga_other'       => $m['expenses']['sga_other']       ?? 0,
                        'indirect'        => $m['expenses']['indirect']        ?? 0,
                        'bonus'           => $m['expenses']['bonus']           ?? 0,
                    ]
                );

                // 2) Compute totals (use payload; if you want canonical truth, re-read from DB here)
                $totalSales =
                    ($m['sales']['sales']          ?? 0) +
                    ($m['sales']['internal_sales'] ?? 0);

                $totalExpenses =
                    ($m['expenses']['salaries']        ?? 0) +
                    ($m['expenses']['outsourcing']     ?? 0) +
                    ($m['expenses']['internal_orders'] ?? 0) +
                    ($m['expenses']['sga_other']       ?? 0) +
                    ($m['expenses']['indirect']        ?? 0) +
                    ($m['expenses']['bonus']           ?? 0);

                // 3) Upsert ONLY the total metrics (if they exist)
                if ($metricIds->has($codes['sales'])) {
                    $mvRows[] = [
                        'project_record_id' => $project->id,
                        'project_metric_id' => $metricIds[$codes['sales']],
                        'period'            => $period,
                        'value'             => $totalSales,
                        'source'            => 'manual',
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ];
                }
                if ($metricIds->has($codes['expenses'])) {
                    $mvRows[] = [
                        'project_record_id' => $project->id,
                        'project_metric_id' => $metricIds[$codes['expenses']],
                        'period'            => $period,
                        'value'             => $totalExpenses,
                        'source'            => 'manual',
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ];
                }
            }

            if ($mvRows) {
                ProjectMetricValue::upsert(
                    $mvRows,
                    ['project_record_id','project_metric_id','period'],
                    ['value','source','updated_at']
                );
            }
        });

        return response()->json(['ok' => true]);
    }
} 
