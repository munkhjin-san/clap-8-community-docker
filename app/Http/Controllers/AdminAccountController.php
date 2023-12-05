<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\userDetail;
use App\Models\Icons;
use App\Models\boardRecord;
use App\Models\boardToUser;
use App\Models\positionRecord;
use App\Models\officeRecord;
use App\Models\workGroup;
use App\Models\workGroupUser;
use App\Models\NiceRecord;
use App\Models\KnowledgeRecord;
use App\Models\ChallengeRecord;
use App\Models\ClapRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File; 
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Services\SharedService;
class AdminAccountController extends Controller
{
    public function __construct(SharedService $sharedService)
    {
        $this->sharedService = $sharedService;
    }
    public function index(Request $request){
        $accessable = [ 1, 2, 765 ];
        if (in_array(Auth::id(), $accessable)) {
            return view('adminAccount');
        }
        else{
            return redirect('home');  
        }
        
    }
    public function getUserList(Request $request){
        $ng_list = ['推し', '知人', '家族', '友人', '関係者', 'お知らせアカウント'];
        $user_list = User::with('user_detail')->with('positions')->whereNotIn('name', $ng_list)->with('offices')->with('work_group_user')->get();
        $position_list = positionRecord::with(['employees' => function($q){
                            $q->with('icons')->select('id', 'name', 'position_id', 'icon_id');
                        }])
                        ->get();
        $position_list_label = positionRecord::select('id AS value', 'name AS label')->get();
        $office_list = officeRecord::with(['employees' => function($q) {
                            $q->select('id', 'name', 'office_id');
                        }])->where('deleted_at', null)->get();
        $office_list_label = officeRecord::select('id AS value', 'name AS label')->get();
        $work_group_with_user = workGroup::with('work_group_user.user')->get();
        $work_group = workGroup::select('id AS value', 'name AS label')->get();

        $data = [
            "user_list" => $user_list,
            "position_list" => $position_list,
            "position_list_label" => $position_list_label,
            "office_list" => $office_list,
            "office_list_label" => $office_list_label,
            "work_group" => $work_group,
            "work_group_users" => $work_group_with_user
        ];

        return response()->json(
            $data
        );
    }
    public function addUser(Request $request){
        // $auth_user = Auth::user();
        // $user->id = Auth::id();
        $validatedData = $request->validate(
            ['user_login' => 'unique:users,login,'],
            ['user_login.unique' => 'このログインIDはすでに登録されています']
        );
        $root_path = base_path();
        $replaced = Str::replaceLast('public_html/', '', Str::replaceLast('app', '', $root_path));

            $user = new User;
            $user->login = $request->user_login;
            $user->name = $request->user_name;
            $user->name_kana = $request->user_kana;
            $user->email = $request->user_email;
            $user->phone_number = $request->user_phone;
            $user->position_id = $request->user_positions;
            $user->office_id = $request->user_offices;
            $user->partner_flag = $request->user_partner_flag;
            $user->password = bcrypt($request->user_password);
            $user->user_code = $request->user_code;
            $user->work_type = $request->user_work_type;
            $user->work_time_day = $request->user_work_time_day;
            $user->award_charge = $request->user_award_charge;
            $user->hide_flag = $request->user_member_show;
            if($request->user_positions == 6){
                $user->work_authority = 1;
            }else{
                $user->work_authority = 0;
            }
            $user->save();

            $board = new boardRecord;
            $board->user_id = $user->id;
            $board->title = 'マイボード';
            $board->private_flag = 3;
            $board->save();

            $self = new boardToUser;
            $self->record_id = $board->id;
            $self->user_id = $user->id;
            $self->admin_flag = 1;
            $self->save();

            $user_detail = new userDetail;
            $user_detail->memo = $request->user_memo;
            $user_detail->user_id = $user->id;
            $user_detail->save();

            foreach($request->user_work_group as $workgroup){
                $work_group_user = new workGroupUser;
                $work_group_user->record_id = $workgroup['value'];
                $work_group_user->user_id = $user->id;
                $work_group_user->save();
            }

            try {
                $createIcon = $this->sharedService->createUserDefaultIcon($user);             
               
                if ($createIcon) {
                    $user->save();
                } else {
                    $user->delete();
                    throw ValidationException::withMessages(['message' => 'Icon create failed.']);
                }   
            } catch (\Exception $e) {           
                $user->delete();       
                throw ValidationException::withMessages(['message' => 'Icon create failed.']);
            }        
            $arr = [
                "message" => "success",
                "success" => true,
                "data" => $user,
                "detail" => $user_detail
            ]; 
            return response()->json($arr);
        
        }

