<?php
declare(strict_types=1);

namespace App\Http\Controllers;
use DB;


use App\Models\boardRecord;
use App\Models\boardToUser;
use App\Models\User;
use App\Models\Icons;
use App\Models\messageRecord;
use App\Events\Message;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class NotificationController extends Controller
{
    public function index(Request $request){
        $allUsers = User::select('id')->get();
        $last_nc = niceRecord::latest('created_at')->first();
        $last_kn = knowledgeRecord::latest('created_at')->first();
        $last_ch = challengeRecord::latest('created_at')->first();
        if($last_nc && $last_kn && $last_ch){
            if(!empty($allUsers)){
                foreach($allUsers as $user){
                    $old = userLastRecord::where('user_id', '=', $user->id)->first();
                    if($old){
                        $old->last_knowledge = $last_kn->id;
                        $old->last_challenge = $last_ch->id;
                        $old->last_nice = $last_nc->id;
                        $old->save();
                    }else{
                        $board = new userLastRecord;
                        $board->user_id = $user->id;   
                        $board->last_knowledge = $last_kn->id;
                        $board->last_challenge = $last_ch->id;
                        $board->last_nice = $last_nc->id;             
                        $board->save();
                    }
                    echo'<pre>';
                    echo('success');
                    echo'</pre>';
                }  
            }
        }
        
    }    
    public function getNotify(Request $request){
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
        $result = [];
        if(!empty($auth_user_id)){
            $list = userLastRecord::where('user_id', '=', $auth_user_id)->first();
            $kn = knowledgeRecord::latest('created_at')->first();
            $kn_from = $list->last_knowledge;            
            $kn_to = $kn->id;
            $kn_difference = knowledgeRecord::whereBetween('id', [$kn_from, $kn_to])->count(); 
            if($kn_difference > 0){
                $kn_difference = $kn_difference - 1;
            }
            $result['knowledge'] =  $kn_difference;

            
            $nc = niceRecord::latest('created_at')->first();
            $nc_from = $list->last_nice;            
            $nc_to = $nc->id;
            $nc_difference = niceRecord::whereBetween('id', [$nc_from, $nc_to])->count(); 
            if($nc_difference > 0){
                $nc_difference = $nc_difference - 1;
            }
            $result['nice'] =  $nc_difference;

            $ch = challengeRecord::latest('created_at')->first();
            $ch_from = $list->last_challenge;            
            $ch_to = $ch->id;
            $ch_difference = challengeRecord::whereBetween('id', [$ch_from, $ch_to])->count(); 
            if($ch_difference > 0){
                $ch_difference = $ch_difference - 1;
            }
            $result['challenge'] =  $ch_difference;

            return response()->json($result);
        }
        
    }
    public function updateNotify(Request $request){
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
        if(!empty($auth_user_id)){
            // $last_update = userLastRecord::where('user_id', '=', $auth_user_id)->first();
            
            $last_update = userLastRecord::firstOrCreate(
                ['user_id' => $auth_user_id]
            );
            
            if($request->which == 'knowledge'){
                $kn = knowledgeRecord::latest('created_at')->first();
                if(!empty($kn)){
                   
                    if(!empty($last_update)){
                        $last_update->last_knowledge = $kn->id;
                        $last_update->save();
                    }
                }
                return response()->json($kn->id);

            }elseif($request->which == 'nice'){
                $nc = niceRecord::latest('created_at')->first();
                
                if(!empty($nc)){
                    
                        $last_update->last_nice = $nc->id;
                        $last_update->save();
                    
                }
                return response()->json($nc->id);

            }elseif($request->which == 'challenge'){
                $ch = challengeRecord::latest('created_at')->first();
                if(!empty($ch)){
                   
                    
                        $last_update->last_challenge = $ch->id;
                        $last_update->save();
                    
                }
                return response()->json($ch->id);

            }
            
        }
        
    }      
}
