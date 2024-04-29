<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
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
use Illuminate\Validation\ValidationException;
use App\Services\SharedService;
use Carbon\Carbon;
class AdminAccountController extends Controller
{
    
    protected $sharedService;
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
    public function get_controllable_users(Request $request){
        $with_users = (int) $request->with_users;
        $ng_list = ['推し', '知人', '家族', '友人', '関係者', 'お知らせアカウント'];
        $user_list = User::with('user_detail')
                            ->with('positions')
                            ->whereNotIn('name', $ng_list)
                            ->with('offices')
                            ->with('work_groups')
                            ->with('linked')
                            ->get();
        $position_list_label = positionRecord::select('id AS value', 'name AS label')->get();
        $office_list_label = officeRecord::select('id AS value', 'name AS label')->get();
        $linkable_accounts = User::where('linkable', 1)->select('id', 'name', 'icon_id')->get();
        $work_groups = workGroup::select('name', 'id')
        ->whereHas('members')
        ->when($with_users, function ($q) {
            $q->with(['members' => function($q) {
                $q->where('users.partner_flag', 0)
                    ->where('users.hide_flag',0)
                    ->where('users.retire', 0)
                    ->orderBy('pivot_authority', 'desc')
                    ->orderBy('users.id', 'desc');
            }]);
        })
        ->get();
        $data = array(
            "u" => $user_list,
            "p" => $position_list_label,
            "o" => $office_list_label,
            "l" => $linkable_accounts,
            "w" => $work_groups
        );

        return response()->json($data);
    }
    function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return '@' . $randomString . '.com';
    }
    public function addUser(Request $request){           
          
        $id = $request->id ? $request->id : '';
        $validatedData = $request->validate([
            'user_params.login' => 'unique:users,login,'. $id,
        ], [
            'user_params.login.unique' => 'このログインIDはすでに登録されています' ,
        ]);
        $user = $request->id ? User::findOrFail($request->id) : new User;
        $user_params = $request->user_params;
        $user->login = $user_params['login'];
        $user->name = $user_params['name'];
        $user->name_kana = $user_params['name_kana'];
        $user->email = $user_params['email'];
        $user->phone_number = $user_params['phone_number'];
        $user->position_id = $user_params['position_id'];
        $user->office_id = $user_params['office_id'];
        $user->partner_flag = $user_params['position_id'] == 14 ? 1 : 0;
        $user->retire = $user_params['retire'];
        if($user_params['retire'] == 1){
            $user->retire_date = Carbon::now()->addMonth();
            $user->login = $user_params['login'] . '_r_' . Carbon::now()->isoFormat('YYYY-MM-DD') . '_' . rand(1000,9999);
            $user->email = $user_params['login'] . Carbon::now()->isoFormat('YYYY-MM-DD') . $this->generateRandomString();
            $user->password = bcrypt('glowd0802');
            $user->hide_flag = 1;
            // $board_to_users = boardToUser::where('user_id', $user->id)->get();
            // if($board_to_users){
            //     $board_to_users->each->delete();
            // }
        }
        if(!$request->id || $request->password_reset){
            $user->password = bcrypt($user_params['password']);
        }
        
        $user->user_code = $user_params['user_code'];
        $user->work_type = $user_params['work_type'];
        $user->on_leave = $user_params['on_leave'];
        $user->work_time_day = $user_params['work_time_day'];
        $user->hide_flag = $user_params['hide_flag'];
        if($user_params['position_id'] == 6){
            $user->work_authority = 1;
        }else{
            $user->work_authority = 0;
        }
        $user->save();
        if(!$request->id){
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
        }
    
            $user->work_groups()->syncWithPivotValues($request->work_groups, ['updated_at' => now()]);
        
        
            $user->linked()->syncWithPivotValues($request->linked, ['updated_at' => now()]);
        
        
        
                
        $arr = [
            "message" => "success",
            "success" => true,
            "data" => $user,
        ]; 
        return response()->json($arr);
    
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
        $work_group = $request->work_group_id ? workGroup::findOrFail($request->work_group_id) : new workGroup;        
        $work_group->name = $request->work_group_name;
        $work_group->save();
        $userIds = array_unique(array_merge($request->work_group_users, $request->work_group_pm));
        $pivotData = collect($userIds)->mapWithKeys(function ($userId) use ($request) {
            $authority = in_array($userId, $request->work_group_pm) ? 1 : 0;
            return [$userId => ['authority' => $authority, 'updated_at' => now()]];
        });

        $work_group->members()->sync($pivotData->toArray());
        return response()->json('success');
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

            $knowledges = KnowledgeRecord::where('deleted_flag', 0)->whereBetween('created_at', [$from, $to])->where('user_id', $var_id)->pluck('id')->toArray();

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