    public function deleteUser(Request $request){
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
          
        if(empty($auth_user_id)){               
            return response()->json("loggedOut");
        }
        if(!empty($request->id)){

            $user = User::where('id', $request->id)->first();
            $user->delete();
            $user->save();

        }

        return response()->json();
    }
    public function editUser(Request $request){

        $user = User::where('id', $request->user_id)->first();
        $user->login = $request->user_login;
        $user->name = $request->user_name;
        $user->name_kana = $request->user_kana;
        $user->email = $request->user_email;
        $user->phone_number = $request->user_phone;
        $user->position_id = $request->user_positions;
        $user->office_id = $request->user_offices;
        $user->partner_flag = $request->user_partner_flag;
        $user->user_code = $request->user_code;
        $user->work_type = $request->user_work_type;
        $user->work_time_day = $request->user_work_time_day;
        $user->hide_flag = $request->user_member_show;
        if($request->user_positions == 6){
            $user->work_authority = 1;
        }else{
            $user->work_authority = 0;
        }
        if($request->user_retire == 1){
            $user->retire = $request->user_retire;
            if($request->user_email){
                $user->email = $request->user_email . '_r_' . date("Ymd") . '_' . rand(1000,9999);
            }   
            $user->login = $request->user_login . '_r_' . date("Ymd") . '_' . rand(1000,9999);
            $user->password = bcrypt('glowd0802');
            $user->hide_flag = 1;
        }else{
            if($request->user_password){
                $user->password = bcrypt($request->user_password);
            }
            $user->retire = $request->user_retire;
        }
        $user->save();
        
        
        $user_detail = userDetail::where('user_id', $request->user_id)->first();
        if($user_detail){
            $user_detail->memo = $request->user_memo;
            $user_detail->user_id = $user->id;
            $user_detail->save();
        }else{
            $user_detail = new userDetail;
            $user_detail->memo = $request->user_memo;
            $user_detail->user_id = $user->id;
            $user_detail->save();
        }

        if(!empty($request->user_work_group)){
            $current_work_group = workGroupUser::where('user_id', '=', $user->id)->get();
            foreach($current_work_group as $current){
                $current->deleted_flag = 1;
                $current->save();
            }
            foreach($request->user_work_group as $workgroup){
                $workgroup_id = $workgroup['value'];
                $update_work_group = workGroupUser::where('record_id', '=', $workgroup_id)->where('user_id', '=', $user->id)->first();
                if(empty($update_work_group)){
                    $workgroupuser = new workGroupUser;
                    $workgroupuser->record_id = $workgroup['value'];
                    $workgroupuser->user_id = $user->id;
                    $workgroupuser->save();
                }else{
                    $update_work_group->deleted_flag = 0;
                    $update_work_group->save();
                }
            }
        }else{
            $current_work_group = workGroupUser::where('user_id', '=', $user->id)->get();
            foreach($current_work_group as $current){
                $current->deleted_flag = 1;
                $current->save();
            }
        }

                
        $arr = [
            "message" => "success",
            "success" => true,
            "data" => $user,
            
        ]; 
        return response()->json($arr);
    }
    public function workgroupSort(Request $request){
        foreach($request->new_list as $newworkgroup){
            foreach($newworkgroup as $newworkgroup_user){
                $work_group_user = workGroupUser::findOrFail($newworkgroup_user['id']);
                $work_group_user->record_id = $newworkgroup_user['record_id'];
                $work_group_user->save();
            }
        }
        return response()->json('success');
    }
    public function workgroupDelete(Request $request){
        $work_group = workGroup::findOrFail($request->work_group_id);
        if($work_group){
            $work_group_user = workGroupUser::where('record_id', $work_group->id)->get();
            $work_group_user->each->delete();
            $work_group->delete();
        }

        return 'deleted';
    }
    public function workgroupAdd(Request $request){

        $user_id = Auth::id();

        $work_group = new workGroup;
        $work_group->name = $request->work_group_name;
        $work_group->user_id = $user_id;
        $work_group->save();
        if(!empty($request->work_group_users)){
            foreach($request->work_group_users as $workgroup){
                $user_id = $workgroup['value'];
                $workgroupuser = new workGroupUser;
                $workgroupuser->record_id = $work_group->id;
                $workgroupuser->user_id = $user_id;
                $workgroupuser->save();
            }
        }
        
        

        $arr = [
            "message" => "success",
            "success" => true,
            "data" => $work_group,
        ]; 
        return response()->json($arr);
    }
    public function workgroupEdit(Request $request){
        $work_group = workGroup::where('id', $request->work_group_id)->first();
        $work_group->name = $request->work_group_name;
       
        if(!empty($request->work_group_users)){
            $current_work_group = workGroupUser::where('record_id', '=', $work_group->id)->get();
            $current_work_group->each->delete();
            foreach($request->work_group_users as $workgroup){
                $user_id = $workgroup['value'];
                $update_work_group = workGroupUser::where('record_id', '=', $work_group->id)->where('user_id', '=', $user_id)->first();
                if(empty($update_work_group)){
                    $workgroupuser = new workGroupUser;
                    $workgroupuser->record_id = $work_group->id;
                    $workgroupuser->user_id = $workgroup['value'];
                    $workgroupuser->save();
                }else{
                    $update_work_group->deleted_at = null;
                    $update_work_group->save();
                }
            }
        }else{
            $current_work_group = workGroupUser::where('record_id', '=', $work_group->id)->get();
            $current_work_group->each->delete();
        }

        $work_group->save();

        return response()->json('success');
    }
    public function addOffice(Request $request){

        $office = new officeRecord;
        $office->name = $request->office_name;
        $office->post_code_1 = $request->office_code1;
        $office->post_code_2 = $request->office_code2;
        $office->address = $request->office_address;
        $office->tel = $request->office_tel;
        $office->fax = $request->office_fax;

        $office->save();

        return response()->json(
            'success'
        );
    }

