<?php

namespace App\Http\Controllers;
use App\Mail\Summary;
use App\Models\boardRecord;
use App\Models\boardToUser;
use App\Models\CalendarMeetingSummary;
use App\Models\CalendarRecord;
use App\Models\ChallengeRecord;
use App\Models\KnowledgeRecord;
use App\Models\NiceRecord;
use App\Models\PostRecord;
use App\Models\timecardRecord;
use App\Models\User;
use App\Models\timecardCostRecord;
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
use App\Models\CommentRecord;
use App\Models\shiftType;
use App\Models\FileRecord;
use App\Models\UserAlbum;
use App\Models\LessonPortfolio;
use App\Models\LessonSection;
use App\Models\LessonMaterial;
use App\Models\ClapRecord;
use App\Models\workGroup;
use App\Models\ProjectRecord;
use App\Models\ProjectMember;
use App\Mail\Warning;
use Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File; 
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Services\SharedService;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;
use App\Jobs\GenerateThumbnailJob;
use App\Jobs\GeneratePostThumbnail;
use League\Csv\Reader;
use League\Csv\Statement;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
class AutoJobController extends Controller

{
    protected $sharedService;
    public function __construct(SharedService $sharedService)
    {
        $this->sharedService = $sharedService;
        // $this->middleware('throttle:3,1');
    }
    
