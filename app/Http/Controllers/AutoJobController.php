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
use App\Models\NoticeFiles;
use App\Models\NoticeRecords;
use App\Models\AppFileRecord;
use App\Models\taskUser;
use App\Models\shiftRecord;
use App\Models\shiftType;
use App\Models\FileRecord;
use App\Models\UserAlbum;
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
use App\Jobs\GenerateThumbnailJob;
use App\Jobs\GeneratePostThumbnail;
class AutoJobController extends Controller

{
    protected $sharedService;
    
    public function __construct(SharedService $sharedService)
    {
        $this->sharedService = $sharedService;
        // $this->middleware('throttle:3,1');
    }
    public function update_last_act(){

        // $users = boardToUser::whereNotNull('user_id')->get();
        // foreach($users as $user){
        //     $user->timestamps = false; // Disable timestamp updates
        //     $user->update(['last_act' => $user->updated_at]);
        //     $user->timestamps = true; // Re-enable timestamp updates
        // }
        // echo('ss');
        // return;
    }
    public function board_files_thumbnail(){
        $files = Storage::allFiles('/shared_files');
        $imageFiles = array_filter($files, function ($file) {
            return in_array(pathinfo($file, PATHINFO_EXTENSION), ['JPG', 'JPEG', 'PNG', 'GIF', 'jpg', 'jpeg', 'png', 'gif']);
        });

        foreach($imageFiles as $file){

            GenerateThumbnailJob::dispatch($file);
            // $parentDirectory = dirname($file);
            // $fileName = pathinfo(basename($file), PATHINFO_FILENAME);
      
            //     $height = 130;
            
            //         $img = Image::make(storage_path('app/'.$file));
                    
            //         $thumbnail = $img->encode('webp')->resize(null, $height, function($constraint) {
            //             $constraint->aspectRatio();
            //             $constraint->upsize();
            //         });  
            //         if (!Storage::disk('local')->exists($parentDirectory . '/thumbnail')) {
            //             Storage::disk('local')->makeDirectory($parentDirectory . '/thumbnail');
            //         }
            //         $thumbnailPath = storage_path('app/') .  $parentDirectory . '/thumbnail/' .  $fileName  . '_thumbnail.webp';
            //         $thumbnail->save($thumbnailPath, 100);
            
           
            // echo($fileName);           

            echo('<br>');
        }
        // dd($imageFiles);
        return 'hi';

    }
    public function createThumbnails(){
        $files = Storage::allFiles('/post_files');
        $imageFiles = array_filter($files, function ($file) {
            return in_array(pathinfo($file, PATHINFO_EXTENSION), ['JPG', 'JPEG', 'PNG', 'GIF', 'jpg', 'jpeg', 'png', 'gif']);
        });
        foreach($imageFiles as $file) {
            GeneratePostThumbnail::dispatch($file)->onQueue('postThumbnail');
            
        }



        // foreach ($file_records as $file) {
        //     $imgPath = storage_path('app/post_files/') . $file->id . '_' . $file->user_id . '_' . $file->path . '.' . $file->extension;
        //     $height = 130;
        //     if (file_exists($imgPath)) {
        //         $img = Image::make($imgPath);
                
        //         $thumbnail = $img->encode('webp')->resize(null, $height, function($constraint) {
        //             $constraint->aspectRatio();
        //             $constraint->upsize();
        //         });  
        
        //         // Save the thumbnail
        //         $thumbnailPath = storage_path('app/post_files/') . $file->id . '_' . $file->user_id . '_' . $file->path . '_thumbnail.webp';
        //         $thumbnail->save($thumbnailPath, 100);
        //     }
        // }
 
        

        return 'success';
    }
    public function sync_first_month_calendar_shift(){
       
        $users = User::where('deleted_flag', 0)->where('retire', 0)->where('hide_flag', 0)->pluck('id')->toArray();
        foreach($users as $id){
            $shift_records = shiftRecord::where('user_id', $id)
            ->where('shift_day', '>', '2023-12-31')
            ->whereIn('shift_type', [0, 2, 3, 5, 14, 15])
            ->select('shift_type AS type', 'shift_day AS date')
            // ->groupBy('shift_day')
            ->get()->groupBy(function($date) {
                return Carbon::parse($date->date)->format('Y-m');
            });
            
            foreach ($shift_records as $key => $value) {
                $date = Carbon::parse($key);

                $year = $date->year; 
                $month = $date->month; 
                $createSchedule = $this->sharedService->syncShiftToCalendar($id, $year, $month, $value);   
                echo($createSchedule);
            }
           
        }
    }
    public function move_note_to_task(){
        $all_memos = memoRecord::where('deleted_flag', 0)->get();
        foreach($all_memos as $memo){
            $board = boardRecord::where('id', $memo->board_id)->where('deleted_flag', 0)->first();
            if($board){
                $users = $board->board_to_users()->pluck('user_id')->toArray();
                $newTask = taskRecord::create([
                    'user_id' => $memo->id,
                    'board_id' => $memo->board_id,
                    'remarks' => $memo->content,
                    'created_at' => $memo->created_at,
                    'updated_at' => $memo->updated_at
                ]);
                foreach($users as $user){
                    $newTaskUser = taskUser::create([
                        'record_id' => $newTask->id,
                        'user_id' => $user,
                    ]);
                }
                
                echo(count($users));
                echo('<br>');
            }
            
            
        }

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
            $board_users = $board->board_to_users()->where('deleted_status', 0)->pluck('user_id')->toArray();
            
            
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
    public function reactedUsersMake(){
        $messages = MessageRecord::whereNotNull('reacted_users')->skip(280000)->take(20000)->get();
        $userExist = User::pluck('id')->toArray();
        $modelCollection = collect($userExist);
        foreach($messages as $message){
            $list = explode(',', $message->reacted_users);
            if(!empty($list)){
                $filteredSecondArray = collect($list)->filter(function ($item) use ($modelCollection) {
                    return $modelCollection->contains($item);
                })->toArray();
                $message->reactedUsers()->sync($filteredSecondArray);                
            }            
        }
        echo('success280000');
        return ;
    }
    public function checkedUsersMake(){
        $messages = MessageRecord::whereNotNull('checked_users')->skip(280000)->take(20000)->get();
        $userExist = User::pluck('id')->toArray();
        $modelCollection = collect($userExist);
        foreach($messages as $message){
            $list = explode(',', $message->checked_users);
            if(!empty($list)){
                $filteredSecondArray = collect($list)->filter(function ($item) use ($modelCollection) {
                    return $modelCollection->contains($item);
                })->toArray();
                $message->checkedUsers()->sync($filteredSecondArray);                
            }            
        }
        echo('success280000');
        return ;
    }
    public function uncheckedUsersMake(){
        $messages = MessageRecord::whereNotNull('unchecked_users')->skip(280000)->take(20000)->get();
        $userExist = User::pluck('id')->toArray();
        $modelCollection = collect($userExist);
        foreach($messages as $message){
            $list = explode(',', $message->unchecked_users);
            if(!empty($list)){
                $filteredSecondArray = collect($list)->filter(function ($item) use ($modelCollection) {
                    return $modelCollection->contains($item);
                })->toArray();
                $message->uncheckedUsers()->sync($filteredSecondArray);                
            }            
        }
        echo('success280000');
        return ;
    }
    public function change_to_dummy(){
        $list = User::get();

        foreach($list as $user){
            $createIcon = $this->sharedService->createUserDefaultIcon($user, Auth::id());   
        }
        // $icons = Icons::where('use_of', 'board')->forceDelete();

        // $board = BoardRecord::where('id', 1143)->first();
        // $createIcon = $this->sharedService->createBoardDefaultIcon($board, Auth::id());   
        return 'hhh';
     


    }
    public function create_notice_board(){

        // $board = new BoardRecord;
        // $board->user_id = 610;
        // $board->title = 'お知らせ';
        // $board->private_flag = 0;
        // $board->save();
        $block = [450];

        $users = User::where('deleted_flag', 0)->whereNotIn('id', $block)->whereNot('position_id', 13)->where('retire', 0)->where('hide_flag', 0)->where('partner_flag', 0)->where('id', '>', 105)->pluck('id')->toArray();
        // print_r(count($users));

        

        $oshirase_current_members = boardToUser::where('record_id', 1056)->where('deleted_flag', 0)->pluck('user_id')->toArray();

        $uniqueInArray1 = collect($users)->diff($oshirase_current_members)->toArray();
        foreach($uniqueInArray1 as $id){
            echo($id);
            echo('<br>');
        }
        // print_r($uniqueInArray1);
        // echo($oshirase_current_members);



        // $notices = NoticeRecords::where('deleted_flag', 0)->get();
        // echo($notices);
        return;
    }
    public function migrate_app_files_to_message_files(){
        $files = AppFileRecord::where('recycle_flag', 0)->get();

        foreach($files as $file){
            $filePath = $file->record_id . '/' . $file->path . '.' . $file->extension;
            if (Storage::disk('local')->exists('managed_files/' . $filePath)) {
                $rec = messageFile::create([
                    'board_id' => $file['record_id'], 
                    'user_id' => $file['user_id'], 
                    'name' => $file['name'], 
                    'mime_type' => $file['mime_type'], 
                    'extension' => $file['extension'], 
                    'size' => $file['size'], 
                    'message_id' => 0
                ]);
                $copy = Storage::disk('local')->copy('managed_files/' . $filePath,'shared_files/' . $file->record_id . '/'. $rec->id . '_' . $file['user_id'] . '_0.' . $file['extension']);
                echo($copy);
            }
        }
        
        return;
    }
    public function removeTemprorayFiles(){ //Cron Job
        // $directory = 'temp_upload';

        // $maxAgeInDays = 7;

        // $thresholdTimestamp = now()->subDays($maxAgeInDays);

        // $files = Storage::disk('local')->files($directory);

        // foreach ($files as $file) {
        //     $fileTimestamp = Storage::disk('local')->lastModified($file);

        //     if ($fileTimestamp <= $thresholdTimestamp->timestamp) {
        //         Storage::disk('local')->delete($file);
        //         $this->info("Deleted: $file");
        //     }
        // }
        $line = Carbon::now()->subDays(7)->format('Y:m:d H:i:s');
        $unused_files = messageFile::where('message_id', null)
            ->where('created_at', '<', $line)   
            ->get();
        foreach($unused_files as $file){           
            $del = Storage::disk('local')->delete('temp_upload/' . $file->id . '.' . $file->extension);        
            $file->delete();            
        }
        return;
    }
}