    public function editOffice(Request $request){

        $office = officeRecord::where('id', $request->office_id)->first();
        $office->name = $request->office_name;
        $office->post_code_1 = $request->office_code1;
        $office->post_code_2 = $request->office_code2;
        $office->address = $request->office_address;
        $office->tel = $request->office_tel;
        $office->fax = $request->office_fax;

        $office->save();

        return response()->json(
            'success'
        );
    }
    public function deleteOffice(Request $request){
        $office = officeRecord::findOrFail($request->office_id);
        if($office){
            $office->delete();
        }

        return 'deleted';
    }
    public function clap_statistics(Request $request) {      
        $ng_list = ['推し', '知人', '家族', '友人', '関係者', 'お知らせアカウント'];  
        $all_users = User::where('deleted_flag', '=', 0)
        ->where('hide_flag', '=', 0)
        ->where('partner_flag', '=', 0)
        ->whereNotIn('name',  $ng_list)
        ->select('id', 'name')
        ->get();
        $clap_data = [];
        $claps = [];
        $from = date($request->start . ' 00:00:00');
        $to = date($request->end . ' 23:59:59');

        
        foreach($all_users as $user){
            $var_id = $user->id;

            $nice_from = $niceFrom = NiceRecord::where('deleted_flag', '=', 0)->whereBetween('created_at', [$from, $to])->where('user_id', '=', $var_id)->pluck('id')->toArray();
            // return $nice_from;

            $nice_to = NiceRecord::where('deleted_flag', '=', 0)->whereBetween('created_at', [$from, $to])->whereHas('to_users', function($q) use ($var_id){
                $q->where('user_id', $var_id);
            })->pluck('id')->toArray();

            $merged = array_merge(array_diff($nice_from, $nice_to), array_diff($nice_to, $nice_from));

            $knowledges = KnowledgeRecord::where('deleted_flag', 0)->where('user_id', $var_id)->pluck('id')->toArray();

            $challenges = ChallengeRecord::where('deleted_flag', 0)->whereBetween('created_at', [$from, $to])->whereHas('to_users', function($q) use ($var_id){
                $q->where('user_id', $var_id);
            })->pluck('id')->toArray();

            $knowledge_claps = ClapRecord::where('deleted_flag', 0)->where('app_name', 'knowledge')->whereIn('record_id', $knowledges)->count();

            $challenge_claps = ClapRecord::where('deleted_flag', 0)->where('app_name', 'challenge')->whereIn('record_id', $challenges)->count();

            $nice_from_claps = ClapRecord::where('deleted_flag', 0)->where('app_name', 'nice')->whereIn('record_id', $merged)->count();

            $sum = $nice_from_claps + $challenge_claps + $knowledge_claps;

            $claps = [
                "nice" => $nice_from_claps,
                "challenge" => $challenge_claps,
                "knowledge" => $knowledge_claps,
                "sum" => $sum,
                "name" => $user->name,
                "id" => $user->id
            ];
            $clap_data[] = $claps; 

        }

        return response()->json($clap_data);

    }
    //
}