    public function zoom_event(Request $request){
        $data = $request->all();
        $path = $request->path();
        $zoom_keys = [
            'zoom1_event' => 'QrGNdoq8TqaJkyOHOnsxhw',
            'zoom2_event' => 'mp63oT6YQbevg-j1z-v-ag',
            'zoom3_event' => 'N7Uu27CwQsOQokLFQQyAgA',
        ];
        
        if ($data['event'] === 'endpoint.url_validation') {
            $plainToken = $data['payload']['plainToken'];            
            $secret = $zoom_keys[$path];            
            $encryptedToken = hash_hmac('sha256', $plainToken, $secret);
            $response = [
                'plainToken' => $plainToken,
                'encryptedToken' => $encryptedToken,
            ];            
            return response()->json($response, 200);
        }

        if($data['event'] === 'meeting.summary_completed'){
            $meeting_id = $data['payload']['object']['meeting_id'];
            $calendar = CalendarRecord::where('zoom_id', $meeting_id)->first();
            $filePath = storage_path('logs/zoomEvent.log');
            if (!File::exists($filePath)) {
                File::put($filePath, '');
            }
            
            $details = Arr::get($data, 'payload.object.summary_details', []);
            $nextSteps = Arr::get($data, 'payload.object.next_steps', []);
            $title = Arr::get($data, 'payload.object.summary_title', '');
            $overview = Arr::get($data, 'payload.object.summary_overview', '');
            Log::channel('zoom')->info("{$meeting_id}",['title' => $title]);
                
                $summary = CalendarMeetingSummary::updateOrCreate(
                    [
                        'meeting_id' => $meeting_id,
                        'title' => $title,
                        'overview' => $overview
                    ],
                );
                foreach($details as $detail){
                    $summary->details()->updateOrCreate(
                        [
                            'label' => $detail['label'],
                            'summary' => $detail['summary']
                        ]
                    );
                }
                foreach($nextSteps as $step){
                    $summary->steps()->updateOrCreate(
                        [
                            'content' => $step
                        ]
                    );
                }
                if ($summary->wasRecentlyCreated) {
                    if(!empty($calendar)){
                        $members = $calendar->calendar_users()->whereNotNull('email')->get();
                        $emails = collect($members)->filter(function($user){
                            return filter_var($user->email, FILTER_VALIDATE_EMAIL);
                        })->pluck('email')->toArray();  
                        $details = [
                            "title" => $calendar->title,
                            "content" => $summary->overview,
                        ]; 
                        foreach($emails as $to){
                            Mail::to($to)->send(new Summary($details, $calendar->id));
                        }
                    }
                }
            return response()->json(['message' => 'data_received'], 200);
        }
        return response()->json(['message' => 'Invalid event'], 400);
    }
    public function clap_process(){
        $update1 = ClapRecord::where('app_name', 'board')->update(['app_id' => 1]);
        $update2 = ClapRecord::where('app_name', 'knowledge')->update(['app_id' => 2]);
        $update3 = ClapRecord::where('app_name', 'nice')->update(['app_id' => 3]);
        $update4 = ClapRecord::where('app_name', 'challenge')->update(['app_id' => 4]);
        $update5 = ClapRecord::where('app_name', 'comment')->update(['app_id' => 5]);
    
        echo('<br>board');
        echo($update1);
        echo('<br>knowledge');
        echo($update2);
        echo('<br>nice');
        echo($update3);
        echo('<br>challenge');
        echo($update4);
        echo('<br>comment');
        echo($update5);
    }
    public function process_csv(){

        $csvFilePath = storage_path('app/self.csv');

        $csv = Reader::createFromPath($csvFilePath, 'r');
        $csv->setHeaderOffset(0);

        $records = (new Statement())->process($csv);
        
        $json = iterator_to_array($records);

        $materials_ids = LessonMaterial::where('lesson_theme_id', 1)->where('priority', 1)->pluck('id')->toArray();
        foreach($json as $row){
            $user_code = (int) $row['社員コード'];
            $user = User::where('user_code', $user_code)->select('id', 'name', 'awareness')->first();
            $content = $row['ポートフォリオ'] == '提出済' ? $user->awareness : $row['ポートフォリオ'];
            $portfolio = LessonPortfolio::firstOrCreate(['lesson_theme_id' => 1, 'user_id' => $user->id]);

            
            
            $portfolio->update([
                "status" => 3,
                "public_content" => $content,
                "positive_feedback" => $row['グループディスカッションでどのようなフィードバックをもらいましたか。']

            ]);
            foreach($materials_ids as $materials_id){
                LessonSection::firstOrCreate([
                    "material_id" => $materials_id,
                    "user_id" => $user->id,
                    "portfolio_id" => $portfolio->id
                ])->update([
                    "status" => 2
                ]);
            }

        }
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



        return 'success';
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

        // foreach($list as $user){
        //     $createIcon = $this->sharedService->createUserDefaultIcon($user, Auth::id());   
        // }
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

    public function removeTemprorayFiles(){ //Cron Job
        $directory = 'temp_upload';

        $maxAgeInDays = 7;

        $thresholdTimestamp = now()->subDays($maxAgeInDays);

        $files = Storage::disk('local')->files($directory);

        foreach ($files as $file) {
            $fileTimestamp = Storage::disk('local')->lastModified($file);

            if ($fileTimestamp <= $thresholdTimestamp->timestamp) {
                Storage::disk('local')->delete($file);
                // $this->info("Deleted: $file");
            }
        }
        // $line = Carbon::now()->subDays(7)->format('Y:m:d H:i:s');
        // $unused_files = messageFile::where('message_id', null)
        //     ->where('created_at', '<', $line)   
        //     ->get();
        // foreach($unused_files as $file){           
        //     $del = Storage::disk('local')->delete('temp_upload/' . $file->id . '.' . $file->extension);        
        //     $file->delete();            
        // }
        return;
    }

    public function change_shift_status(){
        $date = Carbon::now();
        $year = $date->year;
        $month = $date->month;
        $all_shifts = shiftRecord::whereMonth('shift_day', '>', $month)
                                    ->whereYear('shift_day', '>=', $year)
                                    ->whereNot('shift_type', 3)
                                    ->update([
                                        'status_flag' => 2
                                    ]);
        return response()->json($all_shifts);
    }

    public function remove_work_files(){
        $line = Carbon::now()->subDays(7)->format('Y:m:d H:i:s');
        $unused_files = timecardCostRecord::where('deleted_at', '<=', $line)->onlyTrashed()->get();
        foreach($unused_files as $file){           
            Storage::disk('local')->delete('timecard_files/' . $file->file_path);        
        }
        return response()->json($unused_files);
    }

    public function timecard_update(){
        $timecards = timecardRecord::whereHas('department')->with('department')->get();
        $projects = ProjectRecord::pluck('id', 'name');
        foreach ($timecards as $tc) {
            $projectId = $projects[$tc->department->name] ?? null;
            if ($projectId) {
                $tc->work_group_id = $projectId;
                $tc->save();
            }
        }
        return response()->json(['success']);
    }
}
