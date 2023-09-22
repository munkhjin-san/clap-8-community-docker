<?php

namespace App\Http\Controllers;

use App\Models\customFieldRecord;
use App\Models\customFieldTypeRecord;
use App\Models\customFieldPartsRecord;
use App\Models\customFieldDataRecord;
use App\Models\shiftRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomfieldController extends Controller{

   

    public function index(){
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();

        $custtom_field_record = customFieldRecord::where('deleted_flag','=', 0)->where('use_flag', '=', 1)->with(['custom_field_type_records' => function($q){
            $q->where('deleted_flag','=', 0)->with(['custom_field_parts_records' => function($q){
                $q->where('deleted_flag','=', 0);
            }]);
        }])->orderBy('created_at', 'desc')->get();

        return;

    }


    public function customFieldRecordListMessage(Request $request){

        $auth_user = Auth::user();
        $auth_user_id = Auth::id();

        if( !empty($request->app_name) ){
            $custtom_field_record = customFieldRecord::where('use_flag', '=', 1)->with(['custom_field_type_records' => function($q){
                $q->where('use_flag', 1)->with(['custom_field_parts_records'])->orderBy('created_at', 'desc');
            }])->orderBy('created_at', 'desc')->get();

        }

        return response()->json($custtom_field_record);

    }

    public function getTodayWeather(Request $request){
        $auth_user_id = Auth::id();
        $custom_field_data = customFieldDataRecord::where('user_id', $auth_user_id)->where('date', $request->today)->where('type_id', 43)->where('deleted_flag', 0)->first();
        $shift_record = shiftRecord::where('user_id', $auth_user_id)->where('shift_day', $request->today)->first();
        if($shift_record !==null && ($shift_record->shift_type == 2 || $shift_record->shift_type == 5 || $shift_record->shift_type == 0 || $shift_record->shift_type == 14 || $shift_record->shift_type == 15)){
            return response()->json('weekend');
        }
        return response()->json($custom_field_data);
    }

    public function saveWeather(Request $request){
        $auth_user_id = Auth::id();
        $custom_field_data = new customFieldDataRecord;
        $custom_field_data->field_id = 7;
        $custom_field_data->type_id = 43;
        $custom_field_data->app_name = 'work';
        $custom_field_data->date = $request->today;
        $custom_field_data->user_id = $auth_user_id;
        $custom_field_data->value_int = $request->value;
        $custom_field_data->save();
        return response()->json($custom_field_data);
    }


}