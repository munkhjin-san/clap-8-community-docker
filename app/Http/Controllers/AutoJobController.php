<?php

namespace App\Http\Controllers;
use App\Models\boardRecord;
use App\Models\boardToUser;
use App\Models\User;
use App\Models\Icons;
use App\Models\messageRecord;
use App\Models\messageFile;
use App\Models\memoRecord;
use App\Models\appRememberRecord;
use App\Models\searchHistoryRecord;
use App\Models\taskRecord;
use App\Models\Tag;
use App\Events\Message;
use App\Models\tempUser;
use App\Models\PasswordReset;
use App\Mail\Warning;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File; 
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Services\SharedService;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;
class AutoJobController extends Controller

{
    protected $sharedService;
    
    public function __construct(SharedService $sharedService)
    {
        $this->sharedService = $sharedService;
        $this->middleware('throttle:3,1');
    }
    public function removeOldBoards(){
        $time_limit = Carbon::today()->subDays(180);

        $boards = boardRecord::where('private_flag', '!=', 3)->where('last_activity', '<', $time_limit)->get();
        foreach($boards as $board){
            $createIcon = $this->sharedService->removeBoard($board);   
        }
        $time_limit_alert = Carbon::today()->subDays(90);
        $alert_dates = boardRecord::where('private_flag', '!=', 3)->whereDate('last_activity', '=', $time_limit_alert)->get();
        foreach($alert_dates as $board){
            $board_users = $board->board_to_users()->where('deleted_status', 0)->where('member_status', 1)->pluck('user_id')->toArray();
            
            
            $target_users = User::whereIn('id', $board_users)->whereNotNull('email')->get();
            
            foreach($target_users as $target_user){
                // echo($target_user->email);
                if(!empty($target_user->email)){
                    $to = $target_user->email;
                    $lang = empty($target_user->language) ? 'en' : $target_user->language;
                    $chat_title = '';
                    if($board->private_flag == 0){
                        $chat_title = $board->title;
                    }else if($board->private_flag == 1){
                        $get_user_name = $board->board_to_users()->where('user_id', '!=', $target_user->id)->whereHas('user')->first();
                        if(empty($get_user_name)){
                            $unavialabe_users = [
                                "en" => "Unavailable user",
                                "mn" => "Идэвхгүй хэрэглэгч",
                                "ja" => "非アクティブユーザー"
                            ];
                            $chat_title = $unavialabe_users[$lang];
                        }else{
                            $chat_title = $get_user_name->user->name;
                        }
                    }
                    $last_act = $lang == 'en' ? Carbon::parse($board->last_activity)->isoFormat('MMMM Do YYYY') : Carbon::parse($board->last_activity)->isoFormat('YYYY-MM-DD');
                    $deletion_date = $lang == 'en' ? Carbon::parse($board->last_activity)->addDays(180)->isoFormat('MMMM Do YYYY') : Carbon::parse($board->last_activity)->addDays(180)->isoFormat('YYYY-MM-DD');
                 
                    Mail::to($to)
                    ->send(new Warning(
                        $board->id,
                        $chat_title, 
                        $last_act, 
                        $deletion_date,
                        $lang, 
                        'chat_deletion_warning'
                    ));
                }           
            
                        
                
                   
            }
        }
        return;

    }
    public function cronTest(){
        $faker = Faker::create();
        $newTag = new Tag;
        $newTag->name = $faker->word;
        $newTag->save();
        return 'success';
    }
    public function removeOldFiles(){
        $time_limit = Carbon::today()->subDays(90);
        $files = messageFile::where('created_at', '<', $time_limit)->get();
        if($files){
            foreach($files as $file){
                if($file->mime_type == 'image'){
                    $path1 = 'message_files/' . $file->board_id . '/thumbs/' . $file->id . '_' . $file->user_id . '_' . $file->message_id . '_50.' . $file->extension;
                    Storage::disk('s3')->delete($path1);
                    $path2 = 'message_files/' . $file->board_id . '/thumbs/' . $file->id . '_' . $file->user_id . '_' . $file->message_id . '_100.' . $file->extension;
                    Storage::disk('s3')->delete($path2);
                }
                $path = 'message_files/' . $file->board_id . '/' . $file->id . '_' . $file->user_id . '_' . $file->message_id . '.' . $file->extension;
                Storage::disk('s3')->delete($path);
                $file->update(['removed_at' => now()]);
            } 
        }
        
    }
    public function removeTempUsers(){
        tempUser::where('created_at', '<', Carbon::now()->subDay())->delete();
    }
    public function removePasswordResets(){
        PasswordReset::where('created_at', '<', Carbon::now()->subDay())->delete();
    }
}
