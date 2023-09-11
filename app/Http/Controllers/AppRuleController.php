<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\appRule;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class AppRuleController extends Controller
{
    public function getRule(Request $request){
        $rule_list = appRule::with('user')->with('position')->get();
        $user_list = User::select('id','name')->get();
        $column_names = Schema::getColumnListing('app_rules');
        
        $c_list = [];
        foreach($column_names as $column){
            if($column !== 'id' && $column !== 'user_id' && $column !== 'title' && $column !== 'created_at' && $column !== 'updated_at'){
                $c_list[] = $column;
            }
        }
        $data = [
            "rule_list" => $rule_list,
            "column_names" => $c_list,
            "user_list" => $user_list
        ];

        return response()->json(
            $data
        );
    }

    public function addRule(Request $request){
        $auth_user = Auth::user();
        $user_id = Auth::id();

        $rule = new appRule;
        $rule->user_id = $user_id;
        $rule->title = $request->rule_title;
        foreach($request->rule_checked as $value) {
            $rule[$value['name']] = $value['value'];
            // foreach($column_names as $name){
            //     if($value == $name){
            //         $rule->$value = 1;
            //     }
            // }
        }
        $rule->save();

        $arr = [
            "message" => "success",
            "success" => true,
            "data" => $rule,
        ]; 
        return response()->json($arr);
    }

    public function editRule(Request $request){
        $column_names = Schema::getColumnListing('app_rules');

        $rule = appRule::where('id', $request->rule_id)->first();
        $rule->user_id = $request->rule_user_id;
        $rule->title = $request->rule_title;
        foreach($request->rule_checked as $value) {
            $rule[$value['name']] = $value['value'];
        }
        $rule->save();
        $arr = [
            "message" => "success",
            "success" => true,
            "data" => $rule,
        ]; 
        return response()->json($arr);
    }
}
