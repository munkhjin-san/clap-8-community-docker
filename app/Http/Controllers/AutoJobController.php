<?php

namespace App\Http\Controllers;
use App\Models\boardRecord;
use App\Models\boardToUser;
use App\Models\customFieldDataRecord;
use App\Models\timecardRecord;
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
use App\Models\LessonPortfolio;
use App\Models\LessonSection;
use App\Models\LessonMaterial;
use App\Models\ClapRecord;
use App\Models\SupportMailFormRecord;
use App\Http\Controllers\BoardController;
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
use League\Csv\Reader;
use League\Csv\Statement;
class AutoJobController extends Controller

{
    protected $sharedService;
    protected $boardController;
    public function __construct(SharedService $sharedService, BoardController $boardController)
    {
        $this->sharedService = $sharedService;
        $this->boardController = $boardController;
        // $this->middleware('throttle:3,1');
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

    public function incident_report(){
        $yesterday = date("Y-m-d",strtotime('-1 day'));
        $incident_list = customFieldDataRecord::where('date', $yesterday)
                                                ->where('type_id', 40)
                                                ->where('value_int', 1)
                                                ->with('user')
                                                ->get();
        $support_result = SupportMailFormRecord::where('deleted_flag', 0)
                                                ->where('created_at', '>=', $yesterday)
                                                ->count();
        
        $incident_result = '';
        $is_first = true;
        foreach($incident_list as $incident){
            $date = htmlspecialchars($incident->date, ENT_QUOTES, 'UTF-8');
            $name = htmlspecialchars($incident->user['name'], ENT_QUOTES, 'UTF-8');
            if ($is_first) {
                $incident_result .= $date . '　氏名：' . $name . "\n";
                $is_first = false;
            } else {
                $incident_result .= '                             ' . $date . '　氏名：' . $name . "\n";
            }
        }
        if ($incident_result) {
            $incident_result = "・インシデント：" . $incident_result . "└上記対象社員へ内容をヒアリングしていただき、共有をお願い致します。\n";
        } else {
            $incident_result = "・インシデント：なし\n";
        }
        $message = "<a class=\"toAll\">@全員</a>\n各位\nお疲れ様です。経営管理本部です。\n昨日までにインシデント有を選択されていた社員は下記の通りです。\n\n" . $incident_result . "・サポートデスク：{$support_result}件";
        $mention_users = boardToUser::where('record_id', 3532)->pluck('user_id')->toArray();
        $this->send_report(610, 3532, $message, $mention_users);
        
        return response()->json($message);
    }

    public function weekly_report(){
        $message = <<<EOT
        <a class="toAll">@全員</a>
        各位
        お疲れ様です。経営管理本部です。
        
        担当部門において、以下の事項について変更または変更予定がある場合は、速やかに経営管理本部にご連絡いただきますようお願い申し上げます。
        また、変更の可能性がある場合は、事前に経営管理本部と情報共有をお願いいたします。
        
        【人員配置に関する事項】
        ・部門間での異動
        ・パートナー社員の追加・終了
        
        【人事考課に関する事項】
        ・成果目標確認（提出状況・進捗）
        ・半年管理対象者進捗確認
        
        【法務に関する事項】
        ・新規契約
        ・契約更新
        
        下記派遣更新者（派遣料金・就業場所等変更がないかご確認の程よろしくお願いいたします）
        https://docs.google.com/spreadsheets/d/1G6sn_MaaunSbrkEarEKBQbDdtG-WoxS5G8ZhWV4LKGI/edit#gid=116057480
        
        ・契約終了
        
        【会計に関する事項】
        ・仮払未精算
        ・未入金確認
        ・交際費稟議
        ・稟議未計上
        
        【事業戦略に関する事項】
        ・収支入力・更新
        ・タスクの期日確認
        ・戦略会議の宿題進捗
        
        【総務に関する事項】
        ・事務所移転
        ・車入替
        
        https://docs.google.com/spreadsheets/d/1upDP3a8e2TLgaWPPcFCoCxbAGtLvUJ5YZ_F4HVgJy4U/edit#gid=0
        
        ・物品移動
        ・kintoneアカウント追加・削除
        
        【インシデントに関する事項】
        ・インシデント報告・進捗各位
        EOT;
        $mention_users = boardToUser::where('record_id', 3599)->pluck('user_id')->toArray();
        $chat = $this->send_report(610, 3599, $message, $mention_users);
        $chatMessage = $chat->original['data'];
        $override_user = User::select('id', 'name', 'icon_id')->findOrFail(610);
        $checkData = [
            'type' => 'confirm',
            'users' => $mention_users,
            'msg_id' => $chatMessage->id,
            'override_user' => $override_user
        ];
        $check_request = new Request($checkData);
        $this->boardController->checkRequest($check_request);
        return response()->json($chatMessage);
    }
    public function monthly_report1(){
        $month = date("m");
        $nextMonth = date("n", strtotime("+1 month"));
        $message = <<<EOT
        <a class="toAll">@全員</a>
        🔔お願い🔔
        翌月成果シート作成、翌月シフト、勤怠月締め、勤怠管理者変更について
        
        各位
        お疲れ様です、経営管理本部です。
        表題の件4点のお願いです。
        
        1.成果シート
        {$month}月25日(木)が提出期日となっております。
        作成後、上長に提出をお願い致します。
        
        契約社員、正社員、執行役員すべて同じアプリに入力いただきます。
        人事考課時にメンターと共に設定した職務評価基準のレベルにふさわしい内容を目標としてください。
        
        成果目標の確認責任は各案件の執行役員（プロジェクトマネージャー）となります。
        ▶成果目標アプリ
        https://glowd-hldgs.cybozu.com/k/954
        
        2.来月のシフト
        {$month}月25日(木)が提出期日となっております。
        作成をお願い致します。
        
        3.勤怠月締め
        {$nextMonth}月2日(火)が月締めの期日となります。
        こちらも早めのご対応をお願い致します。
        
        4.勤怠承認者変更・マイグループ変更の方がいらっしゃいましたら、ご連絡お願い致します。
        
        ＊転籍社員の方には別途上長の方から、ご連絡をお願い致します。
        
        お忙しいとは思いますが、ご対応の程、宜しくお願い致します。
        EOT;
        $mention_users = boardToUser::where('record_id', 1056)->pluck('user_id')->toArray();
        $this->send_report(610, 1056, $message, $mention_users);
        return response()->json($message);
    }
    public function monthly_report2(){
        $nextMonth = date("n", strtotime("+1 month"));
        $message = "<a class=\"toAll\">@全員</a>\n🔔立替経費・仮払　領収証原本の郵送につきまして🔔\nお疲れ様です。経営管理本部　平川です。\n\n"
                . $nextMonth . "月2日(木) までに、レコードの作成、執行役員承認済みのものが" . $nextMonth . "月精算となります。\n\n"
                . "領収書は" . $nextMonth . "月9日(木) までに本社必着で郵送お願いいたします。";
        $mention_users = boardToUser::where('record_id', 1056)->pluck('user_id')->toArray();
        $this->send_report(610, 1056, $message, $mention_users);
        return response()->json($message);
    }
    public function monthly_report3S(){
        $month = date("n");
        $message = <<<EOT
        <a class=\"toAll\">@全員</a>
        お疲れ様です。経営管理本部 宇都宮です。

        {$month}月の3Sご回答をお願い致します。

        フルネーム・漢字・苗字と名前のスペースなしでの入力を宜しくお願いいたします。
        また、物品管理でのご自身の貸与品について変更がないか確認を必ずお願いいたします。

        個人単位➝全社員・パートナー社員必須　
        　　　　　営業所に常駐してない場合も必須になります。
        　　　　　現在の職場でのルールを把握して、3Sを運用出来ているかの
        　　　　　確認になります。
        　　　　　[3Sフォーム]https://forms.gle/E6BkpHZauLWoeBm4A

        3Sとは
        「会社のお片付け」「お掃除活動」などの意味合いももちろんありますが、きれいにすることだけが目的ではありません。
        3S活動の目的は、「安全」で「効率的」で「快適」な職場を作ることです。

        物品管理の確認方法
        「1.毎月の個別物品確認を徹底しており、常に管理情報が最新の状態である。」
        上記の項目は、物品管理アプリが常に最新の情報が入力されている状態にする為に、毎月個人で実施していただきます。
        https://docs.google.com/document/d/1n3_T-y5q43mpfulaZYHPlwF4mAMNeqjbWXLuuFK267g/edit?usp=sharing

        不明点等出てくるかと思いますが、毎月不明点・質問事項などは、下記URLまでお願い致します。
        https://docs.google.com/spreadsheets/d/1DtHU5xa5uEu2Q6Zhu6fHEbhoukTn7oNIXdU-A-53-7k/edit?usp=sharing
        EOT;
        $mention_users = boardToUser::where('record_id', 1056)->pluck('user_id')->toArray();
        $this->send_report(610, 1056, $message, $mention_users);
        return response()->json($message);
    }
    private function send_report($override_user_id, $board_id, $message, $mention_users){
        
        $override_user = User::select('id', 'name', 'icon_id')->findOrFail($override_user_id);
        $requestData = [
            'record_id' => $board_id,
            'override_user_id' => $override_user_id,
            'message' => $message,
            'mentioned_users' => $mention_users,
            'override_user' => $override_user,
        ];
        $request = new Request($requestData);
        $chat = $this->boardController->chatAdd($request);
        return $chat;

        
    }
}
