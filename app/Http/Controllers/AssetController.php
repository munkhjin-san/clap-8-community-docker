<?php

namespace App\Http\Controllers;

use App\Exports\AssetData;
use App\Imports\AssetImport;
use App\Models\AssetRecord;
use App\Models\AssetRequest;
use App\Models\AssetRequestStep;
use App\Models\AssetType;
use App\Models\officeRecord;
use App\Models\ProjectRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class AssetController extends Controller
{
    private function active_user(){
        $sub = Auth::user()->linked()->where('main_id', Auth::id())->wherePivot('active', 1)->first();
        if($sub){
            return $sub;
        }else{
            return Auth::user();
        }
    }
    public function get_possible_projects() 
    {
        $projects = ProjectRecord::with(['members', 'manager'])->select('id', 'name')->get();
        return response()->json($projects);
    }
    public function get_possible_projects_by_user(Request $request) 
    {
        $request->validate([
            'user_id' => 'required|integer',
        ]);
        $user_id = $request->user_id;
        $projects = ProjectRecord::with(['members', 'manager'])->whereHas('members', function ($query) use ($user_id) {
            $query->where('users.id', $user_id);
        })->orWhereHas('manager', function ($query) use ($user_id) {
            $query->where('users.id', $user_id);
        })
        ->select('id', 'name')->get();
        return response()->json($projects);
    }
    public function get_possible_members()
    {
        $ng_list = ['推し', '知人', '家族', '友人', '関係者', 'お知らせアカウント'];
        $all_users = User::where('deleted_flag', 0)
        ->where('retire', 0)
        ->whereNotIn('name', $ng_list)
        ->select('id', 'name', 'icon_path', 'icon_bg')
        ->get();
        return response()->json($all_users);
    }
    public function get_possible_offices()
    {
        $offices = officeRecord::get();
        return response()->json($offices);
    }


    public function create_asset(Request $request) 
    {
        $id = $request->id ?? null;
        $params = $request->params;
        if($id == null){
            $params['created_by'] = $this->active_user()->id;
        }
        $asset = AssetRecord::findOrNew($id);

        $owner_changed = ($asset->user_id ?? null) !== ($params['user_id'] ?? null)
              || ($asset->external_user ?? null) !== ($params['external_user'] ?? null);

        if($owner_changed){
            $moveRequest = $asset->requests()->create([
                'from_user' => $asset->user_id ?? null,
                'to_user' => $params['user_id'] ?? null,
                'from_external_user' => $asset->external_user ?? null,
                'to_external_user' => $params['external_user'] ?? null,
                'status' => 2,
            ]);
            $moveRequest->steps()->create([
                'created_by' => Auth::id(),
                'value' => 4,
                'approved_by' => Auth::id(),
                'approved_at' => now()
            ]);
        }
        
        $update = $asset->fill($params)->save();

        // $asset = AssetRecord::firstOr
        // $asset = AssetRecord::updateOrCreate(["id" => $id], $params);
        // AssetType::firstOrCreate(['value' => $params['item_name']])->increment('used_count');
        

        return response()->json($update);
    }
    public function admin_asset_list(Request $request) 
    {
        $assets = AssetRecord::query();
        $mode = $request->mode ?? 'normal';
        $assets->with([
            'current_user',
            'current_project' => function ($query) {
                $query->with('manager');                
            },
            'requests' => function ($query) {
                $query->with(['recieve_user', 'send_user', 'files', 'steps' => function ($query) {
                    $query->with(['approver', 'creator']);
                }]);
            },
            'request_logs' => function ($query) {
                $query->with(['recieve_user', 'send_user', 'files', 'steps' => function ($query) {
                    $query->with(['approver', 'creator']);
                }]);
            },
            'current_office'
        ]);
        $data = $assets->orderBy('id', 'desc')->paginate(30);
        return response()->json($data);
    }
    public function get_assets(Request $request) 
    {

        $user = $this->active_user();
        $mode = $request->mode ?? 'normal';
        $projectId = $request->project_id ? [$request->project_id] : [];
        $memberId = $request->user_id ? $request->user_id : [];
        $officeId = $request->office_id ?? [];
        
        $classification = $request->classification ?? [];
        $status = $request->status ?? [];
        $mode = $request->mode ?? 'normal';

        if($mode == 'partner'){
            $memberId = [Auth::id()];
            $projectId = [];
        }
        $assets = AssetRecord::query();
        
        if($request->item_name){
            $assets->where('item_name', 'like', "%{$request->item_name}%");
        }
        if($request->model_number){
            $assets->where('model_number', 'like', "%{$request->model_number}%");
        }
        if($request->gl_number){
            $number = (int) $request->gl_number;
            $number_remove_leading_zero = preg_replace('/^0+/', '', $number);
            $assets->where('id', 'like',"%{$number_remove_leading_zero}%");
        }

        if(!empty($projectId)){
            $assets->whereIn('project_id', $projectId);
        }    
        if(!empty($officeId)){
            $assets->whereIn('office_id', $officeId);
        }    
        if (!empty($memberId)) {
            $assets->whereIn('user_id', $memberId );
        }

        if (!empty($classification)) {
            $assets->whereIn('classification', $classification);
        }

        if (!empty($status)) {
            $assets->whereIn('status', $status);
        }
        if (!empty($request->confirm_status)) {
            if (in_array('confirmed', $request->confirm_status) && !in_array('unconfirmed', $request->confirm_status)) {
                $assets->whereHas('confirm_logs', fn($q) => $q->whereYear('created_at', now()->year));
            } elseif (!in_array('confirmed', $request->confirm_status) && in_array('unconfirmed', $request->confirm_status)) {
                $assets->whereDoesntHave('confirm_logs', fn($q) => $q->whereYear('created_at', now()->year));
            }
        }

        $prioritizeRequests = ($user->id == 610 || $user->id == 608);
        if ($prioritizeRequests) {
            // Put assets that have related requests on top.
            $assets->withCount('requests');
        }
        
        $assets->with([
            'current_user',
            'current_project' => function ($query) use ($projectId) {
                $query->with('manager');                
            },
            'requests' => function ($query) {
                $query->with(['recieve_user', 'send_user', 'files', 'steps' => function ($query) {
                    $query->with(['approver', 'creator'])->orderBy('value', 'desc');
                }])->orderBy('created_at', 'desc');
            },
            'request_logs' => function ($query) {
                $query->with(['recieve_user', 'send_user', 'files', 'steps' => function ($query) {
                    $query->with(['approver', 'creator']);
                }])->orderBy('created_at', 'desc');
            },
            'current_office',
            'confirm_logs' => function ($query) {
                $query->with(['user', 'files'])->orderBy('created_at', 'desc');
            }
        ]);

        if ($prioritizeRequests) {
            $data = $assets
                ->orderByRaw('CASE WHEN requests_count > 0 THEN 1 ELSE 0 END DESC')
                ->orderBy('created_at', 'desc');
        } else {
            $data = $assets->orderBy('created_at', 'desc');
        }

        if($mode == 'export'){
            $data = $data->get();
        }
        else{
            $data = $data->paginate(30);
        }

        return response()->json($data);
    }


    public function delete_asset(Request $request) 
    {
        $request->validate([
            'id' => 'required',
        ]);
        $asset = AssetRecord::findOrFail($request->id);
        $asset->delete();
        return response()->json(['message' => 'Successfully deleted']);
    }


    public function asset_move_request(Request $request){
        $request->validate([
            'asset_id' => 'required',
        ]);
        $files = $request->file_ids ?? [];
        $active_user = $this->active_user();
        $assetRecord = AssetRecord::findOrFail($request->asset_id);
        // $to_project = $request->to_project ?? null;
        $moveRequest = $assetRecord->requests()->create([
            'from_user' => $assetRecord->user_id ?? null,
            'from_project' => $assetRecord->project_id ?? null,
            'to_user' => $request->to_user ?? null,
            'not_broken' => $request->not_broken,
            'from_external_user' => $assetRecord->external_user ?? null,
            'to_external_user' => $request->to_external_user ?? null,

            // 'to_project' => $to_project
        ]);
        
        $moveRequest->files()->sync($files);


        // $step1 = $moveRequest->steps()->create([
        //     'created_by' => $active_user->id,
        //     'value' => 1
        // ]);

        // $fromProject = ProjectRecord::find($assetRecord->project_id);
        // $active_user_is_manager = $fromProject->manager()->where('users.id', $active_user->id)->exists();
        // if($active_user_is_manager){
        //     $step1->update([
        //         'approved_by' => $active_user->id,
        //         'approved_at' => now()
        //     ]);
        // }

        // $step2 = $moveRequest->steps()->create([
        //     'created_by' => $active_user->id,
        //     'value' => 2
        // ]);
        // if($to_project){
        //     $toProject = ProjectRecord::find($to_project);
        //     $step3 = $moveRequest->steps()->create([
        //         'created_by' => $active_user->id,
        //         'value' => 3
        //     ]);
        //     $active_user_is_manager_of_target = $toProject->manager()->where('users.id', $active_user->id)->exists();
        //     if($active_user_is_manager_of_target){
        //         $step3->update([
        //             'approved_by' => $active_user->id,
        //             'approved_at' => now()
        //         ]);
        //     }
        // }
        $step4 = $moveRequest->steps()->create([
            'created_by' => $active_user->id,
            'value' => 4
        ]);


        return response()->json($moveRequest);
    }
    public function asset_return_request(Request $request){
        $request->validate([
            'asset_id' => 'required',
        ]);
        $files = $request->file_ids ?? [];
        $active_user = $this->active_user();
        $assetRecord = AssetRecord::findOrFail($request->asset_id);
        
        $returnRequest = $assetRecord->requests()->create([
            'from_user' => $assetRecord->user_id,
            'from_project' => $assetRecord->project_id,
            'to_user' => 610,
            'not_broken' => $request->not_broken,
        ]);
        
        $returnRequest->files()->sync($files);
        $returnRequest->steps()->create([
            'created_by' => $active_user->id,
            'value' => 7
        ]);

        return response()->json($returnRequest);
    }


    public function asset_approve(Request $request){
        $request->validate([
            'step_id' => 'required',
            'status' => 'required|integer'
        ]);
        $asset_step = AssetRequestStep::findOrFail($request->step_id);
        $asset_request = $asset_step->asset_request;
        $asset_step->update([
            'approved_by' => Auth::id(),
            'approved_at' => now()
        ]);
        if($request->status == 3){
            
            $asset_request->update([
                'status' => 3
            ]);
            $asset_request->asset->update([
                'user_id' => $asset_request->from_user ?? null,
                // 'project_id' => $asset_request->from_project
                'external_user' => $asset_request->from_external_user ?? null
            ]);

            return response()->json($asset_step);                
        }
        // if($asset_step->value == 1){
            
        //     $asset_request->steps()->create([
        //         'created_by' => $this->active_user()->id,
        //         'value' => 2
        //     ]);
        //     return response()->json($asset_step);
        // }
        

        // if($asset_step->value == 2){
        //     $project_id = $request->project_id ?? null;            
        //     $next_step_number = 4;
        //     if($project_id){
        //         $asset_request->asset->update([
        //             'project_id' => $project_id
        //         ]);
        //         $asset_request->update([
        //             'to_project' => $project_id
        //         ]);
        //         $next_step_number = 3;
        //     }
        //     $asset_request->asset->update([
        //         'user_id' => $asset_request->to_user
        //     ]);
        //     $has_next_step = $asset_request->steps()->where('value', 3)->exists();
        //     if(!$has_next_step){
        //         $asset_request->steps()->create([
        //             'created_by' => $this->active_user()->id,
        //             'value' => $next_step_number
        //         ]);
        //     }            
        //     return response()->json($asset_step);

        // }
        // if($asset_step->value == 3){
            
        //     $asset_request->steps()->create([
        //         'created_by' => $this->active_user()->id,
        //         'value' => 4
        //     ]);
        //     return response()->json($asset_step);
        // }
        if($asset_step->value == 4){
            $asset_request->asset->update([
                'user_id' => $asset_request->to_user ?? null,
                'external_user' => $asset_request->to_external_user ?? null,
            ]);
            $current_memo = $asset_request->memo ?? '';
            if($request->memo){
                $username = Auth::user()->name;
                $new_memo = $current_memo ? $current_memo . "\n" : "";
                $new_memo .= "【" . now()->format('Y-m-d H:i') . "】" . $username . ": " . $request->memo;
                $asset_request->update([
                    'memo' => $new_memo
                ]);
            }
            $asset_request->update([
                'status' => 2
            ]);
            $asset_step->update([
                'approved_by' => Auth::id(),
                'approved_at' => now()
            ]);
            return response()->json($asset_step);
        }
        if($asset_step->value == 7){
            $asset_request->update([
                'status' => 2
            ]);
            $asset_request->asset->update([
                'user_id' => null,
                'project_id' => null,
                'external_user' => null,
                'status' => 2,
                'office_id' => $request->office_id ?? null
            ]);
            $asset_step->update([
                'approved_by' => Auth::id(),
                'approved_at' => now()
            ]);
            return response()->json($asset_step);
        }


    }
    public function get_asset_types(Request $request){
        $keyword = $request->key ?? null;
        $super = $request->super ?? null;

        $types = AssetType::query();
        if($keyword){
            $types->where('value', 'like', "%$keyword%");
        }
        if($super){
            // take latest 10 items
            $types->orderBy('used_count', 'desc')->orderBy('id', 'desc')->take(10);
        }
        $data = $types->where('value', '!=', '')->whereNotNull('value')->get();
        $data = $data->map(function($item){
            return $item->value;
        })->toArray();
        if($keyword){
            if(!in_array($keyword, $data )){
                array_unshift($data, $keyword);
            }
        }
        return response()->json($data);
    }
    public function export_asset_csv(Request $request) 
    {
        $assets = $this->get_assets($request);
        $assets = $assets->getData();
        $classification = [
            1 => "資産", 
            2 => "消耗品" ,
            3 => "重要資産" 
        ];
        $statuses = [
            1 => "使用中" ,
            2 => "返却" ,
            3 => "廃棄" ,
            4 => "保管" ,
            5 => "移動" ,
            6 => "故障" 
        ];
        $currentYear = now()->year;
        $rawData = collect($assets)->map(function ($asset) use ($classification, $statuses, $currentYear) {
            
            $gl_number = 'GL' . str_pad($asset->id, 5, '0', STR_PAD_LEFT);
            $confirmCondition = '';
            $confirmedThisYear = collect($asset->confirm_logs ?? [])->filter(function ($log) use ($currentYear) {
                return date('Y', strtotime($log->created_at)) == $currentYear;
            });
            
            return [
                "GL番号" => $gl_number,
                "品名" => $asset->item_name,
                "詳細" => $asset->model_number,
                "使用者" => $asset->external_user ?? $asset->current_user?->name,
                "責任者" => $asset->current_user?->name,
                "ステータス" => $statuses[$asset->status] ?? null,
                "使用場所" => $asset->current_office?->name,
                "確認状況" => $confirmedThisYear->isEmpty() ? "未確認" : "確認済み",
                "確認者" => $confirmedThisYear->first()?->user?->name ?? null,
                "確認日時" => $confirmedThisYear->first() ? date('Y-m-d H:i', strtotime($confirmedThisYear->first()->created_at)) : null,
            ];
        })->toArray();
        return Excel::download(new AssetData($rawData), 'user_data.xlsx');
        // return response()->json($rawData);
    }
    public function get_asset_users(Request $request) 
    {
        
        $user = $this->active_user();
        $mode = $request->mode ?? 'normal';
        $exclude = $request->exclude ?? [];

        if($mode == 'partner'){
            return response()->json([Auth::user()->only('id', 'name', 'icon_path', 'icon_bg')]);
        }
        
        $users = User::where('deleted_flag', 0)
            ->when(!empty($exclude), function ($query) use ($exclude) {
                $query->whereNotIn('id', $exclude);
            })
            ->where('id', '>', 105)
            ->where('retire', 0)
            ->select('id', 'name', 'icon_path', 'icon_bg')
            ->get();

        // add active user to the top of the list
        $active_user_data = $user->only('id', 'name', 'icon_path', 'icon_bg');
        if(!in_array($user->id, $exclude)){
            $users = $users->filter(function($u) use ($user){
                return $u->id != $user->id;
            });
            $users->prepend($active_user_data);
        }

        return response()->json($users);
    }
    public function asset_decision(Request $request){
        $request->validate([
            'asset_request_id' => 'required|integer',
            'status' => 'required|integer'
        ]);
        $asset_request = AssetRequest::findOrFail($request->asset_request_id);
        $returnStep = $asset_request->steps()->where('value', 7)->first();
        $asset_request->update([
            'status' => $request->status,
            'memo' => $request->memo ?? null
        ]);
        if($returnStep){
            if($request->status == 2){
                $asset_request->asset->update([
                    'user_id' => null,
                    'project_id' => null,
                    'external_user' => null,
                    'status' => 2,
                    'office_id' => $request->office_id ?? null
                ]);
            }
            $returnStep->update([
                'approved_by' => Auth::id(),
                'approved_at' => now()
            ]);
            return response()->json($asset_request);
        }
        $moveRequest = $asset_request->steps()->where('value', 4)->first();
        if($moveRequest){
            if($request->status ==2){
                $asset_request->asset->update([
                    'user_id' => $asset_request->to_user ?? null,
                    'external_user' => $asset_request->to_external_user ?? null,
                ]);
                if($request->to_user){
                    $asset_request->update([
                        'to_user' => $request->to_user
                    ]);
                }
                
            }
            
            $moveRequest->update([
                'approved_by' => Auth::id(),
                'approved_at' => now()
            ]);
            return response()->json($asset_request);
        }


    }
    public function confirm_asset(Request $request) 
    {
        $user = $this->active_user();
        $request->validate([
            'asset_id' => 'required',
        ]);
        $asset = AssetRecord::findOrFail($request->asset_id);

        $log = $asset->confirm_logs()->create([
            'user_id' => $user->id,
            'memo' => $request->content ?? null
        ]);
        $files = $request->file_list ?? [];
        $file_ids = collect($files)->pluck('id')->toArray();
        // dd($file_ids);
        $log->files()->sync($file_ids);



        return response()->json(['message' => 'Successfully confirmed']);
    }

}
