<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\questionAndAnswerRecord;
use App\Models\qandaTagRecord;
use App\Models\qandaKeyWordRecord;
use App\Models\qandaUseTag;
use App\Models\SupportMailFormRecord;
use App\Models\SupportMailRespondingLog;
use Illuminate\Support\Facades\Auth;

class SupportController extends Controller
{
    public function support_record_list(Request $request){

        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
        $record_list = questionAndAnswerRecord::where('deleted_flag','=', 0)->with(['qanda_use_tags' => function($q){
            $q->where('deleted_flag','=', 0)->with(['qanda_tag_records' => function($q){
                $q->where('deleted_flag','=', 0);
            }]);
        }])->orderBy('created_at', 'desc')->get();


        $tag_list = qandaTagRecord::where('deleted_flag','=', 0)->with(['tags_use_qanda' => function($q){
            $q->where('deleted_flag','=', 0)->count('useful_count');
        }])->orderBy('useful_count', 'desc')->get();
        
        $key_word_list = qandaKeyWordRecord::where('deleted_flag','=', 0)->with(['key_words_use_qanda' => function($q){
            $q->where('deleted_flag','=', 0)->count('useful_count');
        }])->orderBy('useful_count', 'desc')->get();


        $record_dates_array = array("record_list" => $record_list, "tag_list" => $tag_list, "key_word_list" => $key_word_list);
        
        return response()->json($record_dates_array);

    }
    public function support_feedback(Request $request){
        $create = SupportMailFormRecord::create([
            "user_id" => Auth::id(),
            "kind_value" => $request->kind_value,
            "contact_address" => $request->contact_address,
            "consultation_content" => $request->consultation_content,
        ]);
        return response()->json($create);
    }
    public function support_resolve_decision(Request $request){
        $incement = questionAndAnswerRecord::findOrFail($request->id)->increment('useful_count');
        return response()->json($incement);
    }
    public function support_add_consult(Request $request){
        $create = SupportMailFormRecord::create([
            "user_id" => Auth::id(),
            "kind_value" => $request->kind_value,
            "contact_address" => $request->contact_address,
            "consultation_content" => $request->consultation_content,
        ]);
        return response()->json($create);
    }
    public function get_recieved_consults(){
        $record_list = supportMailFormRecord::where('deleted_flag','=', 0)->with('user')
        ->with(['support_mail_responding_logs' => function($q){
            $q->where('deleted_flag','=', 0)->orderBy('created_at', 'desc')->with('user');
        }])->orderBy('created_at', 'desc')->get();
        return response()->json($record_list);
    }
    public function add_memo_to_consult(Request $request){
        $create = SupportMailRespondingLog::create([
            "user_id" => Auth::id(),
            "text" => $request->text,
            "record_id" => $request->record_id,
        ]);
        return response()->json($create);
    }
    public function update_consult_status(Request $request){
        $update = SupportMailFormRecord::findOrFail($request->record_id)->update([
            "status_flag" => $request->value
        ]);
        return response()->json($update);
    }
}
