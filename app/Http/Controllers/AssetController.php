<?php

namespace App\Http\Controllers;

use App\Imports\AssetImport;
use App\Models\AssetRecord;
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

    public function create_asset(Request $request) 
    {
        $id = $request->id ?? null;
        $params = $request->params;
        $user_ids = $request->user_ids;
        $project_ids = $request->project_ids;
        $asset = AssetRecord::updateOrCreate(["id" => $id], $params);
        $asset->users()->sync($user_ids);
        $asset->projects()->sync($project_ids);

        return response()->json($asset);
    }

    public function get_control_assets()
    {
        
        $control_assets = AssetRecord::with(['users', 'projects'])->get();
        return response()->json($control_assets);
    }
    public function get_assets(Request $request) 
    {
        $projectId = $request->projectId ?? null;
        $memberId = $request->memberId ?? null;
        $classification = $request->classification ?? null;
        $status = $request->status ?? null;
        $assets = AssetRecord::whereHas('users', function ($query) use($memberId) {
            $query->where('users.id', $memberId);
        })
        ->with([
            'users' => function ($query) use($memberId) {
                $query->where('users.id', $memberId);
            },
            'projects' => function ($query) use ($projectId) {
                $query->when($projectId, function ($q) use($projectId) {
                    $q->where('id', $projectId);
                });
            }
        ])->get();
        return response()->json($assets);
    }

    public function delete_asset(Request $request) 
    {
        $request->validate([
            'id' => 'required',
        ]);
        AssetRecord::find($request->id)->delete();
        return response()->json(['message' => 'Successfully deleted']);
    }
}
