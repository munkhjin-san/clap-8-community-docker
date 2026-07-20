<?php

namespace App\Http\Controllers;

use App\Models\ProjectRecord;
use DB;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\boardRecord;
use App\Models\boardToUser;
use App\Models\positionRecord;
use App\Models\officeRecord;
use App\Models\workGroup;
use App\Models\workGroupUser;
use App\Models\userDetail;
use App\Models\UserLeaveRecord;
use App\Models\CommunityRole;
use App\Services\Community\CommunityContext;
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
                            ->inActiveCommunity()
                            ->get();
        $position_list_label = positionRecord::select('name', 'id')->orderBy('sort_flag', 'asc')->get();
        $office_list_label = officeRecord::select('name', 'id')->get();
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
        $projects = ProjectRecord::select('name', 'id')
        ->whereHas('members')
        ->orWhereHas('manager')
        ->get();

        // Attach each user's community role (for the active community) and the
        // selectable role list, so the user editor can assign roles.
        $activeCommunityId = app(CommunityContext::class)->communityId();
        $roleByUser = $activeCommunityId
            ? DB::table('community_user')->where('community_id', $activeCommunityId)->pluck('community_role_id', 'user_id')
            : collect();
        $user_list->each(function ($user) use ($roleByUser) {
            $user->community_role_id = $roleByUser[$user->id] ?? null;
        });
        $roles = $activeCommunityId
            ? CommunityRole::where('community_id', $activeCommunityId)->orderBy('sort_order')->get(['id', 'key', 'name'])
            : collect();

        $data = [
            "u" => $user_list,
            "p" => $position_list_label,
            "o" => $office_list_label,
            "l" => [],
            "w" => $projects,
            "r" => $roles,
        ];

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
        $request->validate([
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
        $user->joined_date = $user_params['joined_date'];
        $user->work_time_day = $user_params['work_time_day'];
        $user->hide_flag = $user_params['hide_flag'];
        if($user_params['position_id'] == 6){
            $user->work_authority = 1;
        }else{
            $user->work_authority = 0;
        }
        $user->save();
        if($user->on_leave === 1){
            UserLeaveRecord::create(
                ['user_id' => $user->id,
                'leave_start' => Carbon::now()->isoFormat('YYYY-MM-DD'),
                'active' => 1],
            );
        }else{
            $userleave = UserLeaveRecord::where("user_id", $user->id)->where('active', 1)->first();
            if ($userleave && $userleave->leave_start) {
                $userleave->update([
                    "leave_end" => Carbon::now()->isoFormat('YYYY-MM-DD'),
                    "active" => 2
                ]);
            }
        }
        
        if(!$request->id){
            // try {
            //     $createIcon = $this->sharedService->createUserDefaultIcon($user);             
                
            //     if ($createIcon) {
            //         $user->save();
            //     } else {
            //         $user->delete();
            //         throw ValidationException::withMessages(['message' => 'Icon create failed.']);
            //     }   
            // } catch (\Exception $e) {           
            //     $user->delete();       
            //     throw ValidationException::withMessages(['message' => 'Icon create failed.']);
            // }  
            $board = new boardRecord;
            $board->user_id = $user->id;
            $board->title = 'マイチャット';
            $board->private_flag = 3;
            $board->save();

            $self = new boardToUser;
            $self->record_id = $board->id;
            $self->user_id = $user->id;
            $self->admin_flag = 1;
            $self->save();
        }
    
            $user->work_groups()->syncWithPivotValues($request->work_groups, ['updated_at' => now()]);

        $this->assignCommunityRole($user, $user_params['community_role_id'] ?? null);

        $arr = [
            "message" => "success",
            "success" => true,
            "data" => $user,
        ];
        return response()->json($arr);

    }

    /**
     * Assign a user to a community role within the active community.
     * Creates the membership if missing; protects the last admin.
     */
    private function assignCommunityRole(User $user, $roleId): void
    {
        $communityId = app(CommunityContext::class)->communityId();
        if (!$communityId || !$roleId) {
            return;
        }

        $role = CommunityRole::where('community_id', $communityId)->where('id', (int) $roleId)->first();
        if (!$role) {
            return;
        }

        $existing = DB::table('community_user')
            ->where('community_id', $communityId)
            ->where('user_id', $user->id)
            ->first();

        $currentRole = $existing ? CommunityRole::find($existing->community_role_id) : null;
        if ($currentRole?->key === 'admin' && $role->key !== 'admin') {
            $adminRoleIds = CommunityRole::where('community_id', $communityId)->where('key', 'admin')->pluck('id');
            $adminCount = DB::table('community_user')
                ->where('community_id', $communityId)
                ->whereIn('community_role_id', $adminRoleIds)
                ->count();
            if ($adminCount <= 1) {
                throw ValidationException::withMessages([
                    'community_role_id' => '管理者ロールには最低1名のメンバーが必要です。',
                ]);
            }
        }

        if ($existing) {
            DB::table('community_user')->where('id', $existing->id)->update([
                'community_role_id' => $role->id,
                'updated_at' => now(),
            ]);
        } else {
            DB::table('community_user')->insert([
                'community_id' => $communityId,
                'user_id' => $user->id,
                'community_role_id' => $role->id,
                'scope' => (int) ($user->partner_flag ?? 0) === 1 ? 'partner' : 'internal',
                'is_default' => true,
                'last_active_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function workgroupDelete(Request $request){
        $work_group = workGroup::findOrFail($request->work_group_id);
        if($work_group){
            workGroupUser::where('record_id', $work_group->id)->delete();
            $work_group->delete();
        }

        return response('success', 200);    
    }
    public function workgroupAdd(Request $request){
        $work_group = $request->work_group_id ? workGroup::findOrFail($request->work_group_id) : new workGroup;        
        $work_group->name = $request->work_group_name;
        $work_group->save();
        $userIds = array_unique(array_merge($request->work_group_users, (array) $request->work_group_pm));
        $pivotData = collect($userIds)->mapWithKeys(function ($userId) use ($request) {
            $authority = $userId === $request->work_group_pm ? 1 : 0;
            return [$userId => ['authority' => $authority, 'updated_at' => now()]];
        });

        $work_group->members()->sync($pivotData->toArray());
        return response()->json('success');
    }
    public function clap_statistics(Request $request) {      
        $ng_list = ['推し', '知人', '家族', '友人', '関係者', 'お知らせアカウント'];
        $from = date($request->start . ' 00:00:00');
        $to = date($request->end . ' 23:59:59');

        $all_users = User::where('deleted_flag', 0)
            ->where('hide_flag', 0)
            ->where('partner_flag', 0)
            ->whereNotIn('name', $ng_list)
            ->with([
                'portfolio' => function ($q) use ($from, $to) {
                    $q->whereBetween('created_at', [$from, $to])->withCount('claps');
                },
                'post' => function ($q) use ($from, $to) {
                    $q->whereBetween('created_at', [$from, $to])->withCount('claps');
                },
                'post_recieved' => function ($q) use ($from, $to) {
                    $q->whereBetween('post_records.created_at', [$from, $to])->withCount('claps');
                },
                'comment' => function ($q) use ($from, $to) {
                    $q->whereBetween('created_at', [$from, $to])->withCount('claps');
                },
                'post_entries' => function ($q) use ($from, $to) {
                    $q->whereBetween('created_at', [$from, $to])->withCount('claps');
                }
            ])
            ->select('id', 'name')
            ->get();

        $clap_data = [];

        $all_users->each(function ($user) use (&$clap_data) {

            $challengeclaps = $user->post_recieved->where('app_type', 2)->sum('claps_count');

            $knowledgeclaps = $user->post->where('app_type', 1)->sum('claps_count');

            $deltanicesent = $user->post->where('app_type', 0)->sum('claps_count');

            $deltanicereceived = $user->post_recieved->where('app_type', 0)->sum('claps_count');

            $deltanicetotal = $deltanicesent + $deltanicereceived;

            $posttotal = $challengeclaps + $knowledgeclaps + $deltanicetotal + $user->post_entries->sum('claps_count');      

            $portfolio_claps = $user->portfolio->sum('claps_count');

            $comment_claps = $user->comment->sum('claps_count');

            $sum = $posttotal + $portfolio_claps + $comment_claps ;

            $claps = [
                "delta_challaenge" => $challengeclaps,
                "delta_knowledge" => $knowledgeclaps,
                "delta_nice_sent" => $deltanicesent,
                "delta_nice_received" => $deltanicereceived,
                "post" => $posttotal,
                "portfolio" => $portfolio_claps,
                "comment" => $comment_claps,
                "sum" => $sum,
                "name" => $user->name,
                "id" => $user->id
            ];
            $clap_data[] = $claps;
        });

        return response()->json($clap_data);

    }
    public function getMonthlyPrizes(Request $request)
    {   
        $year = $request->year ?? now()->year;
        $ng_list = ['推し', '知人', '家族', '友人', '関係者', 'お知らせアカウント'];
        $users = User::with(['task_users' => function ($q) use($year) {
                    $q->select('user_id',
                                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                                DB::raw("SUM(prize) as total_prize")
                            )
                            ->whereYear('created_at', $year)
                            ->where('glowd_nine', 1)
                            ->where('prize', '>', 0)
                            ->groupBy('user_id', 'month');
                },'custom_form_users' => function ($q) use($year) {
                    $q->select('user_id',
                                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                                DB::raw("SUM(prize) as total_prize")
                            )
                            ->where('prize', '>', 0)
                            ->whereYear('created_at', $year)
                            ->groupBy('user_id', 'month');
                },'relay_prizes' => function ($q) use($year) {
                    $q->select('user_id',
                                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                                DB::raw("SUM(prize) as total_prize")
                            )
                            ->where('prize', '>', 0)
                            ->whereYear('created_at', $year)
                            ->groupBy('user_id', 'month');
                }])
                ->whereNotIn('name', $ng_list)
                ->where('retire', 0)
                ->where('hide_flag', 0)
                ->where('deleted_flag', 0)
                ->where('partner_flag', 0)
                ->select('id', 'name', 'icon_path', 'icon_bg')
                ->get();
       
        return response()->json($users);
    }
}
