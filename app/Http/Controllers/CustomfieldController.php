<?php

namespace App\Http\Controllers;

use App\Models\customFieldTypeRecord;
use App\Models\customFieldDataRecord;
use App\Models\shiftRecord;
use App\Models\shiftType;
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
        if(Auth::user()->isAdmin()){
            return response()->json('weekend');
        }
        $shift_record = shiftRecord::where('user_id', $auth_user_id)->where('shift_day', $request->today)->first();
        if ($shift_record && in_array((int) $shift_record->shift_type, shiftType::idsFor(array_merge([shiftType::CATEGORY_ABSENCE, shiftType::CATEGORY_ANNUAL_LEAVE_FULL, shiftType::CATEGORY_DAY_OFF, shiftType::CATEGORY_PLANNED_PAID_LEAVE], shiftType::SPECIAL_LEAVE)), true)) {
            return response()->json('weekend');
        }
        return response()->json($custom_field_data);
    }

    public function saveWeather(Request $request){
        $today = today()->toDateString();
        
        $keys = [
            'user_id'  => Auth::id(),
            'date'     => $today,
            'field_id' => 7,
            'type_id'  => 43,
            'app_name' => 'work',
        ];
        $params = [];
        if ($request->has('value')) {
            $params['value_int'] = (int) $request->value;
        }
        if ($request->has('comment')) {
            $params['value_text'] = $request->comment;
        }
        $record = CustomFieldDataRecord::updateOrCreate(
            $keys,
            $params
        );
        
        $user = $request->user()->fresh()->load([
            'positions','offices','icons',
            'user_album' => fn($q) => $q->where('deleted_flag', 0)->with('tags'),
            'weathers' => fn($q) => $q->where('type_id', 43)->whereDate('date', today()),
            'days_weathers' => fn($q) => $q->where('type_id', 43)->where('deleted_flag', 0)->whereDate('date', '<', today())->latest('date')->limit(5),
            'portfolio','communityMemberships.community','communityMemberships.role',
        ]);
        return response()->json($user);


    }


}
