<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\officeRecord;
use App\Models\User;

class CommunityController extends Controller
{
    public function get_office_list(Request $request)
    {
        $offices = officeRecord::with('employees')->orderBy('created_at', 'desc')->get();

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
                'tel' => $request->phone, 
                'fax' => $request->fax,
                'post_code_1' => $request->post_code_1, 
                'post_code_2' => $request->post_code_2,
            ] 
        );
        $members = $request->employees ?? [];
        $member_ids = array_map(function($member){
            return $member['id'];
        }, $members);
        User::whereIn('id', $member_ids)->whereNot('office_id', $office->id)->update(['office_id' => $office->id]);
        return response()->json($office);
    }
    public function delete_office_item(Request $request){
        $request->validate([
            'id' => 'required',
        ]);
        $delete = officeRecord::findOrFail($request->id)->delete();
        return response()->json($delete);
    }
}
