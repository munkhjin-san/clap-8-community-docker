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
use App\Models\WelcomeMessage;
use App\Models\workGroup;
use App\Models\ProjectRecord;
use App\Models\ProjectMember;
use App\Models\customFieldDataRecord;
use App\Models\UserLeaveRecord;
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
use Illuminate\Validation\ValidationException;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\IncidentPunishment;
class AutoJobController extends Controller

{
    protected $sharedService;
    protected $gemini_url;
    public function __construct(SharedService $sharedService)
    {
        $this->sharedService = $sharedService;

        $this->gemini_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent';
        // $this->middleware('throttle:3,1');
    }
    public function get_welcome_message(){
        $date = Carbon::now()->format('Y-m-d');
        $message = WelcomeMessage::where('date', $date)->latest()->first();
        if($message){
            return response()->json($message);
        }
        return response()->json(['message' => 'No message found'], 404);
    }
    public function generate_welcome_message(){
        $apiKey = config('app.openai_api_key');
        $date = Carbon::now()->format('Y-m-d');
        $instruction = <<<EOD
            今日は「〇〇の日」です。  
            この記念日をテーマに、その背景や文化的意味から連想される  
            “ちょっと哲学的で、少しだけ前向きになれる”ような短い一文を丁寧語で出力してください。  
            1文のみ（句点1つ）、90〜110文字程度を目安に、やさしい口調でシンプルに言い切ってください。  
            文末の結び方は、下記の4タイプから1つを日替わりで使用してください。
            ## 🎯 文末の結び方タイプ（4分類）

            | タグ      | 割合  | 目的          | 文末の例文                |
            | ------- | --- | ----------- | -------------------- |
            | ❓ 問いかけ  | 20% | 余韻・思考を促す    | 〜なのかもしれませんね。／〜でしょうか？ |
            | 💡 提案   | 20% | 軽い行動の後押し    | 〜してみてはいかがでしょうか。      |
            | ✅ 言い切り  | 30% | 安定感・説得力     | 〜なのです。／〜にすぎません。      |
            | 😄 ユーモア | 30% | 親しみ・軽さ・ニヤリ感 | 〜ってことにしておきましょうか。     |

            ※ユーモアタイプは“寒すぎない・やりすぎない”ラインで調整すること
            例：「今日くらいはそれでいいと思いませんか。」
            「気にせず乗り切って、あとで考えましょうか。」

            ---

            ## 🔁 出力サンプル（4タイプ）

            **今日は「パンツの日」**
            人に見えない部分を整えることが、自分への信頼につながるのかもしれませんね。

            **今日は「七夕」**
            願いごとを言葉にするだけで、未来に向けた一歩になることもあるのです。

            **今日は「海苔の日」**
            おにぎりに巻くだけで評価されるなら、自分もそれくらいでいい日があっていいですよね。

            **今日は「歯ブラシ交換デー」**
            そろそろ交換してみると、心も口もスッキリするかもしれません。


        EOD;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
        ])->post('https://api.openai.com/v1/responses', [
            'model' => 'gpt-4.1',
            'instructions' => $instruction,
            'input' => $date,
        ]);
        Log::info('Welcome message request sent', [
            'date' => $date,
            'response_status' => $response->status(),
            'error' => $response->failed() ? $response->body() : null,
        ]);
        $data = $response->json();
        // dd($data);
        $text = data_get($data, 'output.0.content.0.text', '');
        WelcomeMessage::create([
            'date' => $date,
            'content' => $text,
            'chunks' => []
        ]);
        Log::info('Welcome message generated', [
            'date' => $date,
            'content' => $text,
        ]);
        // dd($text);
       
        // $apiKey = config('app.gemini_api_key');

        // $thisMonth = Carbon::now()->format('m');
        // $thisMonthWithoutzero = ltrim($thisMonth, '0');
        // $thisDay = Carbon::now()->format('d');
        // $day = "{$thisMonthWithoutzero}月{$thisDay}日";
        // if (empty($apiKey)) {
        //     return response()->json(['message' => 'API key not found'], 400);
        // }
        
        // $instruction = <<<EOD
        //     {$day}は日本国内もしくは国際で何の日ですか。ネットで検索し次のようにメッセージを作成してください。
        //     1個だけでいいです。もし結果が複数の場合ランダムで選択してください。
        //     最大150文字にまとめてください。
        //     そしてちょっとしたメッセージも付けてください。
        //     例1：今日は『〇〇の日』です。△△しましょう。
        //     フォーマットは：本日は『〇〇日』です。△△。
        //     NGな例：今日は『〇月〇日』です。
        //     注意：作成したメッセージのみを返してください。
        //     「承知しました」や「了解しました」などの前置きは不要です。
        // EOD;
        // // Prepare payload
        // $payload = [
        //     'contents' => [
        //         [
        //             'role' => 'user',
        //             'parts' => [
        //                 [
        //                     'text' => $instruction,
        //                 ],
        //             ],
        //         ],
        //     ],
        //     "tools" => [
        //         [
        //             "google_search" => (object)[]
        //         ]
        //     ],

        //     'generationConfig' => [
        //         'temperature' => 1,
        //         'topK' => 40,
        //         'topP' => 0.95,
        //         'maxOutputTokens' => 8192,
        //         'responseMimeType' => 'text/plain'
        //     ],
        // ];
    
        // // Send request
        // $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=$apiKey";
        // $response = Http::withHeaders([
        //     'Content-Type' => 'application/json',
        // ])->post($url, $payload);

        
        // $date = Carbon::now()->format('Y-m-d');
        // $data = $response->json();
        // $chunks = collect(data_get($data, 'candidates.0.groundingMetadata.groundingChunks'));
        // WelcomeMessage::create([
        //     'date' => $date,
        //     'content' => data_get($data, 'candidates.0.content.parts.0.text'),
        //     'chunks' => $chunks
        // ]);

        // dd($data);
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
    public function db_structure() {
        $database = DB::getDatabaseName();

        $tables = DB::table('information_schema.tables')
            ->where('table_schema', $database)
            ->pluck('table_name');

        $structure = [];

        foreach ($tables as $table) {
            $columns = DB::table('information_schema.columns')
                ->select('column_name', 'data_type', 'is_nullable', 'column_default', 'column_type')
                ->where('table_schema', $database)
                ->where('table_name', $table)
                ->get();

            $structure[$table] = [];

            foreach ($columns as $column) {
                $structure[$table][$column->column_name] = [
                    'type'     => $column->data_type,
                    'nullable' => $column->is_nullable,
                    'default'  => $column->column_default,
                    'full_type' => $column->column_type,
                ];
            }
        }

        return [$database => $structure];
    }
    public function departure_report(Request $request){
        $user_id = $request->input('user');
        $date = $request->input('date');
        $user = User::findOrFail($user_id);
        $data = $this->sharedService->createDepartureReport($user, $date);
        return view('departure_report_result', $data);

    }
    public function get_today_things() {
        $date = Carbon::now()->format('Y-m-d');
        $schedules = CalendarRecord::whereHas('calendar_users', function ($query) {
                $query->where('users.id', Auth::id());
            })
            ->where(function ($q) use ($date) {
                $q->whereDate('date_start', $date)
                ->orWhereDate('date_end', $date);
            })
            ->with(['calendar_users', 'calendar_view_users'])
            ->get();
        $members = User::where('retire', 0)
            ->whereHas('custom_field_data_records', function ($q) use ($date) {
                $q->whereDate('date', $date)
                    ->where('type_id', 43)
                    ->whereNotNull('value_text');
            })->with(['custom_field_data_records' => function ($q) use ($date) {
                $q->whereDate('date', $date)
                    ->where('type_id', 43)
                    ->whereNotNull('value_text')
                    ->select('id', 'user_id', 'date', 'type_id', 'value_text', 'value_int')->with('emotedUsers');
            }])->select('id', 'name', 'icon_bg', 'icon_path')->get();
        $progressExpr = "
            TIMESTAMPDIFF(SECOND, created_at, NOW()) /
            NULLIF(TIMESTAMPDIFF(SECOND, created_at, CONCAT(end_at, ' 23:59:59')), 0)
        ";
        // 1) Not started, already 50% of period passed
        $notStartedReminders = TaskRecord::whereHas('executors', function ($q) {
                $q->where('users.id', Auth::id())
                ->where('progress_flag', 0); // not started
            })
            ->whereNotNull('end_at')
            ->whereRaw("$progressExpr >= 0.5")
            ->with([
                'executors', 
                'files', 
                'supervisors', 
                'project', 
                'board.board_to_users',
            ])
            ->orderByDesc('created_at')
            ->get();

        // 2) In progress, already 80% of period passed
        $inProgressReminders = TaskRecord::whereHas('executors', function ($q) {
                $q->where('users.id', Auth::id())
                ->where('progress_flag', 1); // working
            })
            ->whereNotNull('end_at')
            ->whereRaw("$progressExpr >= 0.8")
            ->with([
                'executors', 
                'files', 
                'supervisors', 
                'project', 
                'board.board_to_users',
            ])
            ->orderByDesc('created_at')
            ->get();
        $tasks = $notStartedReminders->merge($inProgressReminders);

        return response()->json([
            'schedules' => $schedules,
            'members' => $members,
            'tasks' => $tasks,
        ]);
    }
    public function board_badge_update_auto()
    {
        $ng_list = ['推し', '知人', '家族', '友人', '関係者', 'お知らせアカウント'];
        $all_users = User::where('deleted_flag', 0)
        ->where('retire', 0)
        ->whereNotIn('name', $ng_list)
        ->select('id', 'name', 'icon_path', 'icon_bg')
        ->get();
        $result_users = [];
        foreach($all_users as $user) {
            $user_id = $user->id;
            $leavePeriod = UserLeaveRecord::where('user_id', $user_id)
                ->where('active', 2)
                ->first();
            $savedLastMessages = boardToUser::where('user_id', $user_id)
                ->where('deleted_status', 0)
                ->where('deleted_flag', 0)
                ->whereNull('left_at')
                ->whereHas('board', function ($q) {
                    $q->where('deleted_flag', 0)->where('deleted_at', null);
                })
                ->where(function ($query) {
                    $query->whereHas('user', function ($q) {
                        $q->where('on_leave', 0);
                    })
                    ->orWhereHas('board', function ($q) {
                        $q->where('private_flag', 1); 
                    });
                })
                ->orderBy('record_id', 'desc')
                ->get();
            
            foreach($savedLastMessages as $record){
                $last = $record->last_message;
                if(!empty($last)){
                    $unread_count = $record->messageRecords()
                    ->where(function ($query) {
                        $query->where('info_flag', '!=', 1)
                                ->where('info_flag', '!=', 2);
                    })
                    ->where('draft_flag', 0)
                    ->when($last, function ($q) use ($last) {
                        $q->where('id', '>', $last);
                    })
                    ->when($record->created_at, function ($q) use ($record) {
                        $q->where('created_at', '>=', $record->created_at);
                    })->when($leavePeriod, function ($query) use ($leavePeriod) {
                        $query->where(function ($q) use ($leavePeriod) {
                            $q->whereHas('board_record', function ($q) {
                                $q->where('private_flag', 1);
                            })
                            ->orWhereNotBetween('created_at', [$leavePeriod->leave_start, $leavePeriod->leave_end]);
                        });
                    })->count();

                    $record->unread_count = $unread_count;
                    $record->save(); 
                    $result_users[] = [
                        'user' => $user,
                        'record' => $record
                    ];
                }else{
                    if($record->last_act == null){
                        $record->unread_count = 1;
                        $record->save(); 
                    }
                }               
                        
            }
        }
        return response()->json($result_users);
    }
    public function incident_fill(){
        $users = User::select('id', 'name')->get();
        $userMap = $users->pluck('name', 'id')->toArray();
        $userListNoSpace = $users->mapWithKeys(function ($user) {
            return [$user->id => str_replace(' ', '', $user->name)];
        })->toArray();

        $loadedJsonFile = Storage::disk('local')->get('incident.json');
        $incidents = json_decode($loadedJsonFile, true);

        $projects = ProjectRecord::pluck('id', 'name')->toArray();
        $projectListNoSpace = collect($projects)->mapWithKeys(function ($id, $name) {
            return [$id => str_replace(' ', '', $name)];
        })->toArray();

        $incidentCategories = IncidentCategory::pluck('id', 'name')->toArray();
        $incidentCategoryList = collect($incidentCategories)->mapWithKeys(function ($id, $name) {
            return [$id => $name];
        })->toArray();

        $incidentPunishments = IncidentPunishment::pluck('id', 'name')->toArray();
        $incidentPunishmentList = collect($incidentPunishments)->mapWithKeys(function ($id, $name) {
            return [$id => $name];
        })->toArray();

        foreach($incidents as $incident){
            $reporter_id = null;
            $causer_id = null;
            $project_record_id = null;
            $incident_category_id = null;
            $incident_punishment_id = null;
            $memo1 = $incident['memo'] ?? '';
            $memo2 = $incident['memo2'] ?? '';
            $memo_combined = $memo1 . ' ' . $memo2;
            $created_at = $incident['created_at'] ? Carbon::parse($incident['created_at']) : now();
            $updated_at = $incident['updated_at'] ? Carbon::parse($incident['updated_at']) : now();
            $committee_decision_date = $incident['committee_decision_date'] ? Carbon::parse($incident['committee_decision_date']) : null;
            $private_note1 = $incident['private_notes'] ?? '';
            $private_note2 = $incident['private_notes2'] ?? '';
            $private_note_combined = $private_note1 . ' ' . $private_note2;
            
            if($incident['reported_by']){
                $reporter_id = array_search($incident['reported_by'], $userListNoSpace);          
            }
            if($incident['caused_by']){
                $causer_id = array_search($incident['caused_by'], $userListNoSpace);                
            }
            if($incident['occurred_date']){
                $incident['occurred_date'] = Carbon::parse($incident['occurred_date'])->format('Y-m-d');
            }
            if($incident['project_record_id']){
                $project_record_id = array_search($incident['project_record_id'], $projectListNoSpace);                
            }
            if($incident['incident_category']){
                $incident_category_id = array_search($incident['incident_category'], $incidentCategoryList);                
            }
            if($incident['incident_punishment']){
                $incident_punishment_id = array_search($incident['incident_punishment'], $incidentPunishmentList);                
            }
            
            // dd($reporter_id);
            $incident['private_notes'] = $private_note_combined ? $private_note_combined : null;
            $incident['memo'] = $memo_combined ? $memo_combined : null;
            $incident['reported_by'] = $reporter_id;
            $incident['caused_by'] = $causer_id;
            $incident['project_record_id'] = $project_record_id;
            $incident['incident_category_id'] = $incident_category_id;
            $incident['incident_punishment_id'] = $incident_punishment_id;
            $incident['created_at'] = $created_at;
            $incident['updated_at'] = $updated_at;
            $incident['committee_decision_date'] = $committee_decision_date;
            $incident['instruction_date'] = $incident['instruction_date'] ? Carbon::parse($incident['instruction_date'])->format('Y-m-d') : null;

            //drop memo2, private_note2, and original string fields
            unset($incident['memo2'], $incident['private_notes2'], $incident['incident_category'], $incident['incident_punishment']);
            $createRecord = Incident::create($incident);
            echo('Created incident record with ID: ' . $createRecord->id . "\n");            
        }   
    }
}
