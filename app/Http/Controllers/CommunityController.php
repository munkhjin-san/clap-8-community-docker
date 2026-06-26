<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\officeRecord;
use App\Models\User;
use App\Models\ProjectRecord;
use App\Models\EvaluationRecord;
use Illuminate\Support\Facades\Auth;

class CommunityController extends Controller
{

    private function active_user(){
        return Auth::user();
    }
    public function get_office_list(Request $request)
    {
        $offices = officeRecord::with(['employees.related_projects:id,name', 'files'])->orderBy('created_at', 'desc')->get();

        return response()->json($offices);
    }

    public function create_office_item(Request $request){
        $request->validate([
            'name' => 'required',
        ]);
        $id = $request->id ?? null; 
        $office = officeRecord::updateOrCreate(
            ['id' => $id], 
            [
                'name' => $request->name, 
                'address' => $request->address, 
                'tel' => $request->tel, 
                'fax' => $request->fax,
                'post_code_1' => $request->post_code_1, 
                'post_code_2' => $request->post_code_2,
            ] 
        );
        $fileIds = $request->file_ids;
        $office->fileAttachments()->createMany(
            array_map(fn ($id) => [
                'file_id' => $id,
                'collection' => 'attachments',
            ], $fileIds)
        );
        $members = $request->employees ?? [];
        $member_ids = array_map(function($member){
            return $member['id'];
        }, $members);
        $currentIds = $office->employees()->pluck('id')->all();
        $toDetach = array_diff($currentIds, $member_ids);
        if (!empty($toDetach)) {
            User::whereIn('id', $toDetach)
                ->update(['office_id' => null]);
        }
        if (!empty($member_ids)) {
            User::whereIn('id', $member_ids)
                ->update(['office_id' => $office->id]);
            // If you only want to touch those not already attached, use $toAttach instead of $payloadIds
        }
        // User::whereIn('id', $member_ids)->whereNot('office_id', $office->id)->update(['office_id' => $office->id]);
        return response()->json($office);
    }
    public function delete_office_item(Request $request){
        $request->validate([
            'id' => 'required',
        ]);
        $delete = officeRecord::findOrFail($request->id)->delete();
        return response()->json($delete);
    }
    public function community_members_tree(Request $request)
    {
        $user = $this->active_user();

        $userQuery = User::query()->where('retire', 0)
        ->where('partner_flag', 0)
        ->whereNotIn('position_id',[13,14,15] )
        ->where('hide_flag', 0);

        switch ($request->by) {
            case 1:

                return response()->json([[
                    "id" => 1,
                    "name" => "全員",
                    "members" => $userQuery->get(),
                ]]);
            case 2:
                $projects = ProjectRecord::with('members:id')
                ->whereHas('manager', fn ($q) => $q->where('users.id', $user->id))   
                ->whereHas('members', fn ($q) => $q->where('retire', 0)
                    ->where('partner_flag', 0)
                    ->whereNotIn('position_id',[13,14,15] )
                    ->where('hide_flag', 0)
                )
                ->get();

                // $memberIds = $projects->flatMap->members->pluck('id')->unique()->values()->all();
                // $members = $userQuery->whereIn('id', $memberIds)->get();
                $data = $projects->map( function ($project) {
                    return [
                        "id" => $project->id,
                        "name" => $project->name,
                        "members" => $project->members()
                            ->where('retire', 0)
                            ->where('partner_flag', 0)
                            ->whereNotIn('position_id',[13,14,15] )
                            ->where('hide_flag', 0)
                            ->get(),
                    ];
                })->all();
                
                return response()->json($data);
            case 3:
                $year = $request->year;
                $which_half = $request->which_half;
                $evaluationRecords = EvaluationRecord::where('year', $year)
                    ->where('which_half', $which_half)
                    ->where('mentor_id', $user->id)
                    ->whereNotNull('user_id')
                    ->pluck('user_id')
                    ->unique()
                    ->values()

                    ->all();
                $mentees = $userQuery->whereIn('id', $evaluationRecords)->get();
                return response()->json([[
                    "id" => 3,
                    "name" => "メンティー",
                    "members" => $mentees,
                ]]); 
            

        }


        return response()->json([]);
    }
}
