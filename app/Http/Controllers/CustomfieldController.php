<?php

namespace App\Http\Controllers;

use App\Models\customFieldTypeRecord;
use App\Models\customFieldDataRecord;
use App\Models\shiftRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CustomfieldController extends Controller{

   

    public function index(){

        return;

    }


    public function customFieldRecordListMessage(Request $request){

        if( !empty($request->app_name) ){
            $custom_field_record = customFieldTypeRecord::where('use_flag', 1)->with(['custom_field_parts_records' => function($q){
                $q->where('use_flag', 1)->with('sub_parts');
            }])->orderBy('sort_flag', 'asc')->get();

        }

        return response()->json($custom_field_record);

    }

    public function getTodayWeather(Request $request){
        $auth_user_id = Auth::id();
        $custom_field_data = customFieldDataRecord::where('user_id', $auth_user_id)->where('date', $request->today)->where('type_id', 43)->where('deleted_flag', 0)->first();
        if($auth_user_id == 608 || $auth_user_id == 610){
            return response()->json('weekend');
        }
        $shift_record = shiftRecord::where('user_id', $auth_user_id)->where('shift_day', $request->today)->first();
        if ($shift_record && in_array($shift_record->shift_type, [2, 5, 0, 14, 15, 3])) {
            return response()->json('weekend');
        }
        return response()->json($custom_field_data);
    }

    public function saveWeather(Request $request){
        $today = Carbon::now()->format('Y-m-d');
        $exists = customFieldDataRecord::where('user_id', Auth::id())->where('date', $today)->where('field_id', 7)->where('type_id', 43)->where('app_name', 'work')->get();
        
        if(count($exists)){
            foreach($exists as $exist){
                $exist->update(['value_int' => $request->value]);
            }            
            return response()->json($exists);
        }else{
            $auth_user_id = Auth::id();
            $custom_field_data = new customFieldDataRecord;
            $custom_field_data->field_id = 7;
            $custom_field_data->type_id = 43;
            $custom_field_data->app_name = 'work';
            $custom_field_data->date = $today;
            $custom_field_data->user_id = $auth_user_id;
            $custom_field_data->value_int = $request->value;
            $custom_field_data->save();
            return response()->json($custom_field_data);
        }


    }


}