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
use App\Models\ProjectCase;
use App\Models\ProjectMetricFormula;
use App\Models\ProjectMetricValue;
use App\Models\ProjectMetricSubMetric;
use App\Models\ProjectExpense;
use App\Models\ProjectSale;
use App\Models\messageFile;
use App\Models\ProjectMemberReportNotification;
use App\Models\ProjectContract;
use App\Models\ProjectResourceComment;

use Illuminate\Support\Facades\Mail;
use App\Mail\ProjectMention;
use App\Jobs\SendGoalIssueMentionMail;
use App\Jobs\SendProjectEmail;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\BoardController;
use App\Services\SharedService;
use App\Services\VarianceService;
use App\Imports\EvaluationImport;
use App\Exports\YearlyBudgetTemplate;
use App\Imports\YearlyBudgetImport;
use App\Http\Requests\StoreMetricRequest;
use App\Http\Requests\UpdateMetricRequest;
use App\Infrastructure\Kintone\KintoneClient;
use App\Infrastructure\Sheets\GoogleSheetsClient;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File; 
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
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

    private const CASE_KINDS = ['ACTUAL'];
    private const DEFAULT_ACTUAL_STATUSES = [
        ['status_id' => 1, 'label' => '新規契約', 'is_system_default' => true, 'sort_order' => 1],
        ['status_id' => 2, 'label' => '継続契約', 'is_system_default' => true, 'sort_order' => 2],
        ['status_id' => 3, 'label' => 'リプレイス・アップグレード',   'is_system_default' => true, 'sort_order' => 3],
        ['status_id' => 4, 'label' => 'オプション契約', 'is_system_default' => true, 'sort_order' => 4],
        ['status_id' => 5, 'label' => 'アポイント取得', 'is_system_default' => true, 'sort_order' => 5],
    ];
    private const SYSTEM_STATUS_LABELS = [
        1 => '新規契約',
        2 => '継続契約',
        3 => 'リプレイス・アップグレード',
        4 => 'オプション契約',
        5 => 'アポイント取得',
    ];
    public function __construct(
        BoardController $boardController, 
        SharedService $sharedService, 
        private KintoneClient $api,
        private GoogleSheetsClient $client
    ){
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
        $year = $data['year'] ?? null;
        $which_half = $data['which_half'] ?? null;
        $usersLoader = function (bool $withEval = false) use ($year, $which_half) {
            return function ($q) use ($withEval, $year, $which_half) {
                $q->select('users.id','users.name','users.icon_path','users.icon_bg', 'users.position_id')
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
        }
        $user = $this->active_user();
        $position_id = $user->position_id;
        $projects = $query
        ->when($position_id == 15, function ($q) use($user) {
            $q->whereHas('members', function ($q) use($user) {
                $q->where('users.id', $user->id);
            });
        })
        ->with([
            'director:id,name,icon_path,icon_bg',
            'manager' => $usersLoader(true),
            'members' => $usersLoader(true),
            'director',
            'contract'
        ])
        ->get();
        $projects = $projects->map(function (ProjectRecord $project) {
            $project->actual_statuses = $project->actual_statuses ?? [];
            $project->has_goals = $project->has_goals ?? false;
            $project->unit_id = $project->unit_id ?? 'JPY';
            $project->has_actual_func = $project->has_actual_func ?? false;
            $project->custom_unit_label = $project->custom_unit_label ?? null;
            return $project;
        });
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
                                        $q->with(['files', 'actions', 'reports']);
                                    }])
                                    ->get();
        $evalutaionRecord = EvaluationRecord::where('year', $year)
                                    ->where('which_half', $which_half)
                                    ->where('user_id', $user_id)->with('mentor')->first();
        $data = [
            'achievement_total' => $project_goals->sum('achievement_rate'),
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
                        ->when(!empty($params), function ($q) use ($params) {
                            $q->with([
                                'evaluation' => function ($q) use ($params) {
                                    $q->where('year', $params['year'])
                                    ->where('which_half', $params['which_half'])
                                    ->with('mentor', 'candidate');
                                },
                            ]);
                            $q->with(['outcome_goals' => function ($q) use ($params){
                                $q->where('year', $params['year'])
                                    ->where('which_half', $params['which_half'])
                                    ->with('steps');
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
            'is_new',
            'has_goals',
            'has_actual_func',
            'unit_id',
            'custom_unit_label',
        ])->toArray();

        $filteredParams['has_actual_func'] = array_key_exists('has_actual_func', $params) ? (bool)$params['has_actual_func'] : false;
        $filteredParams['has_goals'] = array_key_exists('has_goals', $params) ? (bool)$params['has_goals'] : false;
        $filteredParams['unit_id'] = $params['unit_id'] ?? 'JPY';
        $filteredParams['custom_unit_label'] = $params['custom_unit_label'] ?? null;
        $filteredParams['actual_statuses'] = $this->normalizeActualStatuses($params['actual_statuses'] ?? null);

        $project = ProjectRecord::updateOrCreate(['id' => $id], $filteredParams);
        $members = collect($params['members'])->pluck('id')->toArray();
        $manager = collect($params['manager'])->pluck('id')->toArray();
        $project->members()->sync($members);
        $project->manager()->syncWithPivotValues($manager, ['authority' => 1]);
        $tasks = $request->tasks ?? [];
        if(count($tasks)) {
            $this->create_generated_project_tasks($tasks, $project->id);
        }

        $raw = $request->input('contract_data');
        if (is_string($raw)) {
            $contract = json_decode($raw, true);
        } elseif (is_object($raw)) {
            $contract = json_decode(json_encode($raw), true);
        } else {
            $contract = $raw;
        }

        if (!is_array($contract)) {
            return response()->json($project);
        }

        $overall   = $contract['overall_risk'] ?? 'unknown';
        $findings  = $contract['findings'] ?? [];
        $findCount = is_array($findings) ? count($findings) : 0;

        $responseHash = hash('sha256', json_encode($contract, JSON_UNESCAPED_UNICODE));

        $projectContract = ProjectContract::updateOrCreate(
            [
                'project_record_id' => $project->id,
                'review_type'       => 'quick',    // ここをキーにして「1案件1件」にする
            ],
            [
                'overall_risk'      => $overall,
                'findings_count'    => $findCount,
                'result_json'       => $contract,
                'response_hash'     => $responseHash,
                'file_path'         => $request->input('contract_file_path'),
                'role'              => $request->input('contract_role'),
                'contract_type'     => $request->input('contract_type'),
            ]
        );
        
        return response()->json($project);
    }

    public function show_contract(ProjectRecord $project)
    {
        $this->ensureProjectAccess($project);

        $contract = $this->resolveProjectContract($project);
        if (!$contract) {
            return response()->json(null, 404);
        }

        $filePath = $contract->file_path;
        $disk = Storage::disk('local');
        $fileExists = $filePath && $disk->exists($filePath);

        $payload = $contract->toArray();
        $payload['file_size'] = $fileExists ? $disk->size($filePath) : null;
        $payload['file_url'] = $fileExists ? route('projects.contract.preview', $project) : null;
        $payload['download_url'] = $fileExists ? route('projects.contract.download', $project) : null;

        return response()->json($payload);
    }

    public function preview_contract(ProjectRecord $project)
    {
        $this->ensureProjectAccess($project);

        $contract = $this->resolveProjectContract($project);
        abort_unless($contract, 404, '契約書が見つかりません。');

        $filePath = $contract->file_path;
        $disk = Storage::disk('local');
        abort_if(!$filePath || !$disk->exists($filePath), 404, '契約書ファイルが見つかりません。');

        $absolutePath = $disk->path($filePath);
        $mime = $disk->mimeType($filePath) ?? 'application/pdf';
        $filename = basename($filePath);

        return response()->file($absolutePath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function download_contract(ProjectRecord $project)
    {
        $this->ensureProjectAccess($project);

        $contract = $this->resolveProjectContract($project);
        abort_unless($contract, 404, '契約書が見つかりません。');

        $filePath = $contract->file_path;
        $disk = Storage::disk('local');
        abort_if(!$filePath || !$disk->exists($filePath), 404, '契約書ファイルが見つかりません。');

        $filename = basename($filePath);

        return $disk->download($filePath, $filename);
    }

    public function store_contract(Request $request, ProjectRecord $project)
    {
        $this->ensureProjectAccess($project);

        $data = $request->validate([
            'contract_data'   => ['required', 'array'],
            'file_path'       => ['required', 'string'],
            'contract_role'   => ['required', 'string'],
            'contract_type'   => ['required', 'string'],
        ]);

        $disk = Storage::disk('local');
        abort_if(!$disk->exists($data['file_path']), 404, '契約書ファイルが見つかりません。');

        $contractPayload = $data['contract_data'];
        $overall   = $contractPayload['overall_risk'] ?? 'unknown';
        $findings  = $contractPayload['findings'] ?? [];
        $findCount = is_array($findings) ? count($findings) : 0;
        $responseHash = hash('sha256', json_encode($contractPayload, JSON_UNESCAPED_UNICODE));

        $current = $this->resolveProjectContract($project);
        $version = $current ? ($current->version + 1) : 1;

        if ($current && $current->file_path !== $data['file_path'] && $disk->exists($current->file_path)) {
            $disk->delete($current->file_path);
        }

        $payload = [
            'project_record_id' => $project->id,
            'review_type'       => 'quick',
            'overall_risk'      => $overall,
            'findings_count'    => $findCount,
            'result_json'       => $contractPayload,
            'response_hash'     => $responseHash,
            'file_path'         => $data['file_path'],
            'role'              => $data['contract_role'],
            'contract_type'     => $data['contract_type'],
            'version'           => $version,
            'active'            => true,
        ];

        $record = ProjectContract::updateOrCreate(
            ['project_record_id' => $project->id],
            $payload
        );

        return response()->json($record->fresh());
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
                                        ->with(['candidate', 'checklist', 'user'])->first();

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

    public function get_yearly_plan(Request $request)
    {
        $request->validate([
            'project_id' => 'required',
            'year'       => 'required|integer',
        ]);

        $project = ProjectRecord::findOrFail($request->project_id);

        $year         = (int) $request->year;
        $month        = $request->month; // not strictly needed anymore
        $project_name = $project->name;
        $currentYear  = (int) now()->year;

        // pick file by your existing rule
        $filePathYear = ($year > $currentYear && ($month == 1 || $month == 2)) ? $currentYear : $year;
        $file_path = storage_path("app/yearly_plan/{$filePathYear}.xlsx");
        if (!file_exists($file_path)) {
            return response()->json([]);
        }

        $sheet = Excel::toCollection(new YearlyPlanImport, $file_path)[0];

        // 1) header rows
        $sheet->shift();                 // title row
        $monthHeaders = $sheet->shift(); // the row containing "YYYY年M月" labels
        $subHeaders   = $sheet->shift(); // the row containing "合計 売上高" etc.

        $monthHeaders = $monthHeaders->toArray();
        $subHeaders   = $subHeaders->toArray();

        // 2) Build: month number -> array of column indexes for that month
      
        $fyStart = 3;

        $targetLabels = [];  
        for ($i = 0; $i < 12; $i++) {
            $d = Carbon::create($year, $fyStart, 1)->addMonthsNoOverflow($i);
            $label = sprintf('%d年%d月', $d->year, $d->month);
            $targetLabels[$label] = $d->month;  
        }
        $monthIndexMap = [];
        foreach (array_values($targetLabels) as $m) {
            $monthIndexMap[$m] = []; // 3..12 then 1..2
        }
        $currentMonthKey = null;
        foreach ($monthHeaders as $absIdx => $h) {
            if (is_string($h)) {
                $label = preg_replace('/\s+/', '', $h); // tolerate spaces like "2025年 3月"
                if (isset($targetLabels[$label])) {
                    // start a new month block
                    $currentMonthKey = $targetLabels[$label]; // 3..12,1,2
                    $monthIndexMap[$currentMonthKey][] = $absIdx;
                    continue;
                }
            }
            // Null/blank cells under the same month continue the block
            if ($currentMonthKey !== null && ($h === null || $h === '')) {
                $monthIndexMap[$currentMonthKey][] = $absIdx;
                continue;
            }
            // Any other non-null / non-matching header ends the current block
            $currentMonthKey = null;
        }
        // 3) Find relative positions of required sub-headers within a month block
        $labelToKey = [
            '合計 売上高'          => 'sales_1',
            '合計 内部売上高合計'  => 'sales_2',
            '合計 給料手当'        => 'exp_1',
            '合計 外注費'          => 'exp_2',
            '合計 販管費その他'    => 'exp_3',
            '合計 間接費配賦'      => 'exp_4',
            '合計 内部発注合計'    => 'exp_5',
            '業績連動型賞与引当金' => 'exp_6',
            '利益'                => 'profit',
            '利益率'              => 'profit_rate',
        ];
        $colIndexByMonth = [];

        foreach (array_keys($monthIndexMap) as $m) {   // <- fiscal order
            $colIndexByMonth[$m] = [];
            $indexes = $monthIndexMap[$m] ?? [];
            if (!$indexes) continue;

            // relative idx -> header label (for that month's block)
            $relativeHeaders = [];
            foreach ($indexes as $rel => $abs) {
                $relativeHeaders[$rel] = $subHeaders[$abs] ?? null;
            }

            foreach ($labelToKey as $label => $alias) {
                $relPos = array_search($label, $relativeHeaders, true);
                if ($relPos !== false) {
                    $colIndexByMonth[$m][$alias] = $indexes[$relPos]; // absolute column index
                }
            }
        }
        // 4) Filter rows for this project
        $rows = $sheet->filter(fn($row) => ($row[1] ?? null) === $project_name);
        $fiscalOrder = [3,4,5,6,7,8,9,10,11,12,1,2];
        $out = [];
        foreach ($fiscalOrder as $m) {
            $cols = $colIndexByMonth[$m] ?? [];
            // initialize monthly totals
            $sales = 0.0;
            $expense = 0.0;
            $profit = 0.0;
            $profit_rate = null;

            if (!empty($cols)) {
                foreach ($rows as $row) {
                    
                    $get = fn($alias) => isset($cols[$alias]) ? (float) ($row[$cols[$alias]] ?? 0) : 0.0;
                    
                    // sales: two columns combined
                    $sales    += round($get('sales_1') + $get('sales_2'), 0, PHP_ROUND_HALF_UP);
                    // expenses: sum of six
                    $expense  += round(
                        $get('exp_1') + $get('exp_2') + $get('exp_3') +
                        $get('exp_4') + $get('exp_5') + $get('exp_6'),
                        0,
                        PHP_ROUND_HALF_UP
                    );

                    // profit, profit_rate if present
                    if (isset($cols['profit'])) {
                        $profit += round((float) ($row[$cols['profit']] ?? 0), 0, PHP_ROUND_HALF_UP);
                    }
                    if (isset($cols['profit']) && $sales > 0) {
                        // if multiple rows exist, last one wins; or average—pick your rule
                        $profit_rate = round((float) ($row[$cols['profit']] / $sales * 100 ?? 0), 2, PHP_ROUND_HALF_UP);
                    }
                }
            }
            
            $out[$m] = [
                'sales'       => (int) $sales,
                'expense'     => (int) $expense,
                'profit'      => (int) $profit,
                'profit_rate' => $profit_rate, // could be null if not found
            ];
            
        }

        return response()->json($out);
    }

    public function get_profit(Request $request){
        $request->validate([
            'project_id' => 'required',
            'year' => 'required',
        ]);
        $project = ProjectRecord::findOrFail($request->project_id);
        $year = (int) $request->year;
        $project_name = $project->name;
        $startInstance = Carbon::createFromDate($year, 3, 1);
        $endInstance = $startInstance->copy()->addMonthsNoOverflow(11);
        $offset = 0; $limit = 500;
        $startDate = $startInstance->copy()->startOfMonth()->toDateString();
        $endDate = $endInstance->copy()->endOfMonth()->toDateString();
        $out = [];
        
        $query = "部門 = \"{$project_name}\" and 日付 >= \"{$startDate}\" and 日付 <= \"{$endDate}\"";
        $fields = ["売上高合計", "内部売上高合計", "販売管理費合計", "間接費配賦", "利益", "利益率", '部門', '日付', '業績連動賞与積立金'];
        
        $recs = $this->api->getRecords(1068, $query . " limit {$limit} offset {$offset}", $fields);

        foreach($recs as $r) {
            $date = (string)($r['日付']['value'] ?? '');
            if ($date === '') continue;
            $month = $date ? (int)date('n', strtotime($date)) : null;
            $totalSales = round((float) $r['売上高合計']['value'] + (float) $r['内部売上高合計']['value'], 0, PHP_ROUND_HALF_UP);
            $totalExpense = round((float)  $r['販売管理費合計']['value'] + (float) $r['間接費配賦']['value'] + (float) $r['業績連動賞与積立金']['value'], 0, PHP_ROUND_HALF_UP);
            $totalProfit = $this->f($r['利益']['value']);
            $totalProfitRate = $this->f($r['利益率']['value']);
            $out[$month] = [
                'sales'          => $totalSales,
                'expense'        => $totalExpense,
                'profit'         => round($totalProfit),
                'profit_rate'   => $totalProfitRate
            ];
        }
        return response()->json($out);
    }
    private function f($v): ?float
    {
        if ($v === null || $v === '') return null;
        $f = (float) $v;
        return is_finite($f) ? $f : null;
    }
    public function get_settlement(Request $request){
        $request->validate([
            'project_id' => 'required',
            'year' => 'required',
            'start'=> 'sometimes|date_format:Y-m',
            'end'=> 'sometimes|date_format:Y-m',
        ]);
        $project = ProjectRecord::findOrFail($request->project_id);
        $year = (int) $request->year;
        $project_name = trim((string)($project->name ?? ''));

        $svc      = $this->client->svc;
        $sheet_id = config('services.google.spreadsheet_id');

        $needed_ranges = [];
        $sDate = $request->start ? Carbon::createFromFormat('Y-m', $request->start) : Carbon::createFromDate((int) $year, 3, 1);
        $eDate = $request->end ? Carbon::createFromFormat('Y-m', $request->end) : $sDate->copy()->addMonthsNoOverflow(11);

        for ($d = $sDate->copy(); $d->lessThanOrEqualTo($eDate); $d->addMonth()) {
            $needed_ranges[] = sprintf('%04d%02d', $d->year, $d->month);
        }

        $spreadsheet = $svc->spreadsheets->get($sheet_id);
        $sheets = $spreadsheet->getSheets();
        $existing = [];
        foreach ($sheets as $sheet) {
            $title = $sheet['properties']['title'];
            if (in_array($title, $needed_ranges, true)) {
                $existing[] = $title;
            }
        }
        if (empty($existing)) {
            return response()->json([]); 
        }

        $findRanges = array_map(fn($t) => "'{$t}'!B:B", $existing);
        $findResp   = $svc->spreadsheets_values->batchGet($sheet_id, ['ranges' => $findRanges]);

        $canon = function ($s) {
            $s = preg_replace('/\s+/u', ' ', trim((string)$s));
            return mb_strtolower($s);
        };
        $needle = $canon($project_name);

        $hitRowsByTab = []; 
        foreach ($findResp->getValueRanges() as $i => $vr) {
            $title = $existing[$i];
            $values = $vr->getValues() ?? []; 
            $hits = [];
            foreach ($values as $rIdx => $row) {
                $colB = $row[0] ?? ''; 
                if ($canon($colB) === $needle) {
                    $hits[] = $rIdx + 1; 
                }
            }
            $hitRowsByTab[$title] = $hits;
        }

        $detailRanges = [];
        foreach ($hitRowsByTab as $title => $rows) {
            foreach ($rows as $rowNum) {
                $detailRanges[] = "'{$title}'!C{$rowNum}:G{$rowNum}";
            }
        }

        if (empty($detailRanges)) {
            // No matching project rows in any existing sheet.
            // Still return per-month zeros so frontend can calculate variance.

            $result = [];

            foreach ($existing as $t) {
                // $t is like "202504" → month = 4
                $monthKey = (int) substr($t, 4, 2);

                $result[$monthKey] = [
                    'row'          => null,
                    'sales'        => 0,
                    'expense'      => 0,
                    'overhead'     => 0,
                    'profit'       => 0,
                    'profit_rate'  => null,
                ];
            }

            return response()->json($result);
        }

        $detailResp = $svc->spreadsheets_values->batchGet($sheet_id, ['ranges' => $detailRanges]);

        $result = [];
        foreach ($existing as $t) {
            $monthKey = (int) substr($t, 4, 2);
            $result[$monthKey] = [];
        }

        $valueRanges = $detailResp->getValueRanges() ?? [];
        $k = 0;
        function toNumberOrNull($val)
        {
            if ($val === null || $val === '') {
                return null;
            }

            // remove thousands separators
            $normalized = str_replace(',', '', $val);

            // you can use int if you're dealing with whole yen
            return is_numeric($normalized) ? (float) $normalized : null;
        }

        foreach ($hitRowsByTab as $title => $rows) {
            foreach ($rows as $rowNum) {
                $vals = $valueRanges[$k]->getValues()[0] ?? [];

                $month = (int) substr($title, 4, 2);

                $baseExpense = isset($vals[1]) ? toNumberOrNull($vals[1]) : null; // D
                $overhead    = isset($vals[2]) ? toNumberOrNull($vals[2]) : null; // E

                $totalExpense = null;
                if ($baseExpense !== null || $overhead !== null) {
                    $totalExpense = ($baseExpense ?? 0) + ($overhead ?? 0);
                }

                $result[$month] = [
                    'row'          => $rowNum,
                    'sales'        => isset($vals[0]) ? toNumberOrNull($vals[0]) : null, // C
                    'expense'      => $totalExpense,    // D + E
                    'overhead'     => $overhead,        // E
                    'profit'       => isset($vals[3]) ? toNumberOrNull($vals[3]) : null, // F
                    'profit_rate'  => $vals[4] ?? null, // G (probably already % or plain)
                ];

                $k++;
            }
            
        }
        return response()->json($result);
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
            "query" => "日付 >= \"{$startDate}\" and 日付 <= \"{$endDate}\" limit 500 offset {$offset}",
            "fields" => ["売上高合計", "内部売上高合計", "販売管理費合計", "間接費配賦", "利益", "利益率", '部門', '日付', '業績連動賞与積立金'],
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
        
        $params = [
            'ranges' => $ranges,
            'valueRenderOption' => 'UNFORMATTED_VALUE'
        ];

        $response = $service->spreadsheets_values->batchGet($sheet_id, $params);
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
        $project_names = $projects->pluck('name', 'id')->toArray();   

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
        if((int)$totalCount > 500){
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
        $default_settlement_data = $default_data + ['has_data' => false];

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
        $v = [];
        $defaultPeriodTotals = [
            'yearly_plan' => $default_data,
            'profit' => $default_data,
            'settlement' => $default_settlement_data,
        ];
        $periodTotals = [];
        $periodIterator = $startInstance->copy();
        while ($periodIterator->lessThanOrEqualTo($endInstance)) {
            $periodKey = $periodIterator->format('Y-m');
            $periodTotals[$periodKey] = array_merge($defaultPeriodTotals, [
                'year' => (int) $periodIterator->year,
                'month' => (int) $periodIterator->month,
            ]);
            $periodIterator->addMonth();
        }
        $ensurePeriodKey = function (string $key) use (&$periodTotals, $defaultPeriodTotals) {
            if (!array_key_exists($key, $periodTotals)) {
                $periodTotals[$key] = array_merge($defaultPeriodTotals, [
                    'year' => (int) substr($key, 0, 4),
                    'month' => (int) substr($key, 5, 2),
                ]);
            }
        };
        $accumulatePeriodTotals = function (string $key, string $scenario, array $values) use (&$periodTotals, $ensurePeriodKey) {
            $ensurePeriodKey($key);
            $sales = (float) ($values['sales'] ?? 0);
            $expense = (float) ($values['expense'] ?? 0);
            $profit = array_key_exists('profit', $values) ? (float) $values['profit'] : ($sales - $expense);
            $periodTotals[$key][$scenario]['sales'] = ($periodTotals[$key][$scenario]['sales'] ?? 0) + $sales;
            $periodTotals[$key][$scenario]['expense'] = ($periodTotals[$key][$scenario]['expense'] ?? 0) + $expense;
            $periodTotals[$key][$scenario]['profit'] = ($periodTotals[$key][$scenario]['profit'] ?? 0) + $profit;
            $periodTotals[$key][$scenario]['profit_rate'] = 0;
            if (array_key_exists('has_data', $values)) {
                $periodTotals[$key][$scenario]['has_data'] = ($periodTotals[$key][$scenario]['has_data'] ?? false) || !empty($values['has_data']);
            }
        };
        //process each data for each project
        foreach($project_names as $id => $project_name){
            $stDate = $startInstance->copy();
            $etDate = $endInstance->copy();
            $sumData[$project_name] = $defaultSumData;
            // foreach($months_array as $month){
            while ($stDate->lessThanOrEqualTo($etDate)) {
                $month = $stDate->month;
                $year = $stDate->year;
                $periodKey = sprintf('%04d-%02d', $year, $month);
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
                    $accumulatePeriodTotals($periodKey, 'yearly_plan', $planData);
                }
                else{
                    $plan_res_data[$project_name][$month]['yearly_plan']  = $default_data;
                    $accumulatePeriodTotals($periodKey, 'yearly_plan', $default_data);
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
                    $totalExpense = round((float)  $profitData['販売管理費合計'] + (float) $profitData['間接費配賦'] + (float) $profitData['業績連動賞与積立金'], 0, PHP_ROUND_HALF_UP);
                    $profitData = [
                        "sales" => $totalSales,
                        "expense" => $totalExpense,
                        "profit" => round((float)(float) $profitData['利益'], 0, PHP_ROUND_HALF_UP),
                        "profit_rate" => (float) $profitData['利益率'],
                    ];
                    $plan_res_data[$project_name][$month]['profit'] = $profitData;
                    $sumData[$project_name]['profit']['sales'] = ($sumData[$project_name]['profit']['sales'] ?? 0) + $totalSales;
                    $sumData[$project_name]['profit']['expense'] = ($sumData[$project_name]['profit']['expense'] ?? 0) + $totalExpense;
                    $sumData[$project_name]['profit']['profit'] = ($sumData[$project_name]['profit']['profit'] ?? 0) + $profitData['profit'];
                    $summarizeData['profit']['sales'] = ($summarizeData['profit']['sales'] ?? 0) + $totalSales;
                    $summarizeData['profit']['expense'] = ($summarizeData['profit']['expense'] ?? 0) + $totalExpense;
                    $summarizeData['profit']['profit'] = ($summarizeData['profit']['profit'] ?? 0) + $profitData['profit'];
                    $accumulatePeriodTotals($periodKey, 'profit', $profitData);
                }
                else{
                    $plan_res_data[$project_name][$month]['profit'] = $default_data;
                    $accumulatePeriodTotals($periodKey, 'profit', $default_data);
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
                        $settlement_sales_val = $settlementOfProject[$settlement_sales_index] ?? 0;
                        $settlement_expense_val = $settlementOfProject[$settlement_expense_index] ?? 0;
                        $settlement_additional_expense_val = $settlementOfProject[$settlement_additional_expense_index] ?? 0;
                        $settlement_profit_val = $settlementOfProject[$settlement_profit_index] ?? 0;
                        $settlement_profit_rate_val = $settlementOfProject[$settlement_profit_rate_index] ?? 0; 
                        $totalSales = round((float) str_replace(',', '', $settlement_sales_val), 0, PHP_ROUND_HALF_UP);
                        $totalExpense = (float) str_replace(',', '', $settlement_expense_val) + (float) str_replace(',', '', $settlement_additional_expense_val);
                        $plan_res_data[$project_name][$month]['settlement']= [
                            'sales' => $totalSales,
                            'expense' => $totalExpense ?? 0,
                            'profit' => (float)(float) str_replace(',', '', $settlement_profit_val),
                            'profit_rate' => (float) str_replace('%', '', $settlement_profit_rate_val),
                            'has_data' => true,
                        ];
                        $sumData[$project_name]['settlement']['sales'] = ($sumData[$project_name]['settlement']['sales'] ?? 0) + $totalSales;
                        $sumData[$project_name]['settlement']['expense'] = ($sumData[$project_name]['settlement']['expense'] ?? 0) + $totalExpense;
                        $sumData[$project_name]['settlement']['profit'] = ($sumData[$project_name]['settlement']['profit'] ?? 0) + round((float)(float) str_replace(',', '', $settlement_profit_val), 0, PHP_ROUND_HALF_UP);
                        // $summarizeData['settlement']['sales'] = ($summarizeData['settlement']['sales'] ?? 0) + $totalSales;
                        // $summarizeData['settlement']['expense'] = ($summarizeData['settlement']['expense'] ?? 0) + $totalExpense;
                        $accumulatePeriodTotals($periodKey, 'settlement', $plan_res_data[$project_name][$month]['settlement']);
                 
                    }else{
                        $plan_res_data[$project_name][$month]['settlement'] = $default_settlement_data;
                        $accumulatePeriodTotals($periodKey, 'settlement', $default_settlement_data);
                    }                    
                    

                }else{
                    $plan_res_data[$project_name][$month]['settlement'] = $default_settlement_data;
                    $accumulatePeriodTotals($periodKey, 'settlement', $default_settlement_data);
                }
                $sumData[$project_name]['settlement']['id'] = $id; 
                $v[$project_name] = [
                    'sales'   => VarianceService::achToVar(VarianceService::pct($sumData[$project_name]['settlement']['sales']??null,   $sumData[$project_name]['profit']['sales']??null)),
                    'expenses' => VarianceService::achToVar(VarianceService::pct($sumData[$project_name]['settlement']['expense']??null, $sumData[$project_name]['profit']['expense']??null)),
                    'profit'  => VarianceService::achToVar(VarianceService::pct($sumData[$project_name]['settlement']['profit']??null,  $sumData[$project_name]['profit']['profit']??null)),
                ];
                $stDate->addMonth();
            }
        }
        foreach($periodTotals as $key => &$periodTotal){
            $periodTotal['settlement']['expense'] = (int) round($periodTotal['settlement']['expense'] ?? 0, 0, PHP_ROUND_HALF_UP);
            $periodTotal['settlement']['profit'] = (int) round($periodTotal['settlement']['profit'] ?? 0, 0, PHP_ROUND_HALF_UP);
            $summarizeData['settlement']['sales'] = ($summarizeData['settlement']['sales'] ?? 0) + $periodTotal['settlement']['sales'];
            $summarizeData['settlement']['expense'] = ($summarizeData['settlement']['expense'] ?? 0) + $periodTotal['settlement']['expense'];
        }
        $final_data = [
            'plan_res_data' => $plan_res_data,
            'sumData' => $sumData,
            'summarizeData' => $summarizeData,
            'periodTotals' => $periodTotals,
        ];
        return response()->json( $final_data);


    }
    public function get_total_finance_badge(Request $request)
    {
        $data = $request->validate([
            'interval'            => ['required','array'],
            'interval.startYear'  => ['required','integer'],
            'interval.startMonth' => ['required','integer','between:1,12'],
            'interval.endYear'    => ['required','integer'],
            'interval.endMonth'   => ['required','integer','between:1,12'],
        ]);

        $start = Carbon::createFromDate($data['interval']['startYear'], $data['interval']['startMonth'], 1)->startOfMonth();
        $end   = Carbon::createFromDate($data['interval']['endYear'],   $data['interval']['endMonth'],   1)->startOfMonth();

        if ($end->lt($start)) {
            return response()->json(['error' => true, 'message' => '開始日付は終了日付より前で設定してください。'], 422);
        }

        // Inclusive month count
        $months = $start->diffInMonths($end) + 1;
        if ($months > 12) {
            return response()->json(['error' => true, 'message' => '最大12ヶ月まで選択できます。'], 422);
        }

        $active = $this->active_user();
        $projects = ProjectRecord::when(
            $active->position_id < 6 || $active->id === 610,
            fn($q) => $q,
            fn($q) => $q->whereHas('manager', fn($sq) => $sq->where('users.id', $active->id))
        )->pluck('name')->all();
        function escapeKintoneString(string $s): string {
            // Kintone strings are double-quoted. Escape backslash and double-quote.
            return str_replace(['\\', '"'], ['\\\\', '\"'], $s);
        }

        /**
         * Build the Kintone query string for a chunk of project names and a date range [start, endNext).
         */
        function buildProjectsQuery(array $names, $start, $end): string {
            $escaped = array_map(fn($n) => '"'.escapeKintoneString($n).'"', $names);
            $ymdStart = $start->copy()->startOfMonth()->format('Y-m-d');
            $ymdEndNext = $end->copy()->startOfMonth()->addMonth()->format('Y-m-d'); // exclusive upper bound
            $projectsExpr = '部門 in ('.implode(',', $escaped).')';
            $dateExpr = sprintf('日付 >= "%s" and 日付 < "%s"', $ymdStart, $ymdEndNext);
            return $projectsExpr.' and '.$dateExpr;
        }    
        // If you must pass project names to an external API, escape them properly.
        // Better: filter by 部門 after fetching instead of hand-built IN (...) strings.
        $projectSet = array_flip($projects); // fast membership test

        // Fetch profit data paginated and aggregate once: key = 部門|YYYY-MM
        $profitSums = []; // [部門][yyyy-mm] = ['sales'=>..., 'expense'=>..., 'profit'=>...]
        $offset = 0;
        $totalCount = null;
        $chunks = array_chunk($projects, 100);
        foreach ($chunks as $namesChunk) {
            $query = buildProjectsQuery($namesChunk, $start, $end);

            $offset = 0;
            do {
                // Your collector should accept a query string. If it currently takes just names,
                // move the string-building into it and pass the raw $namesChunk instead.
                $batch = $this->profitCollector($start, $end, (string)$offset, $query);

                $totalCount = (int)($batch['totalCount'] ?? 0);
                $records = $this->kintone_record_cleaner($batch['records'] ?? []);

                foreach ($records as $r) {
                    $dept = $r['部門'] ?? null;
                    if ($dept === null) continue;

                    $ym = Carbon::parse($r['日付'])->startOfMonth()->format('Y-m');
                    $sales   = (float)($r['売上高合計'] ?? 0) + (float)($r['内部売上高合計'] ?? 0);
                    $expense = (float)($r['販売管理費合計'] ?? 0) + (float)($r['間接費配賦'] ?? 0);
                    $profit  = (float)($r['利益'] ?? 0);

                    $node = &$profitSums[$dept][$ym];
                    if (!isset($node)) $node = ['sales'=>0,'expense'=>0,'profit'=>0];
                    $node['sales']   += round($sales,   0, PHP_ROUND_HALF_UP);
                    $node['expense'] += round($expense, 0, PHP_ROUND_HALF_UP);
                    $node['profit']  += round($profit,  0, PHP_ROUND_HALF_UP);
                    unset($node);
                }

                $offset += 500;
                if ($offset > 10000) break; // your safety valve
            } while ($offset < $totalCount);
        }

        // Settlements: pre-aggregate once per month sheet
        $settlementSums = []; // [部門][yyyy-mm] = ['sales'=>..., 'expense'=>..., 'profit'=>...]
        $batchSettlement = $this->settlementCollector($start, $end);

        foreach ($batchSettlement as $tab => $rows) {
            if (empty($rows) || count($rows) < 3) continue;
            // headers at row index 1; data from index 2
            $headers = $rows[1];
            $col = [
                'name'     => array_search('プロジェクト名', $headers) ?: 1, // fallback to col 1 like your current code
                'sales'    => array_search('収入', $headers),
                'expense'  => array_search('支出', $headers),
                'indirect' => array_search('間接費配賦', $headers),
                'profit'   => array_search('利益', $headers),
            ];
            $ym = substr($tab, 0, 4).'-'.substr($tab, 4, 2);

            foreach (array_slice($rows, 2) as $row) {
                $dept = $row[$col['name']] ?? null;
                if ($dept === null || !isset($projectSet[$dept])) continue;

                $sales   = (float)str_replace(',', '', $row[$col['sales']]   ?? 0);
                $expense = (float)str_replace(',', '', $row[$col['expense']] ?? 0);
                $ind     = (float)str_replace(',', '', $row[$col['indirect']] ?? 0);
                $profit  = (float)str_replace(',', '', $row[$col['profit']]  ?? 0);

                $node = &$settlementSums[$dept][$ym];
                if (!isset($node)) $node = ['sales'=>0,'expense'=>0,'profit'=>0];
                $node['sales']   += round($sales,           0, PHP_ROUND_HALF_UP);
                $node['expense'] += round($expense + $ind,  0, PHP_ROUND_HALF_UP);
                $node['profit']  += round($profit,          0, PHP_ROUND_HALF_UP);
                unset($node);
            }
        }

        // Accumulate across the selected months per project
        $cursor = $start->copy();
        $projectTotals = []; // [部門] => ['profit'=>..., 'settlement'=>...]

        while ($cursor->lte($end)) {
            $ym = $cursor->format('Y-m');
            foreach ($projects as $dept) {
                $p = $profitSums[$dept][$ym]      ?? ['sales'=>0,'expense'=>0,'profit'=>0];
                $s = $settlementSums[$dept][$ym]  ?? ['sales'=>0,'expense'=>0,'profit'=>0];

                $pt = &$projectTotals[$dept]['profit'];
                $st = &$projectTotals[$dept]['settlement'];
                if (!isset($pt)) $pt = ['sales'=>0,'expense'=>0,'profit'=>0];
                if (!isset($st)) $st = ['sales'=>0,'expense'=>0,'profit'=>0];

                $pt['sales']   += $p['sales'];    $st['sales']   += $s['sales'];
                $pt['expense'] += $p['expense'];  $st['expense'] += $s['expense'];
                $pt['profit']  += $p['profit'];   $st['profit']  += $s['profit'];

                unset($pt, $st);
            }
            $cursor->addMonthNoOverflow();
        }

        // Final variance
        $out = [];
        foreach ($projects as $dept) {
            $ps = $projectTotals[$dept]['profit']     ?? ['sales'=>0,'expense'=>0,'profit'=>0];
            $ss = $projectTotals[$dept]['settlement'] ?? ['sales'=>0,'expense'=>0,'profit'=>0];
    
            $out[$dept] = [
                'sales'    => VarianceService::achToVar(VarianceService::pct($ss['sales'],   $ps['sales'])),
                'expense' => VarianceService::achToVar(VarianceService::pct($ss['expense'], $ps['expense'])),
                'profit'   => VarianceService::achToVar(VarianceService::pct($ss['profit'],  $ps['profit'])),
            ];
        }

        return response()->json($out);
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
    public function mentionable_users(Request $request)
    {
        $user = $this->active_user();

        $request->validate([
            'projectId' => 'required',
        ]);

        $projects = ProjectRecord::where('id', $request->projectId)
            ->with('manager:id,name,icon_path,icon_bg')
            ->get();

        $managers = $projects->pluck('manager')->flatten(1)->unique('id')->values();

        $directors = User::where(function ($q) {
                $q->where(function ($q) {
                    $q->where('position_id', '<', 6)
                    ->where('retire', 0);
                })
                ->orWhere('id', 610);
            })
            ->where('id', '!=', $user->id) 
            ->select('id', 'name', 'icon_path', 'icon_bg')
            ->get();


        $users = $managers
            ->concat($directors)
            ->reject(fn ($u) => $u->id === $user->id)
            ->unique('id')
            ->values();

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
            'reply_id'            => ['integer', 'nullable'],
            'period'              => ['required', 'date_format:Y-m'],
        ]);

        DB::transaction(function () use (&$comment, $data, $user) {
            $comment = ProjectFinanceComment::create([
                'project_record_id' => $data['project_record_id'],
                'user_id'           => $user->id,
                'comment'           => $data['comment'],
                'type'              => $data['type'] ?? null,
                'reply_id'          => $data['reply_id'] ?? null,
                'period'            => $data['period'],
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
            // $url .= "?period={$data['period']}";

            SendProjectEmail::dispatchSync($emails, new ProjectMention($project, $emailContent, $blocked, $url, auth()->user()));
            
        }
        // load author for UI if you want
        $comment->load(['author:id,name,icon_path,icon_bg', 'checkedUsers', 'reply']);

        return response()->json($comment, 201);
    }

    public function get_project_finance_comments(Request $req) {
        $data = $req->validate([
            'project_record_id' => ['required','integer','exists:project_records,id'],
            'period'            => ['required','date_format:Y-m'],
        ]);

        $comment = ProjectFinanceComment::where('project_record_id', $data['project_record_id'])
                ->where('period', $data['period'])
                ->with(['author:id,name,icon_path,icon_bg', 'checkedUsers', 'reply'])
                ->get();
        
        return response()->json($comment);
    }
    public function monthlyCount(Request $req, ProjectRecord $project)
    {
        $data = $req->validate([
            'period_start' => 'string|date_format:Y-m',
            'period_end'   => 'string|date_format:Y-m',
        ]);
        $count = ProjectFinanceComment::select('period', DB::raw('COUNT(*) as comment_count'))
                ->where('project_record_id', $project->id)
                ->whereBetween('period', [$data['period_start'], $data['period_end']])
                ->whereNull('deleted_at')
                ->groupBy('period')
                ->pluck('comment_count', 'period');

        // always return a stable key
        return response()->json(
            $count,
        );
    }
    public function get_comment_count_from_total(Request $req) {
        $data = $req->validate([
            'projectIds'   => ['sometimes', 'array'],
            'projectIds.*' => ['integer'],
            'period' => ['nullable', 'date_format:Y-m'],
        ]);
        $ids = collect($data['projectIds'] ?? [])
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values();
        if ($ids->isEmpty()) {
            return response()->json((object)[]);
        }
         $countsByName = DB::table('project_finance_comments as c')
            ->join('project_records as pr', 'pr.id', '=', 'c.project_record_id')
            ->whereIn('c.project_record_id', $ids)
            ->whereNull('c.deleted_at')
            ->when(isset($data['period']), function ($query) use ($data) {
                $query->where('c.period', $data['period']);
            })
            ->groupBy('pr.name')
            ->pluck(DB::raw('COUNT(*) as comment_count'), 'pr.name'); // ["Project Name" => count]

        return response()->json($countsByName);

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
                return response()->json([
                    'total_unread' => 0,
                    'projects'     => [],
                ]);
            }
        }

        $q = DB::table('project_finance_comments as c')
            ->select(
                'c.project_record_id',
                'c.period',
                DB::raw('COUNT(*) as unread_count')
            )
            // Prefer period-specific read rows
            ->leftJoin('project_finance_last_reads as lrp', function ($j) use ($userId) {
                $j->on('lrp.project_record_id', '=', 'c.project_record_id')
                  ->on('lrp.period', '=', 'c.period')
                  ->where('lrp.user_id', '=', $userId);
            })
            // Fallback to legacy null-period rows to avoid badge spikes
            ->leftJoin('project_finance_last_reads as lrn', function ($j) use ($userId) {
                $j->on('lrn.project_record_id', '=', 'c.project_record_id')
                  ->whereNull('lrn.period')
                  ->where('lrn.user_id', '=', $userId);
            })
            ->whereIn('c.project_record_id', $projectIds)
            ->whereNull('c.deleted_at')       // if SoftDeletes
            ->where('c.user_id', '!=', $userId)
            ->where(function ($w) {
                $w->whereNull(DB::raw('COALESCE(lrp.last_read_at, lrn.last_read_at)'))
                  ->orWhereColumn('c.created_at', '>', DB::raw('COALESCE(lrp.last_read_at, lrn.last_read_at)'));
            })
            ->groupBy('c.project_record_id', 'c.period');
            // no select('*'), or ONLY_FULL_GROUP_BY will yell again

        $rows = $q->get();


        $totalUnread = 0;
        $projects = [];

        foreach ($rows as $r) {
            $projectId = (int) $r->project_record_id;
            $period    = $r->period;
            $count     = (int) $r->unread_count;

            $totalUnread += $count;

            if (!isset($projects[$projectId])) {
                $projects[$projectId] = [
                    'project_id'    => $projectId,
                    'total_unread'  => 0,
                    'period_counts' => [],   // period => count
                ];
            }

            $projects[$projectId]['total_unread'] += $count;
            $projects[$projectId]['period_counts'][$period] = $count;
        }

        $data = [
            'total_unread' => $totalUnread,
            'projects'     => array_values($projects),
        ];

        return response()->json($data);

    }

    public function mark_finance_read(Request $request, ProjectRecord $project) {
        $data = $request->validate(['period' => ['required', 'date_format:Y-m']]);
        $user = $this->active_user();
        
        ProjectFinanceLastRead::updateOrCreate(
            ['project_record_id' => $project->id, 'user_id' => $user->id, 'period' => $data['period']],
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
    public function mark_finance_check(Request $request){
        $active_user = $this->active_user();
        $comment = ProjectFinanceComment::with('checkedUsers')->findOrFail($request->id);
        if ($comment->checkedUsers()->where('user_id', $active_user->id)->exists()) {
            $comment->checkedUsers()->detach($active_user->id);            
        } else {
            $comment->checkedUsers()->attach($active_user->id);            
        }

        $comment = $comment->fresh();
        $comment->load('checkedUsers');
        return response()->json($comment);
    }

    public function project_resource_comment(Request $req) {
        $user = $this->active_user();
        $data = $req->validate([
            'member_name'        => ['required','string','max:255'],
            'comment'            => ['required','string','max:20000'],
            'period'             => ['required', 'date_format:Y-m'],
        ]);

        DB::transaction(function () use (&$comment, $data, $user) {
            $comment = ProjectResourceComment::create([
                'member_name' => $data['member_name'],
                'user_id'     => $user->id,
                'comment'     => $data['comment'],
                'period'      => $data['period'],
            ]);

        });

        $comment->load(['author:id,name,icon_path,icon_bg']);

        return response()->json($comment, 201);
    }

    public function get_project_resource_comments(Request $req) {
        $data = $req->validate([
            'member_name' => ['required','string','max:255'],
            'period'      => ['required','date_format:Y-m'],
        ]);

        $comment = ProjectResourceComment::where('member_name', $data['member_name'])
            ->where('period', $data['period'])
            ->with(['author:id,name,icon_path,icon_bg'])
            ->get();

        return response()->json($comment);
    }

    public function resource_comment_update(Request $request){
        $request->validate([
            'id' => 'required',
            'comment' => 'required',
        ]); 
        $comment = ProjectResourceComment::findOrFail($request->id);
        $comment->update(['comment' => $request->comment]);
        return response(200);
    }

    public function resource_comment_delete(Request $request){
        $request->validate([
            'id' => 'required',
        ]); 
        $comment = ProjectResourceComment::findOrFail($request->id);
        $comment->delete();
        return response(200);
    }

    public function get_resource_comment_counts(Request $req) {
        $data = $req->validate([
            'member_names'   => ['required', 'array'],
            'member_names.*' => ['string'],
            'period'         => ['required', 'date_format:Y-m'],
        ]);

        $names = collect($data['member_names'])
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->map(fn ($v) => (string) $v)
            ->unique()
            ->values();

        if ($names->isEmpty()) {
            return response()->json((object)[]);
        }

        $counts = DB::table('project_resource_comments as c')
            ->whereIn('c.member_name', $names)
            ->whereNull('c.deleted_at')
            ->where('c.period', $data['period'])
            ->groupBy('c.member_name')
            ->pluck(DB::raw('COUNT(*) as comment_count'), 'c.member_name');

        return response()->json($counts);
    }

    // #Metrics

    public function project_metrics(Request $req)
    {
        $metrics = ProjectMetric::query()
            ->when($req->boolean('active'), fn($query) => $query->where('is_active', 1))
            ->with([
                'formula:id,project_metric_id,expression',
                'subMetrics:id,project_metric_id,expression,sort_order',
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $labelMap = $metrics->pluck('label_ja', 'id')->all();

        return $metrics->map(function (ProjectMetric $metric) use ($labelMap) {
            return [
                'id'               => $metric->id,
                'label_ja'         => $metric->label_ja,
                'kind'             => $metric->kind,
                'value_type'       => $metric->value_type,
                'line'             => $metric->line,
                'is_active'        => (bool) $metric->is_active,
                'sort_order'       => $metric->sort_order,
                'scenario_label_ja'=> $metric->scenario_label_ja,
                'expression'       => $this->denormalizeExpression($metric->formula?->expression, $labelMap),
                'expression_normalized' => $metric->formula?->expression,
                'sub_metrics'      => $metric->subMetrics
                    ->sortBy('sort_order')
                    ->map(fn($sub) => [
                        'id'         => $sub->id,
                        'expression' => $this->denormalizeExpression($sub->expression, $labelMap),
                        'expression_normalized' => $sub->expression,
                        'sort_order' => $sub->sort_order,
                    ])->values(),
            ];
        });
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
    public function project_cases(ProjectRecord $project, Request $req)
    {
        $user = $this->active_user();
        abort_unless($user, 401, '認証が必要です。');

        $isProjectMember = ProjectMember::where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->exists();
        $isDirector = (int) $project->director_id === (int) $user->id;
        $isExecutive = ($user->position_id && $user->position_id < 6) || $user->id == 608;

        abort_unless($isProjectMember || $isDirector || $isExecutive, 403, '閲覧権限がありません。');

        $data = $req->validate([
            'start' => ['nullable', 'date_format:Y-m-d'],
            'end'   => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start'],
            'state' => ['nullable', Rule::in(['draft', 'submitted'])],
        ]);

        $query = ProjectCase::query()
            ->where('project_record_id', $project->id)
            ->with(['reporter:id,name,icon_path,icon_bg']);

        if (!empty($data['start'])) {
            $query->whereDate('report_date', '>=', Carbon::parse($data['start'])->startOfMonth());
        }
        if (!empty($data['end'])) {
            $query->whereDate('report_date', '<=', Carbon::parse($data['end'])->startOfMonth());
        }
        if (!empty($data['state'])) {
            $query->where('state', $data['state']);
        }

        $cases = $query
            ->orderBy('report_date')
            ->orderBy('id')
            ->get()
            ->map(function (ProjectCase $case) {
                $label = $this->resolveCaseLabel($case);
                return [
                    'id'           => $case->id,
                    'project_id'   => $case->project_record_id,
                    'report_date'  => optional($case->report_date)->toDateString(),
                    'status'       => $label,
                    'kind'         => $case->kind,
                    'stage'        => $case->stage,
                    'delivery_status' => $case->delivery_status,
                    'probability'  => $case->probability,
                    'client_name'  => $case->client_name,
                    'case_count'   => $case->case_count,
                    'amount'       => $case->amount,
                    'notes'        => $case->notes,
                    'state'        => $case->state,
                    'submitted_at' => optional($case->submitted_at)?->toDateTimeString(),
                    'reporter'     => $case->reporter ? [
                        'id'        => $case->reporter->id,
                        'name'      => $case->reporter->name,
                        'icon_path' => $case->reporter->icon_path,
                        'icon_bg'   => $case->reporter->icon_bg,
                    ] : null,
                ];
            });

        return response()->json(['cases' => $cases]);
    }
    public function project_case_store(ProjectRecord $project, Request $req)
    {
        $user = $this->active_user();
        abort_unless($user, 401, '認証が必要です。');

        $isProjectMember = ProjectMember::where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->exists();
        $isDirector = ($user->position_id && $user->position_id < 6) || in_array($user->id, [608, 610]);

        abort_unless($isProjectMember || $isDirector, 403, 'このプロジェクトには報告権限がありません。');

        $data = $req->validate([
            'actual_status_label' => ['nullable', 'string', 'max:191'],
            'client_name' => ['nullable', 'string', 'max:191'],
            'case_count'  => ['nullable', 'integer', 'min:0'],
            'amount'      => ['required', 'integer', 'min:0'],
            'notes'       => ['nullable', 'string'],
            'report_date' => ['required', 'date_format:Y-m-d'],
            'state'       => ['required', Rule::in(['draft', 'submitted'])],
            'member_id'   => ['nullable', 'integer']
        ]);

        $kind = 'ACTUAL';
        $statusLabel = $data['actual_status_label'] ?: '実績';
        $stage = null;
        $delivery = null;
        $probability = null;
        $reportDate = Carbon::parse($data['report_date'])->startOfMonth();
        $user_id = $data['member_id'] ?? $user->id;
        $attributes = [
            'project_record_id' => $project->id,
            'user_id'           => $user_id,
            'kind'             => $kind,
            'stage'            => $stage,
            'delivery_status'  => $delivery,
            'status'           => $statusLabel,
            'probability'      => $probability,
            'client_name'      => $data['client_name'] ?? null,
            'case_count'        => $data['case_count'] ?? 0,
            'amount'            => $data['amount'],
            'notes'             => $data['notes'] ?? null,
            'report_date'       => $reportDate,
            'state'             => $data['state'],
            'submitted_at'      => $data['state'] === 'submitted' ? now() : null,
        ];
        
        $exists = ProjectCase::where('report_date', $reportDate)
                ->where('user_id', $user_id)
                ->where('project_record_id', $project->id)
                ->where('status', $statusLabel)
                ->exists();
        abort_if($exists, 403, '同一日付・同一区分の報告は1件のみ登録できます。');

        $case = ProjectCase::create($attributes);
        $case->load('reporter:id,name,icon_path,icon_bg');

        return response()->json([
            'case' => [
                'id'           => $case->id,
                'project_id'   => $case->project_record_id,
                'report_date'  => $case->report_date->toDateString(),
                'status'       => $statusLabel,
                'kind'         => $case->kind,
                'stage'        => $case->stage,
                'delivery_status' => $case->delivery_status,
                'probability'  => $case->probability,
                'case_count'   => $case->case_count,
                'amount'       => $case->amount,
                'notes'        => $case->notes,
                'state'        => $case->state,
                'submitted_at' => optional($case->submitted_at)?->toDateTimeString(),
                'reporter'     => $case->reporter ? [
                    'id'        => $case->reporter->id,
                    'name'      => $case->reporter->name,
                    'icon_path' => $case->reporter->icon_path,
                    'icon_bg'   => $case->reporter->icon_bg,
                ] : null,
            ],
        ], 201);
    }

    public function project_case_update(ProjectRecord $project, ProjectCase $case, Request $req)
    {
        $user = Auth::user();
        abort_unless($user, 401, '認証が必要です。');
        abort_unless($case->project_record_id === $project->id, 404, '案件が見つかりません。');

        $isProjectMember = ProjectMember::where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->exists();
        $isDirector = (int) $project->director_id === (int) $user->id;

        abort_unless($isProjectMember || $isDirector, 403, 'このプロジェクトには報告権限がありません。');

        $data = $req->validate([
            'actual_status_label' => ['nullable', 'string', 'max:191'],
            'client_name' => ['nullable', 'string', 'max:191'],
            'case_count'  => ['nullable', 'integer', 'min:0'],
            'amount'      => ['required', 'integer', 'min:0'],
            'notes'       => ['nullable', 'string'],
            'report_date' => ['required', 'date_format:Y-m-d'],
            'state'       => ['required', Rule::in(['draft', 'submitted'])],
            'member_id'   => ['nullable', 'integer'],
        ], [
            'amount.required' => '金額は必須です。',
        ]);

        $kind = 'ACTUAL';
        $statusLabel = $data['actual_status_label'] ?: '実績';
        $stage = null;
        $delivery = null;
        $probability = null;

        $reportDate = Carbon::parse($data['report_date'])->startOf('month');

        $case->update([
            'user_id'          => $data['member_id'] ?? $case->user_id,
            'kind'             => $kind,
            'stage'            => $stage,
            'delivery_status'  => $delivery,
            'status'           => $statusLabel,
            'probability'      => $probability,
            'client_name'      => $data['client_name'] ?? null,
            'case_count'       => $data['case_count'] ?? 0,
            'amount'           => $data['amount'],
            'notes'            => $data['notes'] ?? null,
            'report_date'      => $reportDate,
            'state'            => $data['state'],
            'submitted_at'     => $data['state'] === 'submitted' ? now() : null,
        ]);

        $case->load('reporter:id,name,icon_path,icon_bg');

        return response()->json([
            'case' => [
                'id'           => $case->id,
                'project_id'   => $case->project_record_id,
                'report_date'  => optional($case->report_date)?->toDateString(),
                'status'       => $statusLabel,
                'kind'         => $case->kind,
                'stage'        => $case->stage,
                'delivery_status' => $case->delivery_status,
                'probability'  => $case->probability,
                'case_count'   => $case->case_count,
                'amount'       => $case->amount,
                'notes'        => $case->notes,
                'state'        => $case->state,
                'submitted_at' => optional($case->submitted_at)?->toDateTimeString(),
                'reporter'     => $case->reporter ? [
                    'id'        => $case->reporter->id,
                    'name'      => $case->reporter->name,
                    'icon_path' => $case->reporter->icon_path,
                    'icon_bg'   => $case->reporter->icon_bg,
                ] : null,
            ],
        ]);
    }

    private function normalizeActualStatuses($input): array
    {
        $rows = is_array($input) ? $input : [];
        $clean = [];
        $order = 1;

        foreach ($rows as $row) {
            $statusId = $row['status_id'] ?? null;
            $customLabel = $row['custom_label'] ?? null;
            $label = $statusId && isset(self::SYSTEM_STATUS_LABELS[$statusId])
                ? self::SYSTEM_STATUS_LABELS[$statusId]
                : trim((string) $customLabel);

            if ($label === '') {
                continue;
            }

            $clean[] = [
                'status_id' => $statusId && isset(self::SYSTEM_STATUS_LABELS[$statusId]) ? (int) $statusId : null,
                'label' => $label,
                'sort_order' => $row['sort_order'] ?? $order,
                'is_system_default' => $statusId && isset(self::SYSTEM_STATUS_LABELS[$statusId]),
            ];
            $order++;
        }

        if (!count($clean)) {
            return [];
        }

        usort($clean, fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));
        $reordered = [];
        $i = 1;
        foreach ($clean as $row) {
            $reordered[] = [
                'status_id' => $row['status_id'],
                'label' => $row['label'],
                'sort_order' => $i++,
                'is_system_default' => $row['is_system_default'] ?? false,
            ];
        }

        return $reordered;
    }

    private function resolveCaseLabel(ProjectCase $case): string
    {
        if ($case->kind === 'PLAN') {
            return '目標値';
        }

        if ($case->kind === 'ACTUAL') {
            if (!empty($case->status)) {
                return $case->status;
            }
            return '実績';
        }

        return $case->status ?? '―';
    }

    public function project_actual_status_suggestions(): JsonResponse
    {
        $rows = ProjectRecord::query()
            ->where('has_actual_func', true)
            ->pluck('actual_statuses')
            ->filter()
            ->toArray();

        $labels = [];
        foreach ($rows as $row) {
            if (is_string($row)) {
                $decoded = json_decode($row, true);
            } else {
                $decoded = $row;
            }
            if (!is_array($decoded)) continue;
            foreach ($decoded as $item) {
                $label = $item['label'] ?? $item['custom_label'] ?? null;
                if (!$label) continue;
                $labels[] = $label;
            }
        }

        $unique = array_values(array_unique($labels));

        return response()->json(['suggestions' => $unique]);
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
                'subMetrics:id,project_metric_id,expression,sort_order',
                'values' => fn($q) => $q
                    ->where('project_record_id', $project->id)
                    ->whereBetween('period', [$data['start'], $data['end']])
                    ->orderBy('period'),
            ])
            ->orderBy('id')
            ->get();

        $labelMap = $metrics->pluck('label_ja', 'id')->all();

        return $metrics->map(function (ProjectMetric $metric) use ($labelMap) {
            $vals = [];
            foreach ($metric->values as $v) {
                $vals[$v->period->format('Y-m-d')] = $v->value;
            }
            return [
                'id'                => $metric->id,
                'label_ja'          => $metric->label_ja,
                'kind'              => $metric->kind,
                'value_type'        => $metric->value_type,
                'line'              => $metric->line,
                'is_active'         => (bool) $metric->is_active,
                'sort_order'        => $metric->sort_order,
                'scenario_label_ja' => $metric->scenario_label_ja,
                'expression'        => $this->denormalizeExpression($metric->formula?->expression, $labelMap),
                'expression_normalized' => $metric->formula?->expression,
                'sub_metrics'       => $metric->subMetrics
                    ->sortBy('sort_order')
                    ->map(fn($sub) => [
                        'id'         => $sub->id,
                        'expression' => $this->denormalizeExpression($sub->expression, $labelMap),
                        'expression_normalized' => $sub->expression,
                        'sort_order' => $sub->sort_order,
                    ])->values(),
                'values'            => $vals,
            ];
        });
    }
    public function project_metrics_for_period(ProjectRecord $project, Request $req) {
        $period = $req->validate(['period' => ['required', 'date_format:Y-m-d']])['period'];

        $metrics = ProjectMetric::with([
            'formula:id,project_metric_id,expression',
            'subMetrics:id,project_metric_id,expression,sort_order',
            'values' => fn($q) => $q
                ->where('project_record_id', $project->id)
                ->where('period', $period),
        ])->orderBy('sort_order')->orderBy('id')->get();

        $labelMap = $metrics->pluck('label_ja', 'id')->all();

        return $metrics->map(fn(ProjectMetric $metric) => [
            'id'               => $metric->id,
            'label_ja'         => $metric->label_ja,
            'kind'             => $metric->kind,
            'value_type'       => $metric->value_type,
            'line'             => $metric->line,
            'is_active'        => (bool) $metric->is_active,
            'sort_order'       => $metric->sort_order,
            'scenario_label_ja'=> $metric->scenario_label_ja,
            'expression'       => $this->denormalizeExpression($metric->formula?->expression, $labelMap),
            'expression_normalized' => $metric->formula?->expression,
            'sub_metrics'      => $metric->subMetrics
                ->sortBy('sort_order')
                ->map(fn($sub) => [
                    'id'         => $sub->id,
                    'expression' => $this->denormalizeExpression($sub->expression, $labelMap),
                    'expression_normalized' => $sub->expression,
                    'sort_order' => $sub->sort_order,
                ])->values(),
            'value'            => optional($metric->values->first())->value,
        ]);
    }
    public function metric_store(StoreMetricRequest $req)
    {
        $data = $req->validated();

        $metric = ProjectMetric::create([
            'label_ja'         => $data['label_ja'],
            'kind'             => $data['kind'],
            'value_type'       => $data['value_type'],
            'line'             => $data['line'] ?? null,
            'is_active'        => $data['is_active'] ?? true,
            'sort_order'       => $data['sort_order'] ?? 0,
            'scenario_label_ja'=> $data['scenario_label_ja'] ?? null,
        ]);

        if ($metric->kind === 'derived') {
            $normalized = $this->normalizeExpression($data['expression'] ?? '', [$metric->id => $metric->label_ja]);
            $this->assertValidExpression($normalized['tokens'], $normalized['metric_ids'], $metric->id);

            $metric->formula()->create([
                'expression' => $normalized['expression'],
                'version'    => 1,
            ]);
        }

        $this->syncSubMetrics($metric, $data['sub_metrics'] ?? []);

        return response()->json(['id' => $metric->id], 201);
    }

    public function metric_update(UpdateMetricRequest $req, ProjectMetric $metric)
    {
        $data = $req->validated();

        $metric->update([
            'label_ja'         => $data['label_ja'],
            'kind'             => $data['kind'],
            'value_type'       => $data['value_type'],
            'line'             => $data['line'] ?? null,
            'is_active'        => $data['is_active'] ?? $metric->is_active,
            'sort_order'       => $data['sort_order'] ?? $metric->sort_order,
            'scenario_label_ja'=> $data['scenario_label_ja'] ?? null,
        ]);

        if ($metric->kind === 'derived') {
            $normalized = $this->normalizeExpression($data['expression'] ?? '', [$metric->id => $metric->label_ja]);
            $this->assertValidExpression($normalized['tokens'], $normalized['metric_ids'], $metric->id);

            $metric->formula()->updateOrCreate(
                [],
                ['expression' => $normalized['expression'], 'version' => 1]
            );
        } else {
            $metric->formula()?->delete();
        }

        $this->syncSubMetrics($metric, $data['sub_metrics'] ?? []);

        return response()->json(['status' => 'ok']);
    }
    public function validateExpression(Request $req)
    {
        $data = $req->validate([
            'expression'        => ['required','string'],
            'target_metric_id'  => ['sometimes','nullable','integer','exists:project_metrics,id'],
            'target_label_ja'   => ['sometimes','nullable','string'],
        ]);

        $targetId = $data['target_metric_id'] ?? null;
        if (! $targetId && ! empty($data['target_label_ja'])) {
            $targetId = ProjectMetric::where('label_ja', $data['target_label_ja'])->value('id');
        }

        $extraLabels = [];
        if ($targetId) {
            $label = ProjectMetric::where('id', $targetId)->value('label_ja');
            if ($label) {
                $extraLabels[$targetId] = $label;
            }
        }

        $normalized = $this->normalizeExpression($data['expression'], $extraLabels);
        $this->assertValidExpression($normalized['tokens'], $normalized['metric_ids'], $targetId);

        return response()->json([
            'ok'         => true,
            'normalized' => $this->denormalizeExpression($normalized['expression'], $this->metricLabelMap($extraLabels)),
        ]);
    }
    protected function metricLabelMap(array $extra = []): array
    {
        return array_replace(ProjectMetric::pluck('label_ja', 'id')->all(), $extra);
    }

    protected function denormalizeExpression(?string $expression, array $labelMap): ?string
    {
        if (! $expression) {
            return null;
        }

        $rendered = preg_replace_callback('/\{\{m:(\d+)\}\}/u', function (array $match) use ($labelMap) {
            $metricId = (int) $match[1];
            return $labelMap[$metricId] ?? "[未定義:{$metricId}]";
        }, $expression);

        return preg_replace('/\s+/', ' ', trim($rendered));
    }

    protected function normalizeExpression(string $expression, array $extraLabels = []): array
    {
        $normalized = preg_replace('/\s+/', ' ', trim($expression));
        if ($normalized === '') {
            return ['expression' => '', 'tokens' => [], 'metric_ids' => []];
        }

        $labelMap = array_filter($this->metricLabelMap($extraLabels), fn($label) => filled($label));
        if (empty($labelMap)) {
            abort(422, '参照できるメトリックがありません。');
        }

        $replacements = [];
        foreach ($labelMap as $id => $label) {
            $replacements[$label] = "{{m:{$id}}}";
        }

        uksort($replacements, fn($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        foreach ($replacements as $label => $placeholder) {
            $pattern = '/' . preg_quote($label, '/') . '/u';
            $normalized = preg_replace($pattern, ' ' . $placeholder . ' ', $normalized);
        }

        $normalized = preg_replace('/\s+/', ' ', trim($normalized));

        preg_match_all('~\{\{m:\d+\}\}|\d+(?:\.\d+)?|[+\-*\/()]|[A-Za-z_][A-Za-z0-9_]*~u', $normalized, $matches);
        $tokens = $matches[0] ?? [];

        $unused = trim(preg_replace('~\{\{m:\d+\}\}|\d+(?:\.\d+)?|[+\-*\/()]|[A-Za-z_][A-Za-z0-9_]*~u', ' ', $normalized));
        
        if ($unused !== '') {
            abort(422, '不明な語句が式に含まれています: ' . $unused);
        }

        $metricIds = [];
        foreach ($tokens as $token) {
            if (preg_match('~\{\{m:(\d+)\}\}~', $token, $m)) {
                $metricIds[] = (int) $m[1];
            }
        }

        return [
            'expression'  => $normalized,
            'tokens'      => $tokens,
            'metric_ids'  => array_values(array_unique($metricIds)),
        ];
    }

    protected function assertValidExpression(array $tokens, array $metricIds, ?int $targetMetricId = null): void
    {
        if (empty($tokens)) {
            abort(422, '式を入力してください。');
        }

        $funcs = ['nullif','pct','ratio'];
        $paren = 0;
        $prevType = null;
        $prevToken = null;

        foreach ($tokens as $index => $token) {
            $type = null;
            $metricId = null;
           
            if (preg_match('/\{\{m:(\d+)\}\}/', $token, $m)) {
                $type = 'metric';
                $metricId = (int) $m[1];
            } elseif (in_array($token, ['+','-','*','/'], true)) {
                $type = 'op';
            } elseif ($token === '(') {
                $type = 'lpar';
            } elseif ($token === ')') {
                $type = 'rpar';
            } elseif (is_numeric($token)) {
                $type = 'num';
            } elseif (in_array(strtolower($token), $funcs, true)) {
                $type = 'func';
            } else {
                abort(422, '使用できないトークンです: ' . $token);
            }
            
            if ($type === 'func') {
                $next = $tokens[$index + 1] ?? null;
                if ($next !== '(') {
                    abort(422, '関数は直後に"("が必要です: ' . $token);
                }
            }

            if ($type === 'metric' && $metricId && ! ProjectMetric::where('id', $metricId)->exists()) {
                abort(422, '存在しないメトリックが参照されています。');
            }

            if ($type === 'lpar') {
                $paren++;
            } elseif ($type === 'rpar') {
                $paren--;
                if ($paren < 0) {
                    abort(422, '括弧の対応が正しくありません。');
                }
            }

            if (in_array($type, ['metric','num','func'], true) && in_array($prevType, ['metric','num','rpar'], true)) {
                abort(422, '演算子が不足しています。');
            }

            if ($type === 'lpar' && in_array($prevType, ['metric','num','rpar'], true)) {
                abort(422, '"(" の前に演算子を挿入してください。');
            }

            if ($type === 'op' && ($prevType === null || in_array($prevType, ['op','lpar'], true))) {
                abort(422, '演算子の位置が不正です。');
            }

            if ($type === 'rpar' && in_array($prevType, ['op','lpar'], true)) {
                abort(422, '閉じ括弧の前に値が必要です。');
            }

            $prevType = $type;
            $prevToken = $token;
        }

        if ($paren !== 0) {
            abort(422, '括弧の数が一致しません。');
        }

        if (in_array($prevType, ['op','lpar'], true)) {
            abort(422, '式の末尾に演算子または"("は使用できません。');
        }

        // if ($targetMetricId && in_array($targetMetricId, $metricIds, true)) {
        //     abort(422, '自分自身を参照する式は登録できません。');
        // }

        if ($targetMetricId) {
            foreach ($metricIds as $id) {
                if ($this->dependsOnMetric($id, $targetMetricId, [$targetMetricId => true])) {
                    abort(422, '循環参照が検出されました。');
                }
            }
        }
    }

    protected function dependsOnMetric(int $startId, int $needleId, array $seen = []): bool
    {
        if (isset($seen[$startId])) {
            return false;
        }

        $seen[$startId] = true;

        $expression = ProjectMetric::find($startId)?->formula?->expression;
        if (! $expression) {
            return false;
        }

        preg_match_all('/\{\{m:(\d+)\}\}/', $expression, $matches);
        $dependencies = array_unique(array_map('intval', $matches[1] ?? []));

        foreach ($dependencies as $dependencyId) {
            if ($dependencyId === $needleId) {
                return true;
            }

            if ($this->dependsOnMetric($dependencyId, $needleId, $seen)) {
                return true;
            }
        }

        return false;
    }

    protected function syncSubMetrics(ProjectMetric $metric, array $subMetrics): void
    {
        if (empty($subMetrics)) {
            $metric->subMetrics()->delete();
            return;
        }

        $keptIds = [];
        foreach ($subMetrics as $index => $sub) {
            if (! isset($sub['expression'])) {
                continue;
            }

            $normalized = $this->normalizeExpression($sub['expression'], [$metric->id => $metric->label_ja]);
            $this->assertValidExpression($normalized['tokens'], $normalized['metric_ids'], $metric->id);

            $payload = [
                'expression' => $normalized['expression'],
                'sort_order' => $sub['sort_order'] ?? $index,
            ];

            if (! empty($sub['id'])) {
                $metric->subMetrics()->where('id', $sub['id'])->update($payload);
                $keptIds[] = $sub['id'];
            } else {
                $new = $metric->subMetrics()->create($payload);
                $keptIds[] = $new->id;
            }
        }

        if (! empty($keptIds)) {
            $metric->subMetrics()->whereNotIn('id', $keptIds)->delete();
        } else {
            $metric->subMetrics()->delete();
        }
    }

    protected function resolveScenarioLabel(string $value): string
    {
        $map = [
            'annual_budget' => '年度予算',
            'plan'          => '損益計画',
            'actual'        => '実績',
        ];

        return $map[$value] ?? $value;
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

    public function metric_values_store(ProjectRecord $project, Request $req)
    {
        $data = $req->validate([
            'period'            => ['required','date_format:Y-m-d'],
            'values'            => ['required','array','min:1'],
            'values.*.label_ja' => ['required','string'],
            'values.*.value'    => ['nullable','numeric'],
        ]);

        $period = Carbon::parse($data['period'])->startOfMonth()->toDateString();

        $labels = collect($data['values'])->pluck('label_ja')->filter()->unique()->values();
        if ($labels->isEmpty()) {
            abort(422, 'メトリックが指定されていません。');
        }

        $metricMap = ProjectMetric::whereIn('label_ja', $labels)->pluck('id', 'label_ja');
        $missing = $labels->diff($metricMap->keys());
        if ($missing->isNotEmpty()) {
            abort(422, '未登録のメトリックがあります: '. $missing->join(', '));
        }

        $now = now();
        $rows = [];
        foreach ($data['values'] as $entry) {
            $label = $entry['label_ja'];
            if (! $metricMap->has($label)) {
                continue;
            }

            $rows[] = [
                'project_record_id' => $project->id,
                'project_metric_id' => $metricMap[$label],
                'period'            => $period,
                'value'             => $entry['value'],
                'source'            => 'manual',
                'created_at'        => $now,
                'updated_at'        => $now,
            ];
        }

        if ($rows) {
            ProjectMetricValue::upsert(
                $rows,
                ['project_record_id', 'project_metric_id', 'period'],
                ['value','source','updated_at']
            );
        }

        return response()->json(['status' => 'ok']);
    }
    public function yearly_budget_store(ProjectRecord $project, Request $req) {
        $data = $req->validate([
            'scenario_code'          => ['required','string'],                 // e.g. 'annual_budget'
            'scenario_label_ja'      => ['sometimes','string'],
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

        $scenarioLabel = $data['scenario_label_ja'] ?? $this->resolveScenarioLabel($data['scenario_code']);

        $metricIds = ProjectMetric::query()
            ->where('scenario_label_ja', $scenarioLabel)
            ->whereIn('line', ['sales','expense'])
            ->pluck('id', 'line');

        DB::transaction(function () use ($project, $data, $metricIds) {
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
                if ($metricIds->has('sales')) {
                    $mvRows[] = [
                        'project_record_id' => $project->id,
                        'project_metric_id' => $metricIds['sales'],
                        'period'            => $period,
                        'value'             => $totalSales,
                        'source'            => 'manual',
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ];
                }
                if ($metricIds->has('expense')) {
                    $mvRows[] = [
                        'project_record_id' => $project->id,
                        'project_metric_id' => $metricIds['expense'],
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

    public function download_yearly_template(Request $req) {
        $data = $req->validate([
            'year' => 'required|integer',
            'rows' => 'array',
            'projectName' => 'string',
            'month' => 'integer',
        ]);
        $export = new YearlyBudgetTemplate(fiscalYear: $data['year'], fiscalMonth: $data['month'], rows: $data['rows'] ?? [], projectName: $data['projectName']);
        return Excel::download(
            $export,
            "{$data['projectName']}_{$data['year']}_年度予算.xlsx"
        );
    }
    public function upload_yearly_budget(Request $req) {
        $data = $req->validate([
            'file' => 'required|file|mimes:xlsx,xls',
            'project_id' => 'required|integer',
            'year' => 'required|integer',
            'projectName' => 'string'
        ]);
    
        Excel::import(new YearlyBudgetImport($data['project_id'], $data['year'], $data['projectName']), $data['file']);

        return response()->json(['ok' => true]);
    }
    public function project_goal_comment_create(Request $request) {
        $data = $request->validate([
            'record_id' => 'required',
            'which' => 'required|string|in:goal,salary_issue',
            'content' => 'required|string',
        ]);
        $user = $this->active_user();
        $which = $request->which;

        $record = $which === 'goal' ? ProjectGoal::findOrFail($request->record_id) : SalaryIssue::findOrFail($request->record_id);
        $report = $record->reports()->create([
            'content' => $request->content,
            'user_id' => $user->id,
        ]);

        if($request->attached_temp_files){ 
            foreach($request->attached_temp_files as $item){      
                $file = messageFile::findOrFail($item['id']);
                $col = $which === 'goal' ? 'project_goal_report_id' : 'salary_issue_report_id';
                $file->update([$col => $report->id]);
                $path = "project_goal_report_files";
                File::isDirectory(storage_path("app/{$path}")) or File::makeDirectory(storage_path("app/{$path}"), 0755, true, true);         
                $srcPath = "{$file->id}.{$file->extension}";
                $destPath = "{$file->id}_{$file->user_id}.{$file->extension}";
                $temp_path = storage_path("app/temp_upload/{$srcPath}");
                Storage::disk('local')->move("temp_upload/{$file->id}.{$file->extension}", "{$path}/{$destPath}");                
            }
        }
        
        $notification_targets = [];
        $payload = [];
        if($which === 'goal'){
            if($record->user_id !== $user->id){
                $notification_targets[] = $record->user_id;
            }else{
                $notification_targets = $record->project->manager->pluck('id')->filter(function($uid) use ($user) {
                    return $uid !== $user->id;
                })->toArray();
            }
            foreach($notification_targets as $target_user_id){
                $payload[] = [
                    'user_id' => $record->user_id,
                    'target_user_id' => $target_user_id,
                    'from_user_id' => $user->id,
                    'project_goal_id' => $record->id,
                    'which_half' => $record->which_half,
                    'project_id' => $record->project_id,
                    'year' => $record->year,
                    'created_at' => now(),
                    'updated_at' => now(),
                    
                ];
            }
            
        } else {
            $notification_targets = [];
            if($record->user_id !== $user->id){
                $notification_targets[] = $record->user_id;
            }else{
                if(isset($record['mentor_id'])){
                    $notification_targets[] = $record['mentor_id'];
                }
            }
            $goal = $record->project_goal;
            if(!$goal){
                throw new \Exception("関連する目標が見つかりません。");
            }
            foreach($notification_targets as $target_user_id){
                
                $payload[] = [
                    'user_id' => $record->user_id,
                    'target_user_id' => $target_user_id,
                    'from_user_id' => $user->id,
                    'which_half' => $goal->which_half,
                    'project_id' => $goal->project_id,
                    'year' => $goal->year,
                    'salary_issue_id' => $record->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                    
                ];
            }
        }
        $notification_targets = array_unique($notification_targets);
        $goal_record = $which === 'goal' ? $record : $record->project_goal;
        
        ProjectMemberReportNotification::insert($payload);

        $syntax = '/\[To:(.*?)\:\]/';
        preg_match_all($syntax, $report->content, $matches);
        $mentioned_targets = $matches[1];
        $users = User::whereNot('id', Auth::id())
        ->whereNotNull('email')
        ->where('retire', 0)
        ->where('on_leave', 0)
        ->whereIn('name', $mentioned_targets)
        ->pluck('email')->toArray();

        $emails = collect($users)->filter(function($email){
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        })->toArray();


        SendGoalIssueMentionMail::dispatchAfterResponse($emails, $goal_record, $report->content);

        return response()->json(['id' => $report->id], 201);
    }
    public function goal_issue_comment_badge(Request $request) {
        $user = $this->active_user();
        $goal_badge_count = ProjectMemberReportNotification::where('target_user_id', $user->id)
            ->get();
        return response()->json($goal_badge_count);
    }
    public function clear_goal_issue_badge(Request $request) {
        $user = $this->active_user();
        ProjectMemberReportNotification::where('target_user_id', $user->id)->where($request->column, $request->value)
            ->delete();
        $goal_badge_count = ProjectMemberReportNotification::where('target_user_id', $user->id)
            ->get();
        return response()->json($goal_badge_count);
    }
    public function project_list(Request $request) {
        $user = $this->active_user();
        $projects = ProjectRecord::select('id', 'name')->get();
        $project_setting = $user->project_settings;
        $myProjects = [];
        $otherProjects = [];
        foreach($projects as $project){
            $is_member = $project->members->contains(function($member) use ($user){
                return $member->id === $user->id;
            });
            $is_manager = $project->manager->contains(function($manager) use ($user){
                return $manager->id === $user->id;
            });

            $setting = $project_setting->firstWhere('project_id', $project->id);
        
            if($is_member || $is_manager){
                $myProjects[] = [
                    'id' => $project->id,
                    'name' => $project->name,
                    'color' => $setting ? $setting->color : null,
                ];
            } else {
                $otherProjects[] = [
                    'id' => $project->id,
                    'name' => $project->name,
                    'color' => $setting ? $setting->color : null,
                ];
            }
        }
        return response()->json([
            'myProjects' => $myProjects,
            'otherProjects' => $otherProjects,
        ]);
    }
    public function view_case(Request $request)
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer'],
            'project_id' => ['nullable', 'integer', 'required_without:id'],
            'member_id' => ['nullable', 'integer', 'required_with:project_id'],
            'status' => ['nullable', 'string'],
            'period' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $query = ProjectCase::query()->with('reporter');

        if (!empty($data['id'])) {
            $query->where('id', $data['id']);
        } else {
            $query->where('project_record_id', $data['project_id']);
            if (!empty($data['member_id'])) {
                $query->where('user_id', $data['member_id']);
            }
            if (!empty($data['status'])) {
                $query->where('status', $data['status']);
            }
        }

        if (!empty($data['period'])) {
            $query->forMonth(Carbon::parse($data['period'])->startOf('month'));
        }

        $case = $query->orderByDesc('report_date')->first();

        return response()->json($case);
    }
    public function delete_case(ProjectCase $case)
    {
        $case->delete();
        return response()->json(['status' => 'ok']);
    }
    public function save_review(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer',
        ]);
        $raw = $request->input('summary');
        if (is_string($raw)) {
            $contract = json_decode($raw, true);
        } elseif (is_object($raw)) {
            $contract = json_decode(json_encode($raw), true);
        } else {
            $contract = $raw;
        }
        $overall   = $contract['overall_risk'] ?? $contract['overallRisk'] ?? 'unknown';
        $findings  = $contract['findings'] ?? [];
        $findCount = is_array($findings) ? count($findings) : 0;

        $responseHash = hash('sha256', json_encode($contract, JSON_UNESCAPED_UNICODE));
        $contractRecord = ProjectContract::findOrFail($data['id']);

        $contractRecord->update([
            'review_type' => 'deep',
            'overall_risk' => $overall,
            'findings_count' => $findCount,
            'result_json' => $contract,
            'response_hash' => $responseHash
        ]);

        return response()->json($contractRecord);
    }

    protected function resolveProjectContract(ProjectRecord $project): ?ProjectContract
    {
        return $project->contract()->latest('updated_at')->first();
    }

    protected function ensureProjectAccess(ProjectRecord $project): void
    {
        abort_unless($this->userCanAccessProject($project), 403, '権限がありません。');
    }

    protected function userCanAccessProject(ProjectRecord $project): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        if (($user->position_id && $user->position_id < 6) || in_array($user->id, [608, 610])) {
            return true;
        }

        $project->loadMissing(['manager', 'members']);

        return $project->manager->contains('id', $user->id)
            || $project->members->contains('id', $user->id)
            || $project->director_id === $user->id;
    }

    public function get_resources_kintone(Request $request)
    {
        $interval = $request->input('interval', []);
        $startYear  = (int)($interval['startYear']  ?? 0);
        $startMonth = (int)($interval['startMonth'] ?? 0);
        $endYear    = (int)($interval['endYear']    ?? 0);
        $endMonth   = (int)($interval['endMonth']   ?? 0);

        if (
            $startYear <= 0 || $endYear <= 0 ||
            $startMonth < 1 || $startMonth > 12 ||
            $endMonth < 1 || $endMonth > 12
        ) {
            return response()->json(['message' => 'Invalid interval'], 422);
        }

        [$startDate, $endDate, $months] = $this->resolveResourceInterval($startYear, $startMonth, $endYear, $endMonth);

        [$out, $opt] = $this->fetchResourceRows($startDate, $endDate);
        $m_out = $this->fetchResourceMembers();
        $out = $this->backfillResourceMembers($out, $m_out, $months);
        $memberMeta = $this->buildResourceMemberMeta($out, $m_out, $months);
        return response()->json([
            'interval' => [
                'startDate' => $startDate,
                'endDate' => $endDate,
            ],
            'data' => $out,
            'member' => $m_out,
            'options' => array_keys($opt),
            'member_meta' => $memberMeta,
        ]);
    }

    private function resolveResourceInterval(int $startYear, int $startMonth, int $endYear, int $endMonth): array
    {
        $startInstance = Carbon::createFromDate($startYear, $startMonth, 1)->startOfDay();
        $endInstance   = Carbon::createFromDate($endYear, $endMonth, 1)->startOfDay();

        if ($endInstance->lt($startInstance)) {
            [$startInstance, $endInstance] = [$endInstance, $startInstance];
        }

        $maxMonths = 12;
        $monthsApart = $startInstance->diffInMonths($endInstance);
        if ($monthsApart > $maxMonths - 1) {
            $endInstance = $startInstance->copy()->addMonths($maxMonths - 1);
        }

        $startDate = $startInstance->copy()->startOfMonth()->toDateString();
        $endDate   = $endInstance->copy()->endOfMonth()->toDateString();

        $months = [];
        $cursor = $startInstance->copy()->startOfMonth();
        $endMonthCursor = $endInstance->copy()->startOfMonth();
        while ($cursor->lte($endMonthCursor)) {
            $months[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        return [$startDate, $endDate, $months];
    }

    private function fetchResourceRows(string $startDate, string $endDate): array
    {
        $query = "日付 >= \"{$startDate}\" and 日付 <= \"{$endDate}\"";
        $fields = ["給料手当テーブル", "部門", "日付", "新部門ｺｰﾄﾞ", "レコード番号"];

        $limit = 500;
        $offset = 0;

        $out = [];
        $opt = [];
        while (true) {
            $q = $query . " limit {$limit} offset {$offset}";
            $recs = $this->api->getRecords(1068, $q, $fields);

            if (empty($recs)) {
                break;
            }

            foreach ($recs as $r) {
                $date = (string)($r['日付']['value'] ?? '');
                if ($date === '') {
                    continue;
                }

                $ym = date('Y-m', strtotime($date));

                $dept = (string)($r['部門']['value'] ?? '');
                if ($dept === '') {
                    $dept = '_no_dept_';
                }

                $rows = $r['給料手当テーブル']['value'] ?? [];
                foreach ($rows as $row) {
                    $v = $row['value'] ?? [];

                    $name = (string)($v['ルックアップ_4']['value'] ?? '');
                    if ($name === '') {
                        continue;
                    }

                    $out[$name][$dept][$ym] = [
                        '給料手当数量' => (float)($v['給料手当数量']['value'] ?? 0),
                        '所定労働日数' => (float)($v['所定労働日数']['value'] ?? 0),
                        '給料手当出金' => (float)($v['給料手当出金']['value'] ?? 0),
                        '部門コード'   => (string)($r['新部門ｺｰﾄﾞ']['value'] ?? ''),
                        'レコード番号' => (int)($r['レコード番号']['value'] ?? 0),
                        '雇用形態'     => (string)($v['雇用形態']['value'] ?? '')
                    ];

                    $val = trim((string)($v['雇用形態']['value'] ?? ''));
                    if ($val !== '') {
                        $opt[$val] = true;
                    }
                }
            }

            if (count($recs) < $limit) {
                break;
            }

            $offset += $limit;
        }

        return [$out, $opt];
    }

    private function fetchResourceMembers(): array
    {
        $m_query = '退職フラグ in ("在籍中") and 社員コード != ""';
        $m_fields = ["氏名", "所定労働日数月平均", "正社員日当計算", "雇用形態"];

        $m_limit = 500;
        $m_offset = 0;
        $m_out = [];
        while (true) {
            $q = $m_query . " limit {$m_limit} offset {$m_offset}";
            $m_recs = $this->api->getRecords(96, $q, $m_fields);

            if (empty($m_recs)) {
                break;
            }

            foreach ($m_recs as $r) {
                $name = $r["氏名"]["value"];
                $amount = $r["正社員日当計算"]["value"];
                $average = $r["所定労働日数月平均"]["value"];
                $type = $r['雇用形態']['value'];
                $m_out[$name] = [
                    '給料手当出金' => (float)($amount ?? 0),
                    '所定労働日数' => (float)($average ?? 0),
                    '雇用形態'     => (string)($type ?? 0),
                ];
            }

            if (count($m_recs) < $m_limit) {
                break;
            }

            $m_offset += $m_limit;
        }

        return $m_out;
    }

    private function backfillResourceMembers(array $out, array $members, array $months): array
    {
        $defaultDept = '';

        foreach ($members as $name => $member) {
            foreach ($months as $ym) {
                $hasMonthAlready = false;

                if (isset($out[$name]) && is_array($out[$name])) {
                    foreach ($out[$name] as $byMonth) {
                        if (isset($byMonth[$ym])) {
                            $hasMonthAlready = true;
                            break;
                        }
                    }
                }

                if ($hasMonthAlready) {
                    continue;
                }

                $out[$name][$defaultDept][$ym] = [
                    '給料手当数量' => 0.0,
                    '所定労働日数' => (float)($member['所定労働日数'] ?? 0),
                    '給料手当出金' => (float)($member['給料手当出金'] ?? 0),
                    '部門コード'   => '',
                    'レコード番号' => 0,
                    '雇用形態'     => (string)($member['雇用形態'] ?? ''),
                ];
            }
        }

        return $out;
    }

    private function buildResourceMemberMeta(array $out, array $members, array $months): array
    {
        $memberMeta = [];
        foreach ($members as $name => $member) {
            foreach ($months as $ym) {
                $memberMeta[$name][$ym] = [
                    '雇用形態'     => (string)($member['雇用形態'] ?? ''),
                    '所定労働日数' => (float)($member['所定労働日数'] ?? 0),
                    '給料手当出金' => (float)($member['給料手当出金'] ?? 0),
                ];
            }
        }

        foreach ($out as $name => $depts) {
            if (isset($memberMeta[$name])) {
                continue;
            }
            foreach ($months as $ym) {
                $memberMeta[$name][$ym] = [
                    '雇用形態'     => '',
                    '所定労働日数' => 0.0,
                    '給料手当出金' => 0.0,
                ];
            }
            foreach ($depts as $byMonth) {
                foreach ($byMonth as $ym => $row) {
                    $memberMeta[$name][$ym] = [
                        '雇用形態'     => (string)($row['雇用形態'] ?? ''),
                        '所定労働日数' => (float)($row['所定労働日数'] ?? 0),
                        '給料手当出金' => (float)($row['給料手当出金'] ?? 0),
                    ];
                }
            }
        }

        return $memberMeta;
    }
    public function update_resource_kintone(Request $request) 
    {
        $data = $request->validate([
            'quantity' => 'required|numeric',
            'member'   => 'required|string',
            'recordId' => 'required|integer'
        ],[
            'quantity.required' => '給料手当数量は必須です。',
            'member.required'   => 'メンバー名は必須です。',
            'recordId.required' => 'レコード番号は必須です。'
        ]);

        $record = $this->api->getRecord(1068, $data['recordId']);
        $table  = data_get($record, '給料手当テーブル.value', []);

        $index = collect($table)->search(fn ($row) =>
            data_get($row, 'value.ルックアップ_4.value') === $data['member']
        );
        
        if ($index === false) {
            abort(404);
        }
        $tableForPut = array_map(function ($row, $i) use ($index, $data) {
            return [
                'id' => (string) $row['id'],
                'value' => [
                    '給料手当数量' => [
                        'value' => $i === $index
                            ? (string) $data['quantity']
                            : (string) data_get($row, 'value.給料手当数量.value', ''),
                    ],
                ],
            ];
        }, $table, array_keys($table));

        
        $updated = $this->api->putRecord(1068, $data['recordId'], [
            '給料手当テーブル' => ['value' => $tableForPut],
        ]);

        return response()->json($updated);
    }
    
}

